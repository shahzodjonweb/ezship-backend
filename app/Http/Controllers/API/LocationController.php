<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Location;
use App\Models\Category;

use Validator;
use Auth;
use App\Http\Resources\Location as LocationResource;
   
class LocationController extends BaseController
{

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update( $id , Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'address' => 'required',
            'city' => 'required',
            'lat' => 'required',
            'lon' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'date' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $location = Auth::user()->stops->where('id', $id)->first()->location;
        
        if (is_null($location)) {
            return $this->sendError('Location not found.');
        }
  
        $location->update($input);

        return $this->sendResponse(new LocationResource($location), 'Location updated successfully.');
    }

}