<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\QuickBooksController;

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
         $user = Jwt::parseJWT($request->code);;
         $email = $user->email;
         $name = explode('@', $email)[0];
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