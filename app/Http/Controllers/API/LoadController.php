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
        
        $load = new Load;
        $load -> user_id = Auth::user()->id;
        $load -> status = 'initial';
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

        if($request->has('categories')){
           $oldCategories = $load->categories;
           $newCategories = $request->categories;
                foreach($newCategories as $category){
                    $isExist = false;
                    foreach($oldCategories as $oldCategory){
                        if($category['name'] == $oldCategory['name']){
                            $oldCategory->update($category);
                            $isExist = true;
                        }
                    }
                    if(!$isExist){
                        $newCategory = new Category;
                        $newCategory->load_id = $load->id;
                        $newCategory->name = $category['name'];
                        $newCategory->value = $category['value'];
                        $newCategory->save();
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