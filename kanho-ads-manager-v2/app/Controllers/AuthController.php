<?php

namespace App\Controllers;

use App\Models\User;

class AuthController
{
    private $userModel;
    
    public function __construct()
    {
        $this->userModel = new User();
    }
    
    public function showLogin()
    {
        if (is_logged_in()) {
            redirect('/dashboard');
            return;
        }
        
        require_once __DIR__ . '/../../views/auth/login.php';
    }
    
    public function showRegister()
    {
        if (is_logged_in()) {
            redirect('/dashboard');
            return;
        }
        
        require_once __DIR__ . '/../../views/auth/register.php';
    }
    
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login');
            return;
        }
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validate input
        $errors = [];
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = ['email' => $email];
            redirect('/login');
            return;
        }
        
        // Find user by email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            $_SESSION['errors'] = ['Invalid email or password'];
            $_SESSION['old_input'] = ['email' => $email];
            redirect('/login');
            return;
        }
        
        // Verify password
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            $_SESSION['errors'] = ['Invalid email or password'];
            $_SESSION['old_input'] = ['email' => $email];
            redirect('/login');
            return;
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            $_SESSION['errors'] = ['Account is inactive. Please contact administrator.'];
            redirect('/login');
            return;
        }
        
        // Login successful - set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Update last login
        $this->userModel->updateLastLoginAt($user['id']);
        
        // Handle remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 days
            
            // Store token in database (you may want to create a separate table for this)
            $this->userModel->update($user['id'], ['remember_token' => $token]);
        }
        
        // Clear any existing errors
        unset($_SESSION['errors'], $_SESSION['old_input']);
        
        // Set success message
        flash('success', 'Login successful! Welcome back, ' . $user['name']);
        
        // Redirect to intended page or dashboard
        $intended = $_SESSION['intended_url'] ?? '/dashboard';
        unset($_SESSION['intended_url']);
        
        redirect($intended);
    }
    
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/register');
            return;
        }
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        // Validate input
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Name is required';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($this->userModel->findByEmail($email)) {
            $errors[] = 'Email already exists';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
        }
        
        if ($password !== $passwordConfirm) {
            $errors[] = 'Password confirmation does not match';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = ['name' => $name, 'email' => $email];
            redirect('/register');
            return;
        }
        
        // Create user
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $password, // Will be hashed in the model
            'role' => 'user',
            'is_active' => 1
        ];
        
        $userId = $this->userModel->create($userData);
        
        if (!$userId) {
            $_SESSION['errors'] = ['Failed to create account. Please try again.'];
            $_SESSION['old_input'] = ['name' => $name, 'email' => $email];
            redirect('/register');
            return;
        }
        
        // Auto-login the user
        $user = $this->userModel->find($userId);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Clear old input
        unset($_SESSION['errors'], $_SESSION['old_input']);
        
        // Set success message
        flash('success', 'Account created successfully! Welcome to Kanho Ads Manager.');
        
        redirect('/dashboard');
    }
    
    public function logout()
    {
        // Clear remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            
            if (isset($_SESSION['user_id'])) {
                $this->userModel->update($_SESSION['user_id'], ['remember_token' => null]);
            }
        }
        
        // Clear session
        session_destroy();
        session_start(); // Restart session for flash message
        
        flash('info', 'You have been logged out successfully.');
        
        redirect('/login');
    }
    
    public function showForgotPassword()
    {
        if (is_logged_in()) {
            redirect('/dashboard');
            return;
        }
        
        require_once __DIR__ . '/../../views/auth/forgot-password.php';
    }
    
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/forgot-password');
            return;
        }
        
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errors'] = ['Please enter a valid email address'];
            redirect('/forgot-password');
            return;
        }
        
        $user = $this->userModel->findByEmail($email);
        
        if ($user) {
            // Generate reset token
            $token = $this->userModel->generateResetToken($email);
            
            // In a real application, you would send an email here
            // For now, we'll just show the token (remove this in production)
            flash('info', "Password reset token generated: {$token}");
        }
        
        // Always show the same message for security
        flash('info', 'If an account with that email exists, a password reset link has been sent.');
        
        redirect('/forgot-password');
    }
    
    public function showResetPassword()
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            flash('error', 'Invalid reset token.');
            redirect('/forgot-password');
            return;
        }
        
        $user = $this->userModel->verifyResetToken($token);
        
        if (!$user) {
            flash('error', 'Invalid or expired reset token.');
            redirect('/forgot-password');
            return;
        }
        
        require_once __DIR__ . '/../../views/auth/reset-password.php';
    }
    
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/forgot-password');
            return;
        }
        
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        // Validate token
        $user = $this->userModel->verifyResetToken($token);
        
        if (!$user) {
            flash('error', 'Invalid or expired reset token.');
            redirect('/forgot-password');
            return;
        }
        
        // Validate password
        $errors = [];
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
        }
        
        if ($password !== $passwordConfirm) {
            $errors[] = 'Password confirmation does not match';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect("/reset-password?token={$token}");
            return;
        }
        
        // Update password
        $this->userModel->updatePassword($user['id'], $password);
        $this->userModel->clearResetToken($user['id']);
        
        flash('success', 'Password has been reset successfully. You can now login with your new password.');
        
        redirect('/login');
    }
    
    public function profile()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $user = $this->userModel->find($_SESSION['user_id']);
        
        require_once __DIR__ . '/../../views/auth/profile.php';
    }
    
    public function updateProfile()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/profile');
            return;
        }
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';
        
        $user = $this->userModel->find($_SESSION['user_id']);
        $errors = [];
        
        // Validate name
        if (empty($name)) {
            $errors[] = 'Name is required';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters';
        }
        
        // Validate email
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($email !== $user['email']) {
            // Check if new email already exists
            if ($this->userModel->findByEmail($email)) {
                $errors[] = 'Email already exists';
            }
        }
        
        // Validate password change if provided
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required to change password';
            } elseif (!$this->userModel->verifyPassword($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
                $errors[] = 'New password must contain at least one uppercase letter, one lowercase letter, and one number';
            } elseif ($newPassword !== $newPasswordConfirm) {
                $errors[] = 'New password confirmation does not match';
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('/profile');
            return;
        }
        
        // Update profile
        $updateData = [
            'name' => $name,
            'email' => $email
        ];
        
        if (!empty($newPassword)) {
            $this->userModel->updatePassword($user['id'], $newPassword);
        }
        
        $this->userModel->update($user['id'], $updateData);
        
        // Update session
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        flash('success', 'Profile updated successfully.');
        
        redirect('/profile');
    }
}