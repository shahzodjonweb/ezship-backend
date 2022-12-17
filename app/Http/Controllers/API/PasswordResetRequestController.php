<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetRequestController extends BaseController {
  
    // send reset link to email
    public function sendPasswordResetEmail(Request $request){
        if(!$this->validEmail($request->email)) {
            // If email does not exist
            $error['success'] =  false;
            return $this->sendError( 'Given email is not registered.', $error);
        } else {
            // If email exists
            $this->sendMail($request->email);
            $success['success'] =  true;
            return  $this->sendResponse($success, 'Password reset link sent to your email!');       
        }
    }

    public function sendMail($email){
        $token = $this->generateToken($email);
        $user = User::where('email', $email)->first();
        $user -> sendPasswordResetNotification($token);
    }
    public function validEmail($email) {
       return !!User::where('email', $email)->first();
    }
    public function generateToken($email){
      $isOtherToken = DB::table('password_resets')->where('email', $email)->first();
      if($isOtherToken) {
        return $isOtherToken->token;
      }
      $token = Str::random(80);;
      $this->storeToken($token, $email);
      return $token;
    }
    public function storeToken($token, $email){
        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()            
        ]);
    }
    // reset password
    public function resetPassword(Request $request){
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        $passwordReset = DB::table('password_resets')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();
        if(!$passwordReset) {
            $error['success'] =  false;
            return $this->sendError( 'This password reset token is invalid.', $error);
        }
        $user = User::where('email', $passwordReset->email)->first();
        if(!$user) {
            $error['success'] =  false;
            return $this->sendError( 'We can\'t find a user with that e-mail address.', $error);
        }
        $user->password = bcrypt($request->password);
        $user->save();
        DB::table('password_resets')->where(['email'=> $user->email])->delete();
        $success['success'] =  true;
        return  $this->sendResponse($success, 'Password reset successfully!');
     }
}