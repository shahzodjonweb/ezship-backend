<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Load;
use Validator;
use App\Http\Resources\Load as LoadResource;
   
class LoadController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $loads = Load::all();
    
        return $this->sendResponse(LoadResource::collection($loads), 'Loads retrieved successfully.');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'type' => 'required',
            'status' => 'string | required',
            'description' => 'nullable',
            'phone' => 'string | required',
            'initial_price' => 'numeric | between:0,99999.99',
            'pickup_address' => 'string | required',
            'pickup_date' => 'date | required',
            'delivery_address' => 'string | required',
            'delivery_date' => 'date | required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $load = Load::create($input);
   
        return $this->sendResponse(new LoadResource($load), 'Load created successfully.');
    } 
   
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $load = Load::find($id);
  
        if (is_null($load)) {
            return $this->sendError('Load not found.');
        }
   
        return $this->sendResponse(new LoadResource($load), 'Load retrieved successfully.');
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Load $load)
    {
        $validator = Validator::make($input, [
            'type' => 'required',
            'status' => 'string | required',
            'description' => 'nullable',
            'phone' => 'string | required',
            'initial_price' => 'numeric | between:0,99999.99',
            'pickup_address' => 'string | required',
            'pickup_date' => 'date | required',
            'delivery_address' => 'string | required',
            'delivery_date' => 'date | required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $load = User::where("user_id", $id)->update([
            "type" => $request->type,
            "status" => $request->status,
            "description" => $request->description,
            "phone" => $request->phone,
            "initial_price" => $request->initial_price,
            "pickup_address" => $request->pickup_address,
            "pickup_date" => $request->pickup_date,
            "delivery_address" => $request->delivery_address,
            "delivery_date" => $request->delivery_date,
        ]);
   
        return $this->sendResponse(new LoadResource($load), 'Load updated successfully.');
    }
   
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Load $load)
    {
        $load->delete();
        return $this->sendResponse([], 'Load deleted successfully.');
    }
}