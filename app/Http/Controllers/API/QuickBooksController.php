<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Location;
use App\Models\Category;
use App\Models\Credential;
use Illuminate\Support\Facades\Http;

use Validator;
use Auth;
   
class QuickBooksController extends BaseController
{

    protected  $base = '';
    protected  $auth_base = '';

    function __construct() {
        $this->base = env('QUICK_BOOKS_BASE');
        $this->auth_base = env('QUICK_BOOKS_AUTH_BASE');
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function createCustomer( Request $request)
    {
        $credentials = Credential::where('name', 'quickbooks')->first();
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic QUJHTlNIMWJNU3ZNdGF3enZwYWJQajB3N0dYZEh1ZE5vd21DVUdpVFJqT0x2clZHcnA6d1drUnJ2T0dnRjM5ZUdod2UyVUhWbXU0TWNVSmE3dEVNSm1DYnoxSw==',
            'Accept' => 'application/json',
            "Content-Type" => "application/x-www-form-urlencoded"
        ])->post($this->auth_base.'/oauth2/v1/tokens/bearer', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credentials->refresh_token,
        ]);

       // return $response['access_token'];
        return $response;
    }

}