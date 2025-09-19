<?php

namespace App\Services;

use App\Exceptions\ConfigurationException;
use Illuminate\Support\Facades\Log;

class ConfigurationValidator
{
    /**
     * Required configuration keys grouped by service
     */
    protected static $requiredConfigs = [
        'google' => [
            'GOOGLE_CLIENT_ID' => 'Google OAuth Client ID',
            'GOOGLE_CLIENT_SECRET' => 'Google OAuth Client Secret'
        ],
        'quickbooks' => [
            'QUICKBOOKS_BASIC_TOKEN' => 'QuickBooks Basic Token',
            'QUICKBOOKS_REALM_ID' => 'QuickBooks Realm ID',
            'QUICKBOOKS_CLIENT_ID' => 'QuickBooks Client ID',
            'QUICKBOOKS_CLIENT_SECRET' => 'QuickBooks Client Secret'
        ],
        'database' => [
            'DB_CONNECTION' => 'Database Connection Type',
            'DB_HOST' => 'Database Host',
            'DB_PORT' => 'Database Port',
            'DB_DATABASE' => 'Database Name',
            'DB_USERNAME' => 'Database Username',
            'DB_PASSWORD' => 'Database Password'
        ],
        'app' => [
            'APP_KEY' => 'Application Encryption Key',
            'APP_URL' => 'Application URL'
        ]
    ];

    /**
     * Validate configuration for a specific service
     *
     * @param string $service
     * @throws ConfigurationException
     */
    public static function validateService(string $service)
    {
        if (!isset(self::$requiredConfigs[$service])) {
            return; // Service not in validation list
        }

        $missingConfigs = [];
        $configs = self::$requiredConfigs[$service];

        foreach ($configs as $key => $description) {
            if (empty(env($key))) {
                $missingConfigs[] = "{$key} ({$description})";
            }
        }

        if (!empty($missingConfigs)) {
            $message = ucfirst($service) . " configuration error. Missing: " . implode(', ', $missingConfigs);
            Log::error($message);
            throw new ConfigurationException($message, 400);
        }
    }

    /**
     * Validate all configurations
     *
     * @param bool $throwException
     * @return array
     */
    public static function validateAll(bool $throwException = false): array
    {
        $results = [];
        $hasErrors = false;

        foreach (self::$requiredConfigs as $service => $configs) {
            $serviceResult = [
                'service' => $service,
                'configured' => true,
                'missing' => []
            ];

            foreach ($configs as $key => $description) {
                if (empty(env($key))) {
                    $serviceResult['configured'] = false;
                    $serviceResult['missing'][] = [
                        'key' => $key,
                        'description' => $description
                    ];
                    $hasErrors = true;
                }
            }

            $results[$service] = $serviceResult;
        }

        if ($hasErrors && $throwException) {
            $missingItems = [];
            foreach ($results as $service => $result) {
                if (!$result['configured']) {
                    foreach ($result['missing'] as $item) {
                        $missingItems[] = $item['key'];
                    }
                }
            }
            throw new ConfigurationException(
                'Missing required configuration: ' . implode(', ', $missingItems),
                400
            );
        }

        return $results;
    }

    /**
     * Check if a specific service is configured
     *
     * @param string $service
     * @return bool
     */
    public static function isServiceConfigured(string $service): bool
    {
        if (!isset(self::$requiredConfigs[$service])) {
            return true; // Service not in validation list, assume configured
        }

        foreach (self::$requiredConfigs[$service] as $key => $description) {
            if (empty(env($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get configuration status for all services
     *
     * @return array
     */
    public static function getConfigurationStatus(): array
    {
        $status = [];
        
        foreach (self::$requiredConfigs as $service => $configs) {
            $configured = true;
            $configuredCount = 0;
            $totalCount = count($configs);
            
            foreach ($configs as $key => $description) {
                if (!empty(env($key))) {
                    $configuredCount++;
                } else {
                    $configured = false;
                }
            }
            
            $status[$service] = [
                'configured' => $configured,
                'progress' => [
                    'configured' => $configuredCount,
                    'total' => $totalCount,
                    'percentage' => round(($configuredCount / $totalCount) * 100)
                ]
            ];
        }
        
        return $status;
    }
}