<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\API\QuickBooksController;
use Illuminate\Support\Facades\Log;
use Validator;
use App\Exceptions\ConfigurationException;
use App\Exceptions\AuthenticationException;
   
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
            // Check if Google OAuth is configured
            if (!config('services.google.client_id') || !config('services.google.client_secret')) {
                Log::error('Google OAuth not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env');
                throw new ConfigurationException('Google OAuth not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env', 400);
            }

            // check access token
            if ($request->has('access_key')) {
                try {
                    $googleUser = Socialite::driver('google')->userFromToken($request->access_key);
                } catch (\Exception $e) {
                    Log::error('Invalid Google access token: ' . $e->getMessage());
                    return $this->sendError('Invalid access token', ['error' => 'The provided Google access token is invalid'], 401);
                }
                
                $user = User::where('email', $googleUser->email)->first();
                if ($user) {
                    $success['token'] =  $user->createToken('MyApp')->accessToken; 
                    $success['name'] =  $user->name;
                    $success['email'] = $user->email;
                    return $this->sendResponse($success, 'User login successfully.');
                } else {
                    $input['name'] = $googleUser->name;
                    $input['email'] = $googleUser->email;
                    $input['avatar'] =  $googleUser->avatar;
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
                        
                        $success['token'] =  $newUser->createToken('MyApp')->accessToken;
                        $success['name'] =  $newUser->name;
                        $success['email'] = $newUser->email;
                        return $this->sendResponse($success, 'User register successfully.');
                    } catch (\Exception $e) {
                        Log::error('User creation failed: ' . $e->getMessage());
                        return $this->sendError('Registration failed', ['error' => 'Failed to create user account'], 500);
                    }
                }
            } else {
                return $this->sendError('Unauthorized', ['error' => 'Access token is required'], 401);
            }
        } catch (\Exception $e) {
            Log::error('Google login error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return $this->sendError('Server Error', ['error' => 'An unexpected error occurred during Google login'], 500);
        }
    }
}