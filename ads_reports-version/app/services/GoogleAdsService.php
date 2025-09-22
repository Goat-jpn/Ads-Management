<?php

namespace App\Services;

use Google\Ads\GoogleAds\Lib\V16\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V16\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V16\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V16\Services\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V16\Services\SearchGoogleAdsRequest;
use Google\Ads\GoogleAds\V16\Enums\ReportingDataDateRangeEnum\ReportingDataDateRange;
use App\Utils\Logger;
use Exception;
use DateTime;

class GoogleAdsService
{
    private GoogleAdsClient $googleAdsClient;
    private Logger $logger;
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->logger = Logger::getInstance();
        $this->initializeClient();
    }

    /**
     * Google Ads クライアントを初期化
     */
    private function initializeClient(): void
    {
        try {
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId($this->config['google_ads']['client_id'])
                ->withClientSecret($this->config['google_ads']['client_secret'])
                ->withRefreshToken($this->config['google_ads']['refresh_token'])
                ->build();

            $this->googleAdsClient = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken($this->config['google_ads']['developer_token'])
                ->withLoginCustomerId($this->config['google_ads']['login_customer_id'])
                ->build();

            $this->logger->info('Google Ads API client initialized successfully');
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize Google Ads API client: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * アカウント情報を取得
     */
    public function getAccountInfo(string $customerId): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = "SELECT customer.id, customer.descriptive_name, customer.currency_code, 
                             customer.time_zone, customer.status 
                      FROM customer 
                      WHERE customer.id = {$customerId}";
            
            $request = SearchGoogleAdsRequest::build($customerId, $query);
            $response = $googleAdsServiceClient->search($request);
            
            $accountInfo = [];
            foreach ($response->iterateAllElements() as $row) {
                $customer = $row->getCustomer();
                $accountInfo = [
                    'id' => $customer->getId(),
                    'name' => $customer->getDescriptiveName(),
                    'currency_code' => $customer->getCurrencyCode(),
                    'time_zone' => $customer->getTimeZone(),
                    'status' => $customer->getStatus()
                ];
                break;
            }
            
            $this->logger->apiLog('google_ads', 'get_account_info', ['customer_id' => $customerId]);
            return $accountInfo;
        } catch (Exception $e) {
            $this->logger->error('Failed to get account info: ' . $e->getMessage(), ['customer_id' => $customerId]);
            throw $e;
        }
    }

    /**
     * 日別のアカウントパフォーマンスデータを取得
     */
    public function getDailyAccountPerformance(string $customerId, DateTime $startDate, DateTime $endDate): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = $endDate->format('Y-m-d');
            
            $query = "SELECT 
                        segments.date,
                        metrics.impressions,
                        metrics.clicks,
                        metrics.cost_micros,
                        metrics.conversions,
                        metrics.ctr,
                        metrics.average_cpc,
                        metrics.cost_per_conversion,
                        metrics.conversion_rate
                      FROM customer 
                      WHERE segments.date BETWEEN '{$startDateStr}' AND '{$endDateStr}'";
            
            $request = SearchGoogleAdsRequest::build($customerId, $query);
            $response = $googleAdsServiceClient->search($request);
            
            $performanceData = [];
            foreach ($response->iterateAllElements() as $row) {
                $segments = $row->getSegments();
                $metrics = $row->getMetrics();
                
                $performanceData[] = [
                    'date' => $segments->getDate(),
                    'impressions' => $metrics->getImpressions(),
                    'clicks' => $metrics->getClicks(),
                    'cost' => $metrics->getCostMicros() / 1000000, // マイクロ単位を通常単位に変換
                    'conversions' => $metrics->getConversions(),
                    'ctr' => $metrics->getCtr(),
                    'average_cpc' => $metrics->getAverageCpc() / 1000000,
                    'cost_per_conversion' => $metrics->getCostPerConversion() / 1000000,
                    'conversion_rate' => $metrics->getConversionRate()
                ];
            }
            
            $this->logger->apiLog('google_ads', 'get_daily_performance', [
                'customer_id' => $customerId,
                'start_date' => $startDateStr,
                'end_date' => $endDateStr,
                'records_count' => count($performanceData)
            ]);
            
            return $performanceData;
        } catch (Exception $e) {
            $this->logger->error('Failed to get daily performance data: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'start_date' => $startDateStr ?? null,
                'end_date' => $endDateStr ?? null
            ]);
            throw $e;
        }
    }

    /**
     * キャンペーンパフォーマンスデータを取得
     */
    public function getCampaignPerformance(string $customerId, DateTime $startDate, DateTime $endDate): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = $endDate->format('Y-m-d');
            
            $query = "SELECT 
                        campaign.id,
                        campaign.name,
                        campaign.status,
                        segments.date,
                        metrics.impressions,
                        metrics.clicks,
                        metrics.cost_micros,
                        metrics.conversions,
                        metrics.ctr,
                        metrics.average_cpc,
                        metrics.cost_per_conversion,
                        metrics.conversion_rate
                      FROM campaign 
                      WHERE segments.date BETWEEN '{$startDateStr}' AND '{$endDateStr}'
                      AND campaign.status = 'ENABLED'";
            
            $request = SearchGoogleAdsRequest::build($customerId, $query);
            $response = $googleAdsServiceClient->search($request);
            
            $campaignData = [];
            foreach ($response->iterateAllElements() as $row) {
                $campaign = $row->getCampaign();
                $segments = $row->getSegments();
                $metrics = $row->getMetrics();
                
                $campaignData[] = [
                    'campaign_id' => $campaign->getId(),
                    'campaign_name' => $campaign->getName(),
                    'status' => $campaign->getStatus(),
                    'date' => $segments->getDate(),
                    'impressions' => $metrics->getImpressions(),
                    'clicks' => $metrics->getClicks(),
                    'cost' => $metrics->getCostMicros() / 1000000,
                    'conversions' => $metrics->getConversions(),
                    'ctr' => $metrics->getCtr(),
                    'average_cpc' => $metrics->getAverageCpc() / 1000000,
                    'cost_per_conversion' => $metrics->getCostPerConversion() / 1000000,
                    'conversion_rate' => $metrics->getConversionRate()
                ];
            }
            
            $this->logger->apiLog('google_ads', 'get_campaign_performance', [
                'customer_id' => $customerId,
                'start_date' => $startDateStr,
                'end_date' => $endDateStr,
                'records_count' => count($campaignData)
            ]);
            
            return $campaignData;
        } catch (Exception $e) {
            $this->logger->error('Failed to get campaign performance data: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'start_date' => $startDateStr ?? null,
                'end_date' => $endDateStr ?? null
            ]);
            throw $e;
        }
    }

    /**
     * アカウント階層のクライアント一覧を取得
     */
    public function getCustomerClients(string $managerCustomerId): array
    {
        try {
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = "SELECT 
                        customer_client.client_customer,
                        customer_client.level,
                        customer_client.manager,
                        customer_client.descriptive_name,
                        customer_client.currency_code,
                        customer_client.time_zone,
                        customer_client.status
                      FROM customer_client 
                      WHERE customer_client.status = 'ENABLED'";
            
            $request = SearchGoogleAdsRequest::build($managerCustomerId, $query);
            $response = $googleAdsServiceClient->search($request);
            
            $clients = [];
            foreach ($response->iterateAllElements() as $row) {
                $customerClient = $row->getCustomerClient();
                
                $clients[] = [
                    'customer_id' => $customerClient->getClientCustomer(),
                    'level' => $customerClient->getLevel(),
                    'is_manager' => $customerClient->getManager(),
                    'name' => $customerClient->getDescriptiveName(),
                    'currency_code' => $customerClient->getCurrencyCode(),
                    'time_zone' => $customerClient->getTimeZone(),
                    'status' => $customerClient->getStatus()
                ];
            }
            
            $this->logger->apiLog('google_ads', 'get_customer_clients', [
                'manager_customer_id' => $managerCustomerId,
                'clients_count' => count($clients)
            ]);
            
            return $clients;
        } catch (Exception $e) {
            $this->logger->error('Failed to get customer clients: ' . $e->getMessage(), [
                'manager_customer_id' => $managerCustomerId
            ]);
            throw $e;
        }
    }

    /**
     * API接続テスト
     */
    public function testConnection(): bool
    {
        try {
            $customerId = $this->config['google_ads']['login_customer_id'];
            $this->getAccountInfo($customerId);
            
            $this->logger->info('Google Ads API connection test successful');
            return true;
        } catch (Exception $e) {
            $this->logger->error('Google Ads API connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * レート制限チェック
     */
    public function checkRateLimit(): array
    {
        $rateLimitConfig = $this->config['api']['rate_limit']['google_ads'];
        
        return [
            'requests_per_minute' => $rateLimitConfig['requests_per_minute'],
            'requests_per_day' => $rateLimitConfig['requests_per_day'],
            'current_usage' => $this->getCurrentUsage() // 実装が必要
        ];
    }

    /**
     * 現在のAPI使用量を取得（簡易実装）
     */
    private function getCurrentUsage(): array
    {
        // 実際の実装では、ログファイルやキャッシュから使用量を取得
        return [
            'minute' => 0,
            'day' => 0
        ];
    }
}