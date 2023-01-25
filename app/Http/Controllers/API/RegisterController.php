<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Http\Resources\User as UserResource;
use App\Http\Controllers\API\QuickBooksController;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function account(){
        $user = Auth::user();
        return $this->sendResponse( new UserResource($user), 'User retrieved successfully.');
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password1' => 'required',
            'password2' => 'required|same:password1',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $input = $request->all();
        $input['password'] = bcrypt($input['password1']);
        $user = User::create($input);
        $result = (new QuickBooksController)->createCustomer($user);
        event(new Registered($user));
        $success['token'] =  $user->createToken('MyApp')->accessToken;
        $success['name'] =  $user->name;
        $success['email'] =  $user->email;
   
        return $this->sendResponse($success, 'User register successfully.');
    }
   
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')-> accessToken; 
            $success['name'] =  $user->name;
   
            return $this->sendResponse($success, 'User login successfully.');
        } 
        else{ 
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        } 
    }
    public function delete(Request $request)
    {
        $user = Auth::user();
        // not delete if user have incomplete orders
        if($user->loads->whereNotIn('status', ['pending', 'completed', 'invoiced' , 'rejected'])->count() > 0){
            return $this->sendError('Incomplete Orders.', ['error'=>'You can not delete your account while you have incomplete orders.']);
        }
        // delete company if user have company
        if($user->company){
            $user->company->delete();
        }
        $user->delete();
        return $this->sendResponse( new UserResource($user), 'User deleted successfully.');
    }
}