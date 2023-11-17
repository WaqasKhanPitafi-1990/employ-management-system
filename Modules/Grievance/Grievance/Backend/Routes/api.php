<?php

use App\Http\Controllers\CommonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Grievance\Grievance\Backend\Http\Controllers\GrievanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/grievance', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix'=> '/grievance', 'middleware' => ['auth:api' ,'cors']] , function(){

    //Partials
    Route::get('get_departments', [GrievanceController::class, 'getDepartments']);
    Route::get('get_designations', [GrievanceController::class, 'getDesignation']);
    Route::get('get_employees', [GrievanceController::class, 'getEmployeeList']);
    Route::get('get_issue_types', [GrievanceController::class, 'getIssueTypes']);
    Route::get('get_login_user_detail', [GrievanceController::class, 'getLoginUserDetail']);

    //Grievance Applications
    Route::group(['prefix'=> '/applications'] , function(){
        Route::post('/', [GrievanceController::class, 'store']);
        Route::get('/', [GrievanceController::class, 'getApplications']);
        Route::post('/{application_id}', [GrievanceController::class, 'updateApplication']);
        Route::get('/{application_id}', [GrievanceController::class, 'getApplicationById']);
        Route::post('/upload-evidence/{application_id}', [GrievanceController::class, 'uploadApplicationEvidence']);
        Route::delete('/delete-evidence/{evidence_id}', [GrievanceController::class, 'deleteApplicationEvidence']);
    });
    
});
Route::get('/', 'GrievanceController@index');
