<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
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
        // check access token
        if ($request->has('access_key')) {
            $googleUser = Socialite::driver('google')->userFromToken($request->access_key);
            $user = User::where('email', $googleUser->email)->first();
            if ($user) {
                $success['token'] =  $user->createToken('MyApp')-> accessToken; 
                $success['name'] =  $user->name;
                return $this->sendResponse($success, 'User login successfully.');
            } else {
                $input['name'] = $googleUser->name;
                $input['email'] = $googleUser->email;
                $success['avatar'] =  $googleUser->picture;
                $input['password'] = bcrypt('you_cannot_find_this_password');
                $newUser = User::create($input);
                $success['token'] =  $newUser->createToken('MyApp')->accessToken;
                $success['name'] =  $newUser->name;
                return $this->sendResponse($success, 'User register successfully.');    
            }
        } else {
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        }
    }
}