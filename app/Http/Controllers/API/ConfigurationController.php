<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Services\ConfigurationValidator;

class ConfigurationController extends BaseController
{
    /**
     * Get configuration status for all services
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        $status = ConfigurationValidator::getConfigurationStatus();
        
        return $this->sendResponse($status, 'Configuration status retrieved successfully');
    }
    
    /**
     * Validate all configurations
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validate()
    {
        try {
            $results = ConfigurationValidator::validateAll(false);
            
            $allConfigured = true;
            foreach ($results as $service => $result) {
                if (!$result['configured']) {
                    $allConfigured = false;
                    break;
                }
            }
            
            if ($allConfigured) {
                return $this->sendResponse($results, 'All services are properly configured');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Some services are not properly configured',
                    'data' => $results
                ], 400);
            }
        } catch (\Exception $e) {
            return $this->sendError('Configuration validation failed', [$e->getMessage()], 500);
        }
    }
    
    /**
     * Get configuration status for a specific service
     *
     * @param string $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function serviceStatus($service)
    {
        $validServices = ['quickbooks', 'database', 'app'];
        
        if (!in_array($service, $validServices)) {
            return $this->sendError('Invalid service name', ['Valid services: ' . implode(', ', $validServices)], 400);
        }
        
        $isConfigured = ConfigurationValidator::isServiceConfigured($service);
        $status = ConfigurationValidator::getConfigurationStatus();
        
        return $this->sendResponse([
            'service' => $service,
            'configured' => $isConfigured,
            'details' => $status[$service] ?? null
        ], 'Service configuration status retrieved successfully');
    }
}