<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Load;
use App\Models\Payment;
use App\Models\Credential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
   
class QuickBooksController extends BaseController
{
    protected string $base = '';
    protected string $authBase = '';
    protected string $basicToken = '';
    protected string $realmId = '';
    protected string $minorVersion = '75'; // Latest API minor version
    protected bool $isSandbox = true;

    public function __construct() 
    {
        $this->isSandbox = env('QUICKBOOKS_SANDBOX', true);
        $this->base = $this->isSandbox 
            ? env('QUICKBOOKS_SANDBOX_BASE', 'https://sandbox-quickbooks.api.intuit.com')
            : env('QUICKBOOKS_PRODUCTION_BASE', 'https://quickbooks.api.intuit.com');
        $this->authBase = env('QUICKBOOKS_AUTH_BASE', 'https://oauth.platform.intuit.com');
        $this->basicToken = env('QUICKBOOKS_BASIC_TOKEN', '');
        $this->realmId = env('QUICKBOOKS_REALM_ID', '');
        $this->minorVersion = env('QUICKBOOKS_MINOR_VERSION', '75');
    }

    /**
     * Get common headers for QuickBooks API requests
     */
    private function getApiHeaders(string $accessToken): array
    {
        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Create a customer in QuickBooks
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function createCustomer($request): array
    {
        try {
            Log::info('Creating QuickBooks customer', ['request' => $request]);
            
            $quickbooksAuth = $this->refreshToken();
            
            // Check if QuickBooks is configured
            if (isset($quickbooksAuth['error'])) {
                Log::warning('QuickBooks not configured for customer creation');
                return ['status' => 'skipped', 'message' => 'QuickBooks integration not configured'];
            }
            
            $url = "{$this->base}/v3/company/{$this->realmId}/customer?minorversion={$this->minorVersion}";
            
            $response = Http::withHeaders($this->getApiHeaders($quickbooksAuth['access_token']))
                ->timeout(30)
                ->retry(3, 100)
                ->post($url, [
                    'DisplayName' => $request->name . ' ' . date('Y-m-d H:i'),
                    'PrimaryEmailAddr' => [
                        'Address' => $request->email
                    ],
                ]);
            
            if (!$response->successful()) {
                Log::error('QuickBooks customer creation failed', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return [
                    'status' => 'error',
                    'message' => 'Failed to create customer in QuickBooks',
                    'error' => $response->json()
                ];
            }
            
            $responseData = $response->json();
            Log::info('QuickBooks customer created successfully', ['response' => $responseData]);
            
            // Update user with QuickBooks ID
            if (isset($responseData['Customer']['Id'])) {
                $user = User::find($request->id);
                if ($user) {
                    $user->quickbooks_id = $responseData['Customer']['Id'];
                    $user->save();
                }
            }
            
            return $responseData;
            
        } catch (Exception $e) {
            Log::error('Exception in createCustomer', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => 'An error occurred while creating customer',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create an invoice in QuickBooks
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function createInvoice($request): array
    {
        try {
            Log::info('Creating QuickBooks invoice', ['request' => $request]);
            
            $load = json_decode(json_encode($request));
            $currentLoad = Load::find($load->id);
            
            if (!$currentLoad) {
                return [
                    'status' => 'error',
                    'message' => 'Load not found'
                ];
            }
            
            $user = $currentLoad->user;
            
            if (!$user || !$user->quickbooks_id) {
                return [
                    'status' => 'error',
                    'message' => 'User not found or not linked to QuickBooks'
                ];
            }
            
            $customerRef = $user->quickbooks_id;
            $reference = substr($load->id, -8);
            $shipper = $load->locations[0] ?? null;
            $receiver = $load->locations[1] ?? null;
            
            if (!$shipper || !$receiver) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid load locations'
                ];
            }
            
            $pickUp = $shipper->address . ', ' . $shipper->city . ', ' . $shipper->state;
            $dropOff = $receiver->address . ', ' . $receiver->city . ', ' . $receiver->state;
            
            $quickbooksAuth = $this->refreshToken();
            
            // Check if QuickBooks is configured
            if (isset($quickbooksAuth['error'])) {
                Log::warning('QuickBooks not configured for invoice creation');
                return ['status' => 'skipped', 'message' => 'QuickBooks integration not configured'];
            }
            
            $url = "{$this->base}/v3/company/{$this->realmId}/invoice?minorversion={$this->minorVersion}";
            
            $response = Http::withHeaders($this->getApiHeaders($quickbooksAuth['access_token']))
                ->timeout(30)
                ->retry(3, 100)
                ->post($url, [
                    'Line' => [
                        [
                            'Description' => 'Ref: #' . $reference . ', From: ' . $pickUp . ', To: ' . $dropOff,
                            'DetailType' => 'SalesItemLineDetail',
                            'Amount' => $load->initial_price,
                            'SalesItemLineDetail' => [
                                'ItemRef' => [
                                    'name' => 'Service',
                                    'value' => '1'
                                ]
                            ]
                        ]
                    ],
                    'CustomerRef' => [
                        'value' => $customerRef
                    ],
                    'AllowIPNPayment' => true,
                    'AllowOnlinePayment' => true,
                    'AllowOnlineACHPayment' => true,
                    'AllowOnlineCreditCardPayment' => true
                ]);
            
            if (!$response->successful()) {
                Log::error('QuickBooks invoice creation failed', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return [
                    'status' => 'error',
                    'message' => 'Failed to create invoice in QuickBooks',
                    'error' => $response->json()
                ];
            }
            
            $responseData = $response->json();
            Log::info('QuickBooks invoice created successfully', ['response' => $responseData]);
            
            // Save payment record
            if (isset($responseData['Invoice']['Id'])) {
                $payment = new Payment();
                $payment->load_id = $load->id;
                $payment->invoice_id = $responseData['Invoice']['Id'];
                $payment->save();
                
                // Send the invoice
                return $this->sendInvoice($responseData['Invoice']['Id'], $user->email);
            }
            
            return $responseData;
            
        } catch (Exception $e) {
            Log::error('Exception in createInvoice', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => 'An error occurred while creating invoice',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send an invoice via email
     *
     * @param string $invoiceId
     * @param string|null $emailAddress
     * @return array
     */
    public function sendInvoice(string $invoiceId, ?string $emailAddress = null): array
    {
        try {
            $quickbooksAuth = $this->refreshToken();
            
            if (isset($quickbooksAuth['error'])) {
                return ['status' => 'skipped', 'message' => 'QuickBooks integration not configured'];
            }
            
            // Use provided email or fall back to configured default
            $sendToEmail = $emailAddress ?: env('QUICKBOOKS_DEFAULT_EMAIL', 'noreply@example.com');
            
            $url = "{$this->base}/v3/company/{$this->realmId}/invoice/{$invoiceId}/send?sendTo={$sendToEmail}&minorversion={$this->minorVersion}";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $quickbooksAuth['access_token'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/octet-stream'
            ])
                ->timeout(30)
                ->retry(3, 100)
                ->post($url);
            
            if (!$response->successful()) {
                Log::error('Failed to send QuickBooks invoice', [
                    'invoice_id' => $invoiceId,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return [
                    'status' => 'error',
                    'message' => 'Failed to send invoice',
                    'error' => $response->json()
                ];
            }
            
            Log::info('Invoice sent successfully', [
                'invoice_id' => $invoiceId,
                'sent_to' => $sendToEmail
            ]);
            
            return $response->json() ?: ['status' => 'success', 'message' => 'Invoice sent successfully'];
            
        } catch (Exception $e) {
            Log::error('Exception in sendInvoice', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => 'An error occurred while sending invoice',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an invoice in QuickBooks
     *
     * @param Request $request
     * @param string $invoiceId
     * @return array
     */
    public function updateInvoice(Request $request, string $invoiceId): array
    {
        try {
            $quickbooksAuth = $this->refreshToken();
            
            if (isset($quickbooksAuth['error'])) {
                return ['status' => 'skipped', 'message' => 'QuickBooks integration not configured'];
            }
            
            // First, get the current invoice to get SyncToken
            $getUrl = "{$this->base}/v3/company/{$this->realmId}/invoice/{$invoiceId}?minorversion={$this->minorVersion}";
            
            $getResponse = Http::withHeaders($this->getApiHeaders($quickbooksAuth['access_token']))
                ->timeout(30)
                ->get($getUrl);
            
            if (!$getResponse->successful()) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to retrieve invoice for update',
                    'error' => $getResponse->json()
                ];
            }
            
            $currentInvoice = $getResponse->json()['Invoice'];
            
            // Update the invoice with new data
            $updateUrl = "{$this->base}/v3/company/{$this->realmId}/invoice?minorversion={$this->minorVersion}";
            
            $updateData = array_merge($currentInvoice, [
                'SyncToken' => $currentInvoice['SyncToken'],
                // Add your update fields here based on request
            ]);
            
            $response = Http::withHeaders($this->getApiHeaders($quickbooksAuth['access_token']))
                ->timeout(30)
                ->retry(3, 100)
                ->post($updateUrl, $updateData);
            
            if (!$response->successful()) {
                Log::error('Failed to update QuickBooks invoice', [
                    'invoice_id' => $invoiceId,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return [
                    'status' => 'error',
                    'message' => 'Failed to update invoice',
                    'error' => $response->json()
                ];
            }
            
            Log::info('Invoice updated successfully', ['invoice_id' => $invoiceId]);
            
            return $response->json();
            
        } catch (Exception $e) {
            Log::error('Exception in updateInvoice', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => 'An error occurred while updating invoice',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Refresh the QuickBooks OAuth token
     *
     * @return array
     */
    public function refreshToken(): array
    {
        try {
            $credentials = Credential::where('name', 'quickbooks')->first();
            
            // Check if QuickBooks credentials exist
            if (!$credentials || !$credentials->refresh_token) {
                Log::warning('QuickBooks credentials not found or refresh token missing');
                return ['error' => 'QuickBooks not configured'];
            }
            
            // Check if basic token is configured
            if (empty($this->basicToken)) {
                Log::error('QuickBooks basic token not configured');
                return ['error' => 'QuickBooks basic token not configured'];
            }
            
            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => 'Basic ' . $this->basicToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ])
                ->timeout(30)
                ->retry(3, 100)
                ->post($this->authBase . '/oauth2/v1/tokens/bearer', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $credentials->refresh_token,
                ]);
            
            if (!$response->successful()) {
                Log::error('Failed to refresh QuickBooks token', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return ['error' => 'Failed to refresh token'];
            }
            
            $responseData = $response->json();
            
            // Update credentials with new tokens
            if (isset($responseData['access_token']) && isset($responseData['refresh_token'])) {
                $credentials->access_token = $responseData['access_token'];
                $credentials->refresh_token = $responseData['refresh_token'];
                $credentials->save();
                
                Log::info('QuickBooks token refreshed successfully');
            }
            
            return $responseData;
            
        } catch (Exception $e) {
            Log::error('Exception in refreshToken', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => 'An error occurred while refreshing token'];
        }
    }

    /**
     * Update a customer in QuickBooks
     *
     * @param Request $request
     * @param string $customerId
     * @return array
     */
    public function updateCustomer(Request $request, string $customerId): array
    {
        try {
            $quickbooksAuth = $this->refreshToken();
            
            if (isset($quickbooksAuth['error'])) {
                return ['status' => 'skipped', 'message' => 'QuickBooks integration not configured'];
            }
            
            // Implementation for updating customer
            // Similar pattern to updateInvoice
            
            return ['status' => 'success', 'message' => 'Customer update not yet implemented'];
            
        } catch (Exception $e) {
            Log::error('Exception in updateCustomer', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => 'An error occurred while updating customer',
                'error' => $e->getMessage()
            ];
        }
    }
}