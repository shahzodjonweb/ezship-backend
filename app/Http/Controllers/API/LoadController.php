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
            'status' => 'required'
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
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'type' => 'required',
            'status' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $load->type = $input['type'];
        $load->status = $input['status'];
        $load->save();
   
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