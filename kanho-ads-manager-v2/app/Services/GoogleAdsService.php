<?php

namespace App\Services;

use Google\Ads\GoogleAds\Lib\V16\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V16\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V16\Services\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V16\Services\CustomerServiceClient;
use Google\Ads\GoogleAds\V16\Services\CampaignServiceClient;
use Google\Ads\GoogleAds\V16\Services\AdGroupServiceClient;
use Google\Ads\GoogleAds\V16\Services\AdGroupAdServiceClient;
use Google\Ads\GoogleAds\V16\Services\SearchGoogleAdsStreamRequest;
use Google\Ads\GoogleAds\V16\Services\ListAccessibleCustomersRequest;
use Google\Ads\GoogleAds\V16\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V16\Enums\AdGroupStatusEnum\AdGroupStatus;
use Google\ApiCore\ApiException;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Google Ads API Service Class
 */
class GoogleAdsService
{
    private ?GoogleAdsClient $googleAdsClient = null;
    private Logger $logger;
    private array $config;
    
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->config = [
            'GOOGLE_DEVELOPER_TOKEN' => $_ENV['GOOGLE_DEVELOPER_TOKEN'] ?? '',
            'GOOGLE_CLIENT_ID' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
            'GOOGLE_CLIENT_SECRET' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
            'GOOGLE_REFRESH_TOKEN' => $_ENV['GOOGLE_REFRESH_TOKEN'] ?? '',
            'GOOGLE_LOGIN_CUSTOMER_ID' => $_ENV['GOOGLE_LOGIN_CUSTOMER_ID'] ?? '',
        ];
        
        // ログ設定
        $this->logger = new Logger('google_ads');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/google_ads.log', Logger::DEBUG));
        
        $this->initializeClient();
    }
    
    /**
     * Google Ads APIクライアントを初期化
     */
    private function initializeClient(): void
    {
        try {
            // OAuth2トークン設定
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId($this->config['GOOGLE_CLIENT_ID'])
                ->withClientSecret($this->config['GOOGLE_CLIENT_SECRET'])
                ->withRefreshToken($this->config['GOOGLE_REFRESH_TOKEN'])
                ->build();
            
            // Google Ads クライアント設定
            $this->googleAdsClient = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken($this->config['GOOGLE_DEVELOPER_TOKEN'])
                ->withLoginCustomerId($this->config['GOOGLE_LOGIN_CUSTOMER_ID'])
                ->build();
            
            $this->logger->info('Google Ads API client initialized successfully');
            
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize Google Ads API client: ' . $e->getMessage());
            throw new Exception('Google Ads API初期化に失敗しました: ' . $e->getMessage());
        }
    }
    
    /**
     * アクセス可能なアカウントリストを取得
     * 
     * @return array
     */
    public function getAccessibleAccounts(): array
    {
        try {
            if (!$this->googleAdsClient) {
                throw new Exception('Google Ads client not initialized');
            }
            
            $customerServiceClient = $this->googleAdsClient->getCustomerServiceClient();
            
            // アクセス可能な顧客アカウントを取得
            $request = new ListAccessibleCustomersRequest();
            $accessibleCustomers = $customerServiceClient->listAccessibleCustomers($request);
            
            $accounts = [];
            foreach ($accessibleCustomers->getResourceNames() as $resourceName) {
                // 顧客IDを抽出 (customers/123456789 → 123456789)
                $customerId = str_replace('customers/', '', $resourceName);
                
                // 詳細情報を取得
                $accountInfo = $this->getAccountInfo($customerId);
                if ($accountInfo) {
                    $accounts[] = $accountInfo;
                }
            }
            
            $this->logger->info('Retrieved ' . count($accounts) . ' accessible accounts');
            return $accounts;
            
        } catch (ApiException $e) {
            $this->logger->error('API Exception in getAccessibleAccounts: ' . $e->getMessage());
            throw new Exception('アカウント取得に失敗しました: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('Exception in getAccessibleAccounts: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 特定のアカウント情報を取得
     * 
     * @param string $customerId
     * @return array|null
     */
    public function getAccountInfo(string $customerId): ?array
    {
        try {
            if (!$this->googleAdsClient) {
                throw new Exception('Google Ads client not initialized');
            }
            
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = "SELECT customer.id, customer.descriptive_name, customer.currency_code, 
                             customer.time_zone, customer.manager, customer.test_account 
                      FROM customer 
                      WHERE customer.id = $customerId";
            
            $stream = $googleAdsServiceClient->searchStream(
                new SearchGoogleAdsStreamRequest([
                'customer_id' => $customerId,
                'query' => $query
            ])
            );
            
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                $customer = $googleAdsRow->getCustomer();
                
                return [
                    'customer_id' => (string)$customer->getId(),
                    'name' => $customer->getDescriptiveName(),
                    'currency' => $customer->getCurrencyCode(),
                    'timezone' => $customer->getTimeZone(),
                    'is_manager' => $customer->getManager(),
                    'is_test_account' => $customer->getTestAccount()
                ];
            }
            
            return null;
            
        } catch (ApiException $e) {
            $this->logger->warning("Failed to get account info for customer $customerId: " . $e->getMessage());
            return null;
        } catch (Exception $e) {
            $this->logger->error("Exception in getAccountInfo for customer $customerId: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * アカウントのキャンペーンデータを取得
     * 
     * @param string $customerId
     * @return array
     */
    public function getCampaigns(string $customerId): array
    {
        try {
            if (!$this->googleAdsClient) {
                throw new Exception('Google Ads client not initialized');
            }
            
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = "SELECT campaign.id, campaign.name, campaign.status, 
                             campaign.advertising_channel_type, campaign.start_date, 
                             campaign.end_date, metrics.impressions, metrics.clicks, 
                             metrics.ctr, metrics.cost_micros, metrics.average_cpc 
                      FROM campaign 
                      WHERE segments.date DURING LAST_30_DAYS 
                      ORDER BY campaign.name";
            
            $stream = $googleAdsServiceClient->searchStream(
                new SearchGoogleAdsStreamRequest([
                'customer_id' => $customerId,
                'query' => $query
            ])
            );
            
            $campaigns = [];
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                $campaign = $googleAdsRow->getCampaign();
                $metrics = $googleAdsRow->getMetrics();
                
                $campaigns[] = [
                    'campaign_id' => (string)$campaign->getId(),
                    'name' => $campaign->getName(),
                    'status' => CampaignStatus::name($campaign->getStatus()),
                    'channel_type' => $campaign->getAdvertisingChannelType(),
                    'start_date' => $campaign->getStartDate(),
                    'end_date' => $campaign->getEndDate(),
                    'impressions' => $metrics->getImpressions(),
                    'clicks' => $metrics->getClicks(),
                    'ctr' => $metrics->getCtr(),
                    'cost_micros' => $metrics->getCostMicros(),
                    'average_cpc' => $metrics->getAverageCpc()
                ];
            }
            
            $this->logger->info("Retrieved " . count($campaigns) . " campaigns for customer $customerId");
            return $campaigns;
            
        } catch (ApiException $e) {
            $this->logger->error("API Exception in getCampaigns for customer $customerId: " . $e->getMessage());
            throw new Exception('キャンペーンデータの取得に失敗しました: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error("Exception in getCampaigns for customer $customerId: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * キャンペーンの広告グループを取得
     * 
     * @param string $customerId
     * @param string $campaignId
     * @return array
     */
    public function getAdGroups(string $customerId, string $campaignId): array
    {
        try {
            if (!$this->googleAdsClient) {
                throw new Exception('Google Ads client not initialized');
            }
            
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = "SELECT ad_group.id, ad_group.name, ad_group.status, 
                             ad_group.cpc_bid_micros, metrics.impressions, metrics.clicks, 
                             metrics.ctr, metrics.cost_micros, metrics.average_cpc 
                      FROM ad_group 
                      WHERE campaign.id = $campaignId 
                      AND segments.date DURING LAST_30_DAYS 
                      ORDER BY ad_group.name";
            
            $stream = $googleAdsServiceClient->searchStream(
                new SearchGoogleAdsStreamRequest([
                'customer_id' => $customerId,
                'query' => $query
            ])
            );
            
            $adGroups = [];
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                $adGroup = $googleAdsRow->getAdGroup();
                $metrics = $googleAdsRow->getMetrics();
                
                $adGroups[] = [
                    'ad_group_id' => (string)$adGroup->getId(),
                    'name' => $adGroup->getName(),
                    'status' => AdGroupStatus::name($adGroup->getStatus()),
                    'cpc_bid_micros' => $adGroup->getCpcBidMicros(),
                    'impressions' => $metrics->getImpressions(),
                    'clicks' => $metrics->getClicks(),
                    'ctr' => $metrics->getCtr(),
                    'cost_micros' => $metrics->getCostMicros(),
                    'average_cpc' => $metrics->getAverageCpc()
                ];
            }
            
            $this->logger->info("Retrieved " . count($adGroups) . " ad groups for campaign $campaignId");
            return $adGroups;
            
        } catch (ApiException $e) {
            $this->logger->error("API Exception in getAdGroups for campaign $campaignId: " . $e->getMessage());
            throw new Exception('広告グループデータの取得に失敗しました: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error("Exception in getAdGroups for campaign $campaignId: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * アカウントのパフォーマンスサマリーを取得
     * 
     * @param string $customerId
     * @param string $dateRange (例: "LAST_30_DAYS", "THIS_MONTH", "LAST_7_DAYS")
     * @return array
     */
    public function getPerformanceSummary(string $customerId, string $dateRange = 'LAST_30_DAYS'): array
    {
        try {
            if (!$this->googleAdsClient) {
                throw new Exception('Google Ads client not initialized');
            }
            
            $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();
            
            $query = "SELECT metrics.impressions, metrics.clicks, metrics.ctr, 
                             metrics.cost_micros, metrics.average_cpc, metrics.conversions, 
                             metrics.conversion_rate, metrics.cost_per_conversion 
                      FROM customer 
                      WHERE segments.date DURING $dateRange";
            
            $stream = $googleAdsServiceClient->searchStream(
                new SearchGoogleAdsStreamRequest([
                'customer_id' => $customerId,
                'query' => $query
            ])
            );
            
            $totalMetrics = [
                'impressions' => 0,
                'clicks' => 0,
                'cost_micros' => 0,
                'conversions' => 0
            ];
            
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                $metrics = $googleAdsRow->getMetrics();
                
                $totalMetrics['impressions'] += $metrics->getImpressions();
                $totalMetrics['clicks'] += $metrics->getClicks();
                $totalMetrics['cost_micros'] += $metrics->getCostMicros();
                $totalMetrics['conversions'] += $metrics->getConversions();
            }
            
            // CTRと平均CPCを計算
            $ctr = $totalMetrics['clicks'] > 0 ? 
                   ($totalMetrics['clicks'] / $totalMetrics['impressions']) * 100 : 0;
            $averageCpc = $totalMetrics['clicks'] > 0 ? 
                         $totalMetrics['cost_micros'] / $totalMetrics['clicks'] : 0;
            $conversionRate = $totalMetrics['clicks'] > 0 ? 
                             ($totalMetrics['conversions'] / $totalMetrics['clicks']) * 100 : 0;
            $costPerConversion = $totalMetrics['conversions'] > 0 ? 
                                $totalMetrics['cost_micros'] / $totalMetrics['conversions'] : 0;
            
            return [
                'impressions' => $totalMetrics['impressions'],
                'clicks' => $totalMetrics['clicks'],
                'ctr' => $ctr,
                'cost_micros' => $totalMetrics['cost_micros'],
                'cost_yen' => $totalMetrics['cost_micros'] / 1000000, // マイクロから円に変換
                'average_cpc' => $averageCpc,
                'conversions' => $totalMetrics['conversions'],
                'conversion_rate' => $conversionRate,
                'cost_per_conversion' => $costPerConversion,
                'date_range' => $dateRange
            ];
            
        } catch (ApiException $e) {
            $this->logger->error("API Exception in getPerformanceSummary for customer $customerId: " . $e->getMessage());
            throw new Exception('パフォーマンスサマリーの取得に失敗しました: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error("Exception in getPerformanceSummary for customer $customerId: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * APIクライアントの動作確認
     * 
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $accounts = $this->getAccessibleAccounts();
            return count($accounts) > 0;
        } catch (Exception $e) {
            $this->logger->error('Connection test failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ログの取得
     * 
     * @return string
     */
    public function getLastLogEntry(): string
    {
        $logFile = __DIR__ . '/../../logs/google_ads.log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            return end($lines) ?: 'No log entries found';
        }
        return 'Log file not found';
    }
}