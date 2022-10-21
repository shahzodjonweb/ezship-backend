<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Load;
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
            'initial_price' => 'numeric | between:0,99999.99',
            'pickup_address' => 'string',
            'pickup_date' => 'date',
            'delivery_address' => 'string',
            'delivery_date' => 'date',
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