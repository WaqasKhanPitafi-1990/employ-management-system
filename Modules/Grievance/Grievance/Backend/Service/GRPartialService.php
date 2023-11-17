<?php
namespace Modules\Grievance\Grievance\Backend\Service;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Grievance\Grievance\Backend\Entities\GrIssueType;

class GRPartialService
{
    public function getEmployeeList($request){
        $client_id = Auth::user()->cid;
        $role_ids = Role::select('id')->where('cid',$client_id)->whereIn('role',['Employee','LM'])->get()->pluck('id');
        $employeeList = DB::table('proll_employee')->select('proll_employee.id as id', 'proll_employee.name_salute as name_salute', 'proll_employee.name as name', 'proll_employee.dept_id as designation_id', 'proll_employee.department_id as department_id', 'roles.role as role')
                                ->join('user_roles', 'user_roles.user_id', 'proll_employee.id')
                                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                                ->whereIn('user_roles.role_id' ,$role_ids )
                                ->where('proll_employee.cid',$client_id);
        if(isset($request->search_name)){
            $employeeList = $employeeList->where('proll_employee.name', 'like' , $request->search_name."%");
        }
        $employeeList = $employeeList->get();
        
        return $employeeList;
    }

    public function getDepartments($request){
        $client_id = Auth::user()->cid;
        $departments = Department::where('type','=','department')->where('status','=',1)->where('cid', $client_id);

        if(isset($request->search_name)){
            $departments = $departments->where('department_name', 'like' , $request->search_name."%");
        }

        $departments = $departments->get();
        
        return $departments;
    }

    public function getDesignation($request){
        $client_id = Auth::user()->cid;
        $designations = Designation::where('status',1)->where('cid', $client_id)->select('designation_id','designation_name');
        
        if(isset($request->search_name)){
            $designations = $designations->where('designation_name', 'like' , $request->search_name."%");
        }
        
        $designations = $designations->get();

        return $designations;
    }
    

    public function getIssueTypes($request){
        $activeIssueTypes = GrIssueType::where('status',1)->select('issue_type_id','issue_type_unique_name','issue_type_name')->get();

        return $activeIssueTypes;
    }
}