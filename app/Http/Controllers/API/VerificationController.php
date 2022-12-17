<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Verified;



class VerificationController extends BaseController
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */


    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Show the email verification notice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
      public function show()
    {
        //
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verify(Request  $request)
    {
        $user = User::find($request->route('id'));
        if ($user->hasVerifiedEmail()) {
            $success['verified'] =  true;
            return $this->sendResponse($success, 'Given email is already verified.');
          }
      
          if ($user->markEmailAsVerified()) {
            event(new Verified($user));
          }
      
          $success['verified'] =  true;
          return $this->sendResponse($success, 'Verification complete.');
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resend(Request $request)
    {
        $user = User::where('email' , $request->email)->first();;
        if (!$user) {
            $error['verified'] =  false;
            return $this->sendError( 'Given email is not registered.', $error);
        }
        if ( $user->hasVerifiedEmail()) {
            $success['verified'] =  true;
            return $this->sendResponse($success, 'Given email is already verified.');
        }

         $user->sendEmailVerificationNotification();

        $success['verified'] =  false;
        return $this->sendResponse($success, 'Verification email sent.');
    }







}