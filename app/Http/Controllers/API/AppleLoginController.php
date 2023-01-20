<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use League\OAuth2\Client\Provider\Apple as Apple;
use App\Models\User;
use Firebase\JWT\JWT as JWT;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\API\QuickBooksController;
use FaganChalabizada\AppleTokenAuth\Classes\AppleAuth;

use Validator;
   
class AppleLoginController extends BaseController
{
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
          // check access token
        if ($request->has('code')) {
       
        $data = [
            "client_id" => 'register.ezship.app',
            "team_id"   => '442HGK87WA',
            "key_id"    => 'LS6M598Q77',
            "key"       => __DIR__ .'\AuthKey_LS6M598Q77.p8', //path where is your p8 key example if your key is in storage
            "code"      => $request->code //code sended by your front end guy
         ];
         $appleAuth = new AppleAuth($data);
         $user = $appleAuth->getUserData();
         $email = $user['user']->email;
         $name = explode('@', $email)[0];
         error_log($email .' '. $name);
         $user = User::where('email', $email)->first();
         if ($user) {
            $success['token'] =  $user->createToken('MyApp')-> accessToken; 
            $success['name'] =  $user->name;
            return $this->sendResponse($success, 'User login successfully.');
        } else {
            $input['name'] = $name;
            $input['email'] = $email;
            $input['email_verified_at'] = now();
            $input['password'] = bcrypt('you_cannot_find_this_password');
            $newUser = User::create($input);
            $result = (new QuickBooksController)->createCustomer($newUser);
            $success['token'] =  $newUser->createToken('MyApp')->accessToken;
            $success['name'] =  $newUser->name;
            return $this->sendResponse($success, 'User register successfully.');    
        }
        } else {
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        }
    }
}