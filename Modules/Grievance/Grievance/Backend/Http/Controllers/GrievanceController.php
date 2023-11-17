<?php

namespace Modules\Grievance\Grievance\Backend\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Role;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Grievance\Grievance\Backend\Entities\GrIssueType;
use Modules\Grievance\Grievance\Backend\Service\GRApplicationService;
use Modules\Grievance\Grievance\Backend\Http\Requests\GRAplicationCreateRequest;
use Modules\Grievance\Grievance\Backend\Service\GRPartialService;
use PhpParser\Node\Expr\FuncCall;

class GrievanceController extends Controller
{
    public $gr_application_service;
    public $gr_partial_service;

    public function __construct( GRApplicationService $gr_application_service, GRPartialService $gr_partial_service )
    {
        $this->gr_application_service = $gr_application_service;
        $this->gr_partial_service = $gr_partial_service;
    }

    public function getEmployeeList(Request $request){        
        $employeeList = $this->gr_partial_service->getEmployeeList($request);   
        if($employeeList->isEmpty()){
            return $this->sendResponse(200, "No Employee Available");
        }
        else
        {
            return $this->sendResponse(200, "success", $employeeList);
        }
    }

    public function getDepartments(Request $request){
        $departments = $this->gr_partial_service->getDepartments($request);
        if($departments->isEmpty()){
            return $this->sendResponse(200, "No Departments Available"); 
        }
        else{
            return $this->sendResponse(200, "success", $departments);
        }  
    }

    public function getDesignation(Request $request){
        $designation = $this->gr_partial_service->getDesignation($request);
        if($designation->isEmpty()){
            return $this->sendResponse(200, "No Designation Available");
        }else{
            return $this->sendResponse(200, "success", $designation);
        }
    }
    

    public function getIssueTypes(Request $request){
        $activeIssueTypes = $this->gr_partial_service->getIssueTypes($request);
        if($activeIssueTypes->isEmpty()){
            return $this->sendResponse(200, "No Grievance Issue Types Available");
        }
        else
        {
            return $this->sendResponse(200, "success", $activeIssueTypes);
        }
    }

    public function getLoginUserDetail(Request $request){
        $user_id = Auth::user()->id;
        $user =  Employee::where('id', $user_id)->with('department')->first();
        //$user =  Auth::user();
        if(!$user){
            return $this->sendResponse(200, "No Grievance Issue Types Available");
        }
        else
        {
            return $this->sendResponse(200, "success", $user);
        }
    }

    public function store(GRAplicationCreateRequest $request){        
        try{
            $application = $this->gr_application_service->createApplication($request);
            return $this->sendResponse(200, "Grievance Application Submitted Successfully", $application);
        } catch (Exception $ex){
            return $this->sendErrorResponse($ex->getMessage());
        }
        
    }

    public function getApplications(Request $request){
        try{
            $applications = $this->gr_application_service->getApplications($request);
            if(count($applications) > 0)
                return $this->sendResponse(200, "List of Grievance Applications", $applications);
            else
            return $this->sendResponse(200, "No Grievance Applications Available");
        } catch (Exception $ex){
            return $this->sendErrorResponse($ex->getMessage());
        }
    }

    public function getApplicationById($application_id){
        try{
            $application = $this->gr_application_service->getApplicationById($application_id);
            if($application)
                return $this->sendResponse(200, "Grievance Applications", $application);
            else
            return $this->sendResponse(200, "No Grievance Application Available");
        } catch (Exception $ex){
            return $this->sendErrorResponse($ex->getMessage());
        }
    }

    public function updateApplication(Request $request, $application_id)
    {
        try{
            $application = $this->gr_application_service->updateApplication($request, $application_id);
            if($application)
                return $this->sendResponse(200, "Grievance Application Updated Successfully", $application);
            else
                return $this->sendResponse(200, "Issue Grievance Application while updating");
        } catch (Exception $ex){
            return $this->sendErrorResponse($ex->getMessage());
        }
    }

    public function uploadApplicationEvidence(Request $request, $application_id){
        try{
            $application_evidence = $this->gr_application_service->uploadApplicationEvidence($request, $application_id);
            if($application_evidence)
                return $this->sendResponse(200, "Grievance Application Evidence upload", $application_evidence);
            else
            return $this->sendResponse(200, "Issue while Grievance Application Evidence uploading");
        } catch (Exception $ex){
            return $this->sendErrorResponse($ex->getMessage());
        }
    }

    public function deleteApplicationEvidence(Request $request, $evidence_id){
        try{
            $application_evidence = $this->gr_application_service->deleteApplicationEvidence($evidence_id);
            if($application_evidence)
                return $this->sendResponse(200, "Grievance Application Evidence deleted", $application_evidence);
            else
            return $this->sendResponse(200, "Issue While Grievance Application Evidence");
        } catch (Exception $ex){
            return $this->sendErrorResponse($ex->getMessage());
        }
    }
    
}
