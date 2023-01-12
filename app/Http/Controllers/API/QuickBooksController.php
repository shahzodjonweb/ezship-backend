<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Load;
use App\Models\Payment;
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
    public function createCustomer($request)
    {
      error_log(json_encode($request));
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
            error_log(json_encode($response));
        $user = User::find($request->id);
        $user->quickbooks_id = $response['Customer']['Id'];
        $user->save();
        return $response;
    }
    public function createInvoice( $request)
    {
        error_log(json_encode($request));
        $load = json_decode(json_encode($request));
        //get last 8 chars of id
        $current_load = Load::find($load->id);
        $user = $current_load->user;
        $customer_ref = $user->quickbooks_id;
        error_log(json_encode($user));

        $reference = substr($load->id, -8);
        $shipper = $load ->locations[0];
        $receiver = $load ->locations[1];
        $pick_up = $shipper->address.', '.$shipper->city.', '.$shipper->state;
        $drop_off = $receiver->address.', '.$receiver->city.', '.$receiver->state;
        $quickbookAuth = $this->refreshToken();
        $response = Http::withHeaders([
               'Authorization' => 'Bearer '.$quickbookAuth['access_token'],
               'Accept' => 'application/json',
               "Content-Type" => "application/json"
           ])->post($this->base.'/v3/company/'.$this->realm_id.'/invoice', [
            "Line" => [
                [
                    "Description" => 'Ref: #'.$reference.', From: '.$pick_up.', To: '.$drop_off,
                    "DetailType" =>  "SalesItemLineDetail", 
                  "Amount"=> $load->initial_price, 
                  "SalesItemLineDetail"=> [
                    "ItemRef"=> [
                      "name"=> "Service", 
                      "value"=> "1"
                    ]
                  ]
                ]
                    ],
              "CustomerRef" => [
                "value" => $customer_ref
              ],
              "AllowIPNPayment"=> true,
              "AllowOnlinePayment"=>true,
              'AllowOnlineACHPayment'=> true,
              'AllowOnlineCreditCardPayment'=> true
           ]);
           $payment = new Payment();
           $payment->load_id = $load->id;
           $payment->invoice_id = $response['Invoice']['Id'];
           $payment->save();

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
    public function updateInvoice(){
        $quickbookAuth = $this->refreshToken();
        $response = Http::withHeaders([
               'Authorization ' => 'Bearer '.$quickbookAuth['access_token'],
                'Accept' => 'application/json',
                "Content-Type" => "application/json"
            ])->post($this->base.'/v3/company/'.$this->realm_id.'/invoice/1', [
                'Line' => [
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
            return $response;
    }
    public function updateCompany(){
        $quickbookAuth = $this->refreshToken();
        $response = Http::withHeaders([
               'Authorization ' => 'Bearer '.$quickbookAuth['access_token'],
                'Accept' => 'application/json',
                "Content-Type" => "application/json"
            ])->post($this->base.'/v3/company/'.$this->realm_id.'/invoice/1', [
                'Line' => [
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
        error_log(json_encode($response));
        $credentials->access_token = $response['access_token'];
        $credentials->refresh_token = $response['refresh_token'];
        $credentials->update();
        return $response;
    }

}