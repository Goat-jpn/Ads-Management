module.exports = {
  apps: [{
    name: 'kanho-ads-manager',
    script: 'mariadb-server.js',
    cwd: '/home/user/webapp',
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'development',
      PORT: 8080,
      DB_HOST: 'localhost',
      DB_PORT: 3306,
      DB_NAME: 'kanho_adsmanager',
      DB_USER: 'kanho_adsmanager',
      DB_PASS: 'Kanho20200701'
    },
    log_file: './logs/app.log',
    out_file: './logs/out.log',
    error_file: './logs/error.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    watch: false,
    ignore_watch: ['node_modules', 'logs'],
    restart_delay: 1000,
    max_restarts: 10,
    min_uptime: '10s'
  }]
};