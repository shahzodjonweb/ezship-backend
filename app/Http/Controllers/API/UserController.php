<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Company;

use Validator;
use Auth;
use App\Http\Resources\User as UserResource;
   
class UserController extends BaseController
{

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update( Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required',
            'email' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $user = Auth::user();
        $user->update($input);

        return $this->sendResponse(new UserResource($user) , 'Location updated successfully.');
    }

 

    public function updateCompany(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            'address' => 'required',
            'business_phone' => 'required',
            'business_email' => 'required',
            'representative_name' => 'required',
            'representative_position' => 'required',
            'sales_person_name' => 'required',
            'sales_phone' => 'required',
            'sales_email' => 'required',
            'billing_address' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $user = Auth::user();
        $company = $user->company;
        if($company){
            $company->update($input);
        }else{
            $company = new Company;
            $company->fill($input);
            $company->user_id = $user->id;
            $company->save();
            $user->has_company = true;
            $user->save();
        }
        return $this->sendResponse(new UserResource($user) , 'Company updated successfully.');
    }

}