<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Credential;
use App\Exceptions\ConfigurationException;

class QuickBooksAuthController extends Controller
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $scope = 'com.intuit.quickbooks.accounting';
    protected $authBase;
    protected $isSandbox;
    
    public function __construct()
    {
        // Decode the basic token to get client ID and secret
        $basicToken = env('QUICKBOOKS_BASIC_TOKEN', '');
        if ($basicToken) {
            $decoded = base64_decode($basicToken);
            if (strpos($decoded, ':') !== false) {
                list($this->clientId, $this->clientSecret) = explode(':', $decoded);
            }
        }
        
        $this->redirectUri = url('/quickbooks/callback');
        $this->authBase = env('QUICKBOOKS_AUTH_BASE', 'https://oauth.platform.intuit.com');
        $this->isSandbox = env('QUICKBOOKS_SANDBOX', true);
    }
    
    /**
     * Redirect to QuickBooks OAuth authorization
     */
    public function connect()
    {
        if (!$this->clientId) {
            throw new ConfigurationException('QuickBooks client ID not configured. Please check QUICKBOOKS_BASIC_TOKEN in .env', 400);
        }
        
        $state = bin2hex(random_bytes(16));
        session(['quickbooks_oauth_state' => $state]);
        
        $params = [
            'client_id' => $this->clientId,
            'scope' => $this->scope,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'state' => $state,
        ];
        
        $authUrl = $this->authBase . '/oauth2/v1/authorize?' . http_build_query($params);
        
        Log::channel('quickbooks')->info('Initiating OAuth flow', [
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'auth_url' => $authUrl
        ]);
        
        return redirect($authUrl);
    }
    
    /**
     * Handle OAuth callback from QuickBooks
     */
    public function callback(Request $request)
    {
        // Verify state
        $state = $request->query('state');
        if ($state !== session('quickbooks_oauth_state')) {
            Log::channel('quickbooks')->error('OAuth state mismatch');
            return response()->json(['error' => 'Invalid state parameter'], 400);
        }
        
        // Check for errors
        if ($request->has('error')) {
            Log::channel('quickbooks')->error('OAuth error', [
                'error' => $request->query('error'),
                'description' => $request->query('error_description')
            ]);
            return response()->json([
                'error' => $request->query('error'),
                'description' => $request->query('error_description')
            ], 400);
        }
        
        $code = $request->query('code');
        $realmId = $request->query('realmId');
        
        if (!$code) {
            return response()->json(['error' => 'No authorization code received'], 400);
        }
        
        try {
            // Exchange code for tokens
            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => 'Basic ' . env('QUICKBOOKS_BASIC_TOKEN'),
                    'Accept' => 'application/json',
                ])
                ->post($this->authBase . '/oauth2/v1/tokens/bearer', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                ]);
            
            if (!$response->successful()) {
                Log::channel('quickbooks')->error('Failed to exchange code for tokens', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return response()->json([
                    'error' => 'Failed to get access token',
                    'details' => $response->json()
                ], $response->status());
            }
            
            $tokens = $response->json();
            
            // Save tokens to database
            $credential = Credential::updateOrCreate(
                ['name' => 'quickbooks'],
                [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                ]
            );
            
            Log::channel('quickbooks')->info('OAuth tokens saved successfully', [
                'realm_id' => $realmId,
                'expires_in' => $tokens['expires_in'] ?? 3600
            ]);
            
            // Update realm ID in .env if different
            if ($realmId && $realmId !== env('QUICKBOOKS_REALM_ID')) {
                return view('quickbooks-success', [
                    'message' => 'QuickBooks connected successfully!',
                    'realm_id' => $realmId,
                    'note' => 'Please update QUICKBOOKS_REALM_ID in your .env file to: ' . $realmId
                ]);
            }
            
            return view('quickbooks-success', [
                'message' => 'QuickBooks connected successfully!',
                'realm_id' => $realmId
            ]);
            
        } catch (\Exception $e) {
            Log::channel('quickbooks')->error('Exception during OAuth callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to complete OAuth flow',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check QuickBooks connection status
     */
    public function status()
    {
        $credential = Credential::where('name', 'quickbooks')->first();
        
        $status = [
            'connected' => false,
            'has_refresh_token' => false,
            'token_expires_at' => null,
            'realm_id' => env('QUICKBOOKS_REALM_ID'),
            'sandbox_mode' => env('QUICKBOOKS_SANDBOX', true),
        ];
        
        if ($credential) {
            $status['connected'] = !empty($credential->refresh_token);
            $status['has_refresh_token'] = !empty($credential->refresh_token);
            $status['token_expires_at'] = $credential->expires_at;
            $status['token_expired'] = $credential->expires_at ? now()->gt($credential->expires_at) : true;
        }
        
        return response()->json($status);
    }
}