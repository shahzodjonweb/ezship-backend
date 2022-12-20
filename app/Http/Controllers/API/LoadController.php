<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Load;
use App\Models\Location;
use App\Models\Category;

use Validator;
use Auth;
use App\Http\Resources\Load as LoadResource;
   
class LoadController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $loads =Load::where('user_id',Auth::user()->id)->where('status', '!=', 'initial')
        ->orderBy('created_at','DESC')->get();
        if($request['status']){
            $loads =$loads->where('status', $request['status']);
        }
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
        $validator = Validator::make($request->all(), [
            'status' => 'string',
            'phone' => 'string',
            'initial_price' => 'numeric | between:0,99999.99'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
     
        $load = new Load;
        $load -> user_id = Auth::user()->id;
        $load -> status = 'pending';
        $load -> type = $request->type;
        $load -> description = $request->description;
        $load -> phone = $request->phone;
        $load -> initial_price = $request->initial_price;
        $load -> save();

        $newTypes = $request->categories;
        foreach($newTypes as $type){
               $newType = new Category;
               $newType->load_id = $load->id;
               $newType->name = $type['name'];
               $newType->value = $type['value'];
               $newType->save();
        }
        $locations = $request->locations;
        foreach($locations as $location){
                $new_location = new Location;
                $new_location->load_id = $load->id;
                $new_location->address = $location['address'];
                $new_location->city = $location['city'];
                $new_location->state = $location['state'];
                $new_location->zip = $location['zip'];
                $new_location->date = $location['date'];
                $new_location->lat = $location['lat'];
                $new_location->lon = $location['lon'];
                $new_location->save();
        }

        $load -> save();
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
            'status' => 'string',
            'phone' => 'string',
            'initial_price' => 'numeric | between:0,99999.99'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $load->update($input);
        if($request->has('types')){
           $oldTypes = $load->types;
           $newTypes = $request->types;
           foreach($newTypes as $type){
              $isExist = false;
              foreach($oldTypes as $oldType){
                 if($type['name'] == $oldType->name){
                    $oldType->update($type);
                    $isExist = true;
                 }
              }
              if(!$isExist){
                 $newType = new Category;
                 $newType->load_id = $load->id;
                 $newType->name = $type['name'];
                 $newType->value = $type['value'];
                 $newType->save();
              }
           }
        }

        if($request->has('locations')){
            $old_locations = $load->locations;
            $new_locations = $request->locations;
                 foreach($new_locations as $key1=>$location){
                     $isExist = false;
                     foreach($old_locations as $key2=>$old_location){
                         if($key1 == $key2){
                             $old_location->update($location);
                             $isExist = true;
                         }
                     }
                     if(!$isExist){
                         $new_location = new Location;
                         $new_location->load_id = $load->id;
                         $new_location->address = $location['address'];
                         $new_location->city = $location['city'];
                         $new_location->state = $location['state'];
                         $new_location->zip = $location['zip'];
                         $new_location->date = $location['date'];
                         $new_location->save();
                     }
                 }
         }
   
        return $this->sendResponse(new LoadResource($load), 'Load updated successfully.');
    }
   public function handleCounterRate($id , Request $request){
        $load = Load::find($id);
        if($request['action'] == 'accept'){
            $load->initial_price = $load -> counter_price;
            $load->status = 'accepted';
        }else if($request['action'] == 'reject'){
            $load->status = 'rejected';
        }
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