<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
   
class PriceController extends BaseController
{

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getRates()
    {
        $pricing = [
            'car' => 2.5,
            'flatbed' => 2.75,
            'reefer' => 2.65,
            'stepdeck' => 2.65,
            'van' => 2.35,
            'poweronly' => 2.0,
            'move' => 1.5,
            'default' => 2.5
        ];
        return response()->json($pricing, 200);
    }

}