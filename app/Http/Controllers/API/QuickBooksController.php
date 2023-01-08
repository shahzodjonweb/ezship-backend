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
    protected  $basic_token = '';
    protected  $realm_id = '';

    function __construct() {
        $this->base = env('QUICK_BOOKS_BASE');
        $this->auth_base = env('QUICK_BOOKS_AUTH_BASE');
        $this->basic_token = env('QUiCK_BOOKS_BASIC_TOKEN');
        $this->realm_id = env('QUICK_BOOKS_REALM_ID');
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
       $quickbookAuth = $this->refreshToken();
         $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$quickbookAuth['access_token'],
                'Accept' => 'application/json',
                "Content-Type" => "application/json"
            ])->post($this->base.'/v3/company/'.$this->realm_id.'/customer', [
                'DisplayName' => $request->name,
                'PrimaryEmailAddr' => [
                    'Address' => $request->email
                ],
            ]);
        return $response;
    }
    public function createInvoice( Request $request)
    {
        $quickbookAuth = $this->refreshToken();
        $response = Http::withHeaders([
               'Authorization' => 'Bearer '.$quickbookAuth['access_token'],
               'Accept' => 'application/json',
               "Content-Type" => "application/json"
           ])->post($this->base.'/v3/company/'.$this->realm_id.'/invoice', [
            "Line" => [
                [
                    "Description" => "Rock Fountain", 
                    "DetailType" =>  "SalesItemLineDetail", 
                  "Amount"=> 100.0, 
                  "SalesItemLineDetail"=> [
                    "ItemRef"=> [
                      "name"=> "Services", 
                      "value"=> "1"
                    ]
                  ]
                ]
                    ],
              "CustomerRef" => [
                "value" => "1"
              ],
           ]);
     $sendInvoice = $this->sendInvoice($response['Invoice']['Id']);   
       return $sendInvoice;
    }
    
    public function sendInvoice($invoiceId){
        $quickbookAuth = $this->refreshToken();
        $response = Http::withHeaders([
               'Authorization' => 'Bearer '.$quickbookAuth['access_token'],
                'Accept' => 'application/json',
                "Content-Type" => "application/octet-stream"
            ])->post($this->base.'/v3/company/'.$this->realm_id.'/invoice/'.$invoiceId.'/send?sendTo=shaxaprogrammer@gmail.com', [
                'sendTo' => 'shaxaprogrammer@gmail.com'
            ]);
                    return $response;
                }
    public function refreshToken(){
        $credentials = Credential::where('name', 'quickbooks')->first();
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic '.$this->basic_token,
            'Accept' => 'application/json',
            "Content-Type" => "application/x-www-form-urlencoded"
        ])->post($this->auth_base.'/oauth2/v1/tokens/bearer', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credentials->refresh_token,
        ]);

        $credentials->access_token = $response['access_token'];
        $credentials->refresh_token = $response['refresh_token'];
        $credentials->update();
        return $response;
    }

}