<?php

namespace Modules\Grievance\Grievance\Backend\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function sendResponse($status, $message , $data = null){
        return response()->json([
                'status'=>$status,
                'message'=> $message,
                'data' => $data
            ], $status);
    }

    public function sendErrorResponse($message){
        return response()->json([
            'status'=>500,
            'message'=> $message
        ], 500);
    }

}
