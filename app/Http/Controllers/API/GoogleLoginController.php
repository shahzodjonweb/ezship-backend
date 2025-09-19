<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\QuickBooksController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Validator;
   
class GoogleLoginController extends BaseController
{
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        try {
            // Check if access token is provided
            if (!$request->has('access_key')) {
                return $this->sendError('Unauthorized', ['error' => 'Access token is required'], 401);
            }

            // Validate the Google access token directly with Google's API
            try {
                $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                    'access_token' => $request->access_key
                ]);

                if (!$response->successful()) {
                    Log::error('Invalid Google access token: ' . $response->body());
                    return $this->sendError('Invalid access token', ['error' => 'The provided Google access token is invalid'], 401);
                }

                $googleData = $response->json();
                
                // Verify the token is valid and has email scope
                if (!isset($googleData['email']) || !isset($googleData['email_verified']) || $googleData['email_verified'] !== 'true') {
                    return $this->sendError('Invalid token', ['error' => 'Token must have verified email'], 401);
                }

                // Get additional user info if needed
                $userInfoResponse = Http::withToken($request->access_key)
                    ->get('https://www.googleapis.com/oauth2/v2/userinfo');
                
                if (!$userInfoResponse->successful()) {
                    Log::error('Failed to get user info from Google: ' . $userInfoResponse->body());
                    return $this->sendError('Failed to get user info', ['error' => 'Could not retrieve user information from Google'], 500);
                }

                $googleUser = $userInfoResponse->json();
                
            } catch (\Exception $e) {
                Log::error('Google API error: ' . $e->getMessage());
                return $this->sendError('Authentication failed', ['error' => 'Failed to validate with Google'], 500);
            }
            
            // Check if user exists
            $user = User::where('email', $googleUser['email'])->first();
            if ($user) {
                $success['token'] = $user->createToken('MyApp')->accessToken;
                $success['name'] = $user->name;
                $success['email'] = $user->email;
                return $this->sendResponse($success, 'User login successfully.');
            } else {
                // Create new user
                $input['name'] = $googleUser['name'] ?? $googleUser['email'];
                $input['email'] = $googleUser['email'];
                $input['avatar'] = $googleUser['picture'] ?? null;
                $input['email_verified_at'] = now();
                $input['password'] = bcrypt('google_oauth_user_' . time());
                
                try {
                    $newUser = User::create($input);
                    
                    // Try to create QuickBooks customer (but don't fail if it doesn't work)
                    try {
                        $result = (new QuickBooksController)->createCustomer($newUser);
                    } catch (\Exception $qbError) {
                        Log::warning('QuickBooks customer creation failed: ' . $qbError->getMessage());
                    }
                    
                    $success['token'] = $newUser->createToken('MyApp')->accessToken;
                    $success['name'] = $newUser->name;
                    $success['email'] = $newUser->email;
                    return $this->sendResponse($success, 'User register successfully.');
                } catch (\Exception $e) {
                    Log::error('User creation failed: ' . $e->getMessage());
                    return $this->sendError('Registration failed', ['error' => 'Failed to create user account'], 500);
                }
            }
        } catch (\Exception $e) {
            Log::error('Google login error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            
            
            return $this->sendError('Server Error', ['error' => 'An unexpected error occurred during Google login'], 500);
        }
    }
}