<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;
use Exception;
use DateTime;

class YahooAdsService
{
    private Client $httpClient;
    private Logger $logger;
    private array $config;
    private array $accessTokens = []; // プラットフォーム別のアクセストークン

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->logger = Logger::getInstance();
        $this->httpClient = new Client([
            'timeout' => $this->config['api']['timeout'],
            'verify' => true,
        ]);
    }

    /**
     * Display Ads API用のアクセストークンを取得
     */
    public function getDisplayAdsAccessToken(): string
    {
        if (!isset($this->accessTokens['display']) || $this->isTokenExpired('display')) {
            $this->accessTokens['display'] = $this->refreshDisplayAdsToken();
        }
        
        return $this->accessTokens['display']['access_token'];
    }

    /**
     * Search Ads API用の認証ヘッダーを取得
     */
    public function getSearchAdsAuthHeaders(): array
    {
        return [
            'license-id' => $this->config['yahoo_ads']['search']['license_id'],
            'api-account-id' => $this->config['yahoo_ads']['search']['api_account_id'],
            'api-account-password' => $this->config['yahoo_ads']['search']['api_account_password'],
        ];
    }

    /**
     * Display Ads APIのトークンリフレッシュ
     */
    private function refreshDisplayAdsToken(): array
    {
        try {
            $response = $this->httpClient->post('https://auth.login.yahoo.co.jp/yconnect/v2/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->config['yahoo_ads']['display']['refresh_token'],
                    'client_id' => $this->config['yahoo_ads']['display']['app_id'],
                    'client_secret' => $this->config['yahoo_ads']['display']['secret'],
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);

            $tokenData = json_decode($response->getBody()->getContents(), true);
            $tokenData['expires_at'] = time() + $tokenData['expires_in'] - 300; // 5分の余裕を持つ
            
            $this->logger->apiLog('yahoo_display', 'token_refresh', ['success' => true]);
            
            return $tokenData;
        } catch (Exception $e) {
            $this->logger->error('Failed to refresh Yahoo Display Ads token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * トークンの有効期限チェック
     */
    private function isTokenExpired(string $platform): bool
    {
        if (!isset($this->accessTokens[$platform]['expires_at'])) {
            return true;
        }
        
        return time() >= $this->accessTokens[$platform]['expires_at'];
    }

    /**
     * Display Ads API - アカウント情報取得
     */
    public function getDisplayAdsAccountInfo(string $accountId): array
    {
        try {
            $accessToken = $this->getDisplayAdsAccessToken();
            
            $response = $this->httpClient->get(
                "https://ads-display.yahooapis.jp/api/v14/AccountManagement/Account/{$accountId}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ]
                ]
            );

            $accountData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->apiLog('yahoo_display', 'get_account_info', ['account_id' => $accountId]);
            
            return $accountData;
        } catch (Exception $e) {
            $this->logger->error('Failed to get Yahoo Display Ads account info: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Display Ads API - パフォーマンスレポート取得
     */
    public function getDisplayAdsPerformanceReport(string $accountId, DateTime $startDate, DateTime $endDate): array
    {
        try {
            $accessToken = $this->getDisplayAdsAccessToken();
            
            $requestBody = [
                'reportJob' => [
                    'accountId' => (int)$accountId,
                    'reportName' => 'Daily Performance Report - ' . date('Y-m-d H:i:s'),
                    'dateRange' => [
                        'startDate' => $startDate->format('Y-m-d'),
                        'endDate' => $endDate->format('Y-m-d'),
                    ],
                    'fields' => [
                        'DAY',
                        'IMPS',
                        'CLICKS',
                        'COST',
                        'CONVERSIONS',
                        'CTR',
                        'CPC',
                        'CPA',
                        'CONV_RATE'
                    ],
                    'format' => 'JSON'
                ]
            ];

            // レポートジョブを作成
            $response = $this->httpClient->post(
                'https://ads-display.yahooapis.jp/api/v14/ReportDefinitionService/add',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $requestBody
                ]
            );

            $jobResponse = json_decode($response->getBody()->getContents(), true);
            $jobId = $jobResponse['rval']['values'][0]['reportJob']['jobId'];

            // ジョブの完了を待機
            $this->waitForReportCompletion($jobId, 'display');

            // レポートデータを取得
            $reportData = $this->downloadDisplayAdsReport($jobId);

            $this->logger->apiLog('yahoo_display', 'get_performance_report', [
                'account_id' => $accountId,
                'job_id' => $jobId,
                'records_count' => count($reportData)
            ]);

            return $reportData;
        } catch (Exception $e) {
            $this->logger->error('Failed to get Yahoo Display Ads performance report: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Search Ads API - アカウント情報取得
     */
    public function getSearchAdsAccountInfo(string $accountId): array
    {
        try {
            $headers = array_merge($this->getSearchAdsAuthHeaders(), [
                'Content-Type' => 'application/json',
            ]);

            $response = $this->httpClient->post(
                'https://ads-search.yahooapis.jp/api/v14/AccountService/get',
                [
                    'headers' => $headers,
                    'json' => [
                        'selector' => [
                            'accountIds' => [(int)$accountId]
                        ]
                    ]
                ]
            );

            $accountData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->apiLog('yahoo_search', 'get_account_info', ['account_id' => $accountId]);
            
            return $accountData;
        } catch (Exception $e) {
            $this->logger->error('Failed to get Yahoo Search Ads account info: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Search Ads API - パフォーマンスレポート取得
     */
    public function getSearchAdsPerformanceReport(string $accountId, DateTime $startDate, DateTime $endDate): array
    {
        try {
            $headers = array_merge($this->getSearchAdsAuthHeaders(), [
                'Content-Type' => 'application/json',
            ]);

            $requestBody = [
                'operations' => [
                    [
                        'accountId' => (int)$accountId,
                        'operand' => [
                            'reportName' => 'Daily Performance Report - ' . date('Y-m-d H:i:s'),
                            'dateRange' => [
                                'startDate' => $startDate->format('Ymd'),
                                'endDate' => $endDate->format('Ymd'),
                            ],
                            'fields' => [
                                'DAY',
                                'IMPRESSIONS',
                                'CLICKS',
                                'COST',
                                'CONVERSIONS',
                                'CTR',
                                'CPC',
                                'COST_PER_CONVERSION',
                                'CONVERSION_RATE'
                            ],
                            'reportType' => 'ACCOUNT'
                        ],
                        'operator' => 'ADD'
                    ]
                ]
            ];

            // レポートジョブを作成
            $response = $this->httpClient->post(
                'https://ads-search.yahooapis.jp/api/v14/ReportDefinitionService/mutate',
                [
                    'headers' => $headers,
                    'json' => $requestBody
                ]
            );

            $jobResponse = json_decode($response->getBody()->getContents(), true);
            $jobId = $jobResponse['rval']['values'][0]['reportDefinition']['reportJobId'];

            // ジョブの完了を待機
            $this->waitForReportCompletion($jobId, 'search');

            // レポートデータを取得
            $reportData = $this->downloadSearchAdsReport($jobId);

            $this->logger->apiLog('yahoo_search', 'get_performance_report', [
                'account_id' => $accountId,
                'job_id' => $jobId,
                'records_count' => count($reportData)
            ]);

            return $reportData;
        } catch (Exception $e) {
            $this->logger->error('Failed to get Yahoo Search Ads performance report: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * レポート完了まで待機
     */
    private function waitForReportCompletion(string $jobId, string $platform): void
    {
        $maxWait = 300; // 最大5分間待機
        $interval = 10;  // 10秒間隔でチェック
        $waited = 0;

        while ($waited < $maxWait) {
            $status = $this->getReportStatus($jobId, $platform);
            
            if ($status === 'COMPLETED') {
                return;
            }
            
            if ($status === 'FAILED') {
                throw new Exception("Report job failed: {$jobId}");
            }
            
            sleep($interval);
            $waited += $interval;
        }
        
        throw new Exception("Report job timeout: {$jobId}");
    }

    /**
     * レポートジョブのステータスを取得
     */
    private function getReportStatus(string $jobId, string $platform): string
    {
        try {
            if ($platform === 'display') {
                $accessToken = $this->getDisplayAdsAccessToken();
                
                $response = $this->httpClient->post(
                    'https://ads-display.yahooapis.jp/api/v14/ReportDefinitionService/get',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'selector' => [
                                'jobIds' => [(int)$jobId]
                            ]
                        ]
                    ]
                );
            } else {
                $headers = array_merge($this->getSearchAdsAuthHeaders(), [
                    'Content-Type' => 'application/json',
                ]);
                
                $response = $this->httpClient->post(
                    'https://ads-search.yahooapis.jp/api/v14/ReportDefinitionService/get',
                    [
                        'headers' => $headers,
                        'json' => [
                            'selector' => [
                                'reportJobIds' => [(int)$jobId]
                            ]
                        ]
                    ]
                );
            }

            $statusResponse = json_decode($response->getBody()->getContents(), true);
            return $statusResponse['rval']['values'][0]['reportDefinition']['reportJobStatus'];
        } catch (Exception $e) {
            $this->logger->error("Failed to get report status for job {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Display Ads レポートダウンロード
     */
    private function downloadDisplayAdsReport(string $jobId): array
    {
        try {
            $accessToken = $this->getDisplayAdsAccessToken();
            
            $response = $this->httpClient->get(
                "https://ads-display.yahooapis.jp/api/v14/ReportDefinitionService/download?jobId={$jobId}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ]
                ]
            );

            $reportContent = $response->getBody()->getContents();
            return json_decode($reportContent, true);
        } catch (Exception $e) {
            $this->logger->error("Failed to download Display Ads report {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Search Ads レポートダウンロード
     */
    private function downloadSearchAdsReport(string $jobId): array
    {
        try {
            $headers = array_merge($this->getSearchAdsAuthHeaders(), [
                'Content-Type' => 'application/json',
            ]);
            
            $response = $this->httpClient->post(
                'https://ads-search.yahooapis.jp/api/v14/ReportService/get',
                [
                    'headers' => $headers,
                    'json' => [
                        'reportJobId' => (int)$jobId
                    ]
                ]
            );

            $reportContent = $response->getBody()->getContents();
            return json_decode($reportContent, true);
        } catch (Exception $e) {
            $this->logger->error("Failed to download Search Ads report {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * API接続テスト
     */
    public function testConnections(): array
    {
        $results = [
            'display_ads' => false,
            'search_ads' => false
        ];

        // Display Ads接続テスト
        try {
            $this->getDisplayAdsAccessToken();
            $results['display_ads'] = true;
            $this->logger->info('Yahoo Display Ads API connection test successful');
        } catch (Exception $e) {
            $this->logger->error('Yahoo Display Ads API connection test failed: ' . $e->getMessage());
        }

        // Search Ads接続テスト
        try {
            $headers = $this->getSearchAdsAuthHeaders();
            if (!empty($headers['license-id']) && !empty($headers['api-account-id'])) {
                $results['search_ads'] = true;
                $this->logger->info('Yahoo Search Ads API connection test successful');
            }
        } catch (Exception $e) {
            $this->logger->error('Yahoo Search Ads API connection test failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * レート制限チェック
     */
    public function checkRateLimit(string $platform): array
    {
        $rateLimitConfig = $this->config['api']['rate_limit']["yahoo_{$platform}"];
        
        return [
            'requests_per_minute' => $rateLimitConfig['requests_per_minute'],
            'requests_per_day' => $rateLimitConfig['requests_per_day'],
            'current_usage' => $this->getCurrentUsage($platform)
        ];
    }

    /**
     * 現在のAPI使用量を取得
     */
    private function getCurrentUsage(string $platform): array
    {
        // 実際の実装では、ログファイルやキャッシュから使用量を取得
        return [
            'minute' => 0,
            'day' => 0
        ];
    }
}