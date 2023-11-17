<?php
namespace Modules\Grievance\Grievance\Backend\Service;

use App\Models\DepartmentManager;
use App\Models\Employee;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Grievance\Grievance\Backend\Entities\GRApplication;
use Modules\Grievance\Grievance\Backend\Entities\GRApplicationEvidence;
use Modules\Grievance\Grievance\Backend\Entities\GRApplicationRecipient;
use PhpParser\Node\Expr;

class GRApplicationService
{
    public function getApplications($request){
        try{
            $role = $this->getLoginUserRole();
            $user_id = Auth::user()->id;
            //dd($role);
            //$applications = array();
            $applications = GRApplication::select("application_id", "application_no", "issue_raised_against_emp_id",
                "create_at", "issue_type_id","from_date", "to_date","save_draft","application_status")
                ->with('issue_type');
            if(isset($request->search_status) && !empty($request->search_status))
                $applications = $applications->where("application_status", $request->search_status);

            if(isset($request->search_employee_id) && !empty($request->search_employee_id)){
                $applications = $applications->where("issue_raised_against_emp_id", $request->search_employee_id);
            }

            $applications = $applications->orderBy('application_id', 'desc');
        
            if($role == "Employee"){
                $applications = $applications->where("issue_raised_by_emp_id",$user_id)
                ->with(['issue_raised_against_employee' =>function ($query){$query->select('id','name');}]);
                $applications = $applications->get();
                return $applications;
            } else if($role == "LM" || $role == "Line Manager"){
                $application_data['lm_applications'] = $applications->where("issue_raised_by_emp_id", Auth::user()->id)
                    ->with(['issue_raised_against_employee' =>function ($query){$query->select('id','name');}])
                    ->orderBy('application_id', 'desc')
                    ->get();
                $lm_applications = GRApplication::select("application_id", "application_no","issue_raised_by_emp_id", "issue_raised_against_emp_id",
                "create_at", "from_date", "issue_type_id", "to_date","save_draft","application_status")->with('issue_type');
                if(isset($request->search_status) && !empty($request->search_status))
                    $lm_applications = $lm_applications->where("application_status", $request->search_status);

                if(isset($request->search_employee_id) && !empty($request->search_employee_id)){
                    $lm_applications = $lm_applications->where("issue_raised_against_emp_id", $request->search_employee_id);
            }
                $application_data['assigned_applications'] = $lm_applications->whereHas("application_recipients", function($q) use ($user_id){
                    $q->where('recipient_emp_id', $user_id);
                })
                ->with(['issue_raised_against_employee' =>function ($query){$query->select('id','name');}])
                ->with(['issue_raised_by_employee' =>function ($query){$query->select('id','name');}])
                ->orderBy('application_id', 'desc')
                ->get();

                return $application_data;
            }
            
            return false;
            //dd($applications)
            
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function getApplicationById($application_id){
        try{
            $role = $this->getLoginUserRole();
            //$applications = array();
            $application = GRApplication::select("application_id", "application_no",
            "issue_raised_by_emp_id","issue_raised_by_dept_id","issue_raised_by_desig_id", 
                "issue_raised_against_emp_id","issue_raised_against_desig_id","issue_raised_against_dept_id",
                "create_at", "issue_type_id","from_date", "to_date","save_draft","application_status","anonymous")
                ->with(['detail_section', 'issue_type','issue_raised_by_department', 'issue_raised_by_designation'])
                ->with(['issue_raised_by_employee' =>function ($query){$query->select('id','name');}]);
            if($role == "Employee"){
                $application = $application->where("issue_raised_by_emp_id", Auth::user()->id);

                $application = $application->get();
            }
            else if($role == "LM" || $role == "Line Manager"){
                $application = $application->with(['login_lm_section']);
            }else if($role == "HR"){
                $application = $application->with(['lm_section']);
            }
            $application = $application->where("application_id", $application_id)->first();
            //dd($application)

            return $this->formatApplication($application, $role);
            //return $application;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function updateApplication($request, $application_id){
        DB::beginTransaction();
        try{
            $application = GRApplication::where('application_id',$application_id)->first();
            if(!$application)
                throw new \ErrorException('Application Not Fount');
            if($application->save_draft == 0)
                throw new \ErrorException('You can not update that application');
            //dd($request->all());
            //$this->createApplicationEvidence($request->evidence, $application_id);
            $insert_application = [
                "issue_raised_against_emp_id" => $request->issue_raised_against_emp_id??$application->issue_raised_against_emp_id,
                "issue_raised_against_dept_id" => $request->issue_raised_against_dept_id??$application->issue_raised_against_dept_id,
                "issue_raised_against_desig_id" => $request->issue_raised_against_desig_id??$application->issue_raised_against_desig_id,
                "from_date" => $this->format_str_date_for_db($request->from_date??$application->from_date),
                "to_date" => $this->format_str_date_for_db($request->to_date??$application->to_date),
                "save_draft" => $request->save_draft??$application->save_draft,
                "anonymous" => $request->anonymous??$application->anonymous,
                "modified_by" => Auth::user()->id,
                "modified_at" => Carbon::now()
            ];
            $application = $application->update($insert_application);
            if(isset($request->evidence) && count($request->evidence) > 0){             
                $this->createApplicationEvidence($request->evidence, $application_id);
            }
            if($request->save_draft == 0){
                $recipient_request = [
                    "application_id" => $application_id,
                    "issue_raised_by_emp_id" => Auth::user()->id,
                    "issue_raised_by_dept_id" => Auth::user()->department_id,
                    "issue_raised_by_desig_id" => Auth::user()->dept_id,
                    "issue_raised_against_emp_id" => $request->issue_raised_against_emp_id,
                    "issue_raised_against_dept_id" => $request->issue_raised_against_dept_id,
                    "issue_raised_against_desig_id" => $request->issue_raised_against_desig_id,
                    "recipient_type" => $request->recipient_type
                ];
                $this->createApplicationRecipient($recipient_request);
            }
            DB::commit();
            return $application;
        } catch (Exception $ex){
            DB::rollback();
            throw $ex;
        }
    }

    public function uploadApplicationEvidence($request, $application_id){
        DB::beginTransaction();
        try{
            $this->createApplicationEvidence($request->evidence, $application_id);
            DB::commit();
            return true;
        } catch (Exception $ex){
            DB::rollback();
            throw $ex;
        }
    }

    public function deleteApplicationEvidence( $evidence_id){
        try{
            $delete_evidence = GRApplicationEvidence::where('evidence_id',$evidence_id)->delete();
            return $delete_evidence;
        } catch (Exception $ex){
            throw $ex;
        }
    }

    public function createApplication ($request){
        DB::beginTransaction();
        try{
            //Insert Application
            $insert_application = [
                "application_no" => $this->generate_application_no(),
                "issue_type_id" => $request->issue_type_id,
                "issue_raised_by_emp_id" => Auth::user()->id,
                "issue_raised_by_dept_id" => Auth::user()->department_id,
                "issue_raised_by_desig_id" => Auth::user()->dept_id,
                "issue_raised_against_emp_id" => $request->issue_raised_against_emp_id,
                "issue_raised_against_dept_id" => $request->issue_raised_against_dept_id,
                "issue_raised_against_desig_id" => $request->issue_raised_against_desig_id,
                "from_date" => $this->format_str_date_for_db($request->from_date),
                "to_date" => $this->format_str_date_for_db($request->to_date),
                "save_draft" => $request->save_draft,
                "application_status" => 1,
                "anonymous" => $request->anonymous,
                "client_id" => $this->get_client_id(),
                "created_by" => Auth::user()->id,
                "create_at" => Carbon::now(),
                "modified_by" => Auth::user()->id,
                "modified_at" => Carbon::now()
            ];
            $application = GRApplication::create($insert_application);

            if(isset($request->evidence) && count($request->evidence) > 0){
                $this->createApplicationEvidence($request->evidence, $application->application_id);
            }
            if($request->save_draft == 0){
                $recipient_request = [
                    "application_id" => $application->application_id,
                    "issue_raised_by_emp_id" => Auth::user()->id,
                    "issue_raised_by_dept_id" => Auth::user()->department_id,
                    "issue_raised_by_desig_id" => Auth::user()->dept_id,
                    "issue_raised_against_emp_id" => $request->issue_raised_against_emp_id,
                    "issue_raised_against_dept_id" => $request->issue_raised_against_dept_id,
                    "issue_raised_against_desig_id" => $request->issue_raised_against_desig_id,
                    "recipient_type" => $request->recipient_type
                ];
                $this->createApplicationRecipient($recipient_request);
            }
            
            //dd($insert_application);
            DB::commit();
            //dd($application);
            //$this->upload_file($request->file("file"));
        } catch (Exception $ex){
            DB::rollback();
            throw $ex;
        }
    }

    public function createApplicationEvidence($evidences, $application_id){
        try{
            //dd($evidences);
            $is_raised_by = false;
            $application = GRApplication::where('application_id',$application_id)->first();
            if($application && $application->issue_raised_by_emp_id == Auth::user()->id){
                $is_raised_by = true;
            }
            $section_type = $this->getEvidenceSectionType($is_raised_by);

            foreach ($evidences as $key => $evidence) {
                if($evidence['type'] == "text"){
                    GRApplicationEvidence::where([
                        'application_id'=>$application_id, 
                        'created_by'=>Auth::user()->id, 
                        'evidence_type'=>'text', 
                        ])->delete();
                }
                $insert_evidence = [
                    "application_id" => $application_id,
                    "section_type" => $section_type,
                    "text_description" =>$evidence['text']??"",
                    "evidence_type" => $evidence['type'],                    
                    "created_by" => Auth::user()->id,
                    "created_at" => Carbon::now(),
                    "modified_by" => Auth::user()->id,
                    "modified_at" => Carbon::now()
                ];
                if(isset($evidence['file']) && $evidence['file'] != null){
                    $upload_file = $this->upload_file($evidence['file']);
                    $insert_evidence["file_name"] = $upload_file['file_name'];  
                    $insert_evidence["file_path"] = $upload_file['file_path'];  
                    $insert_evidence["file_ext"] = $upload_file['file_ext']; 
                    //dd($insert_evidence);
                }
                GRApplicationEvidence::create($insert_evidence);
                //dd(false);
            }
            
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param String recipient_type canbe 'HR' , 'LM'
     * @param  boolean is_grievance_against_general canbe 'HR' , 'LM'
     */
    public function createApplicationRecipient($recipient_request){
        try{
            $recipient_list = array();
            if(isset($recipient_request['issue_raised_against_emp_id']) 
            && $recipient_request['issue_raised_against_emp_id'] != null 
            && $recipient_request['recipient_type'] == 'LM'){                
                $recipient_list = $this->get_employee_lm($recipient_request['issue_raised_by_emp_id'] , $recipient_request['issue_raised_by_dept_id']);                
            }else{
                $recipient_list = $this->get_department_hr($recipient_request['issue_raised_by_dept_id']);
            }
            
            //dd($recipient_list);
            if(count($recipient_list) > 0){
                foreach ($recipient_list as $key => $recipient_id) {
                    GRApplicationRecipient::create([
                        "recipient_application_id" => $recipient_request['application_id'],
                        "recipient_emp_id" => $recipient_id,
                        "recipient_status" => 0
                    ]);
                }
            }
            
        } catch (Exception $ex) {
            throw $ex;
        }   
    }
    /******************************************************* ************************************************************/
        private function generate_application_no(){
            $count_application = GRApplication::count()+1;
            $timestamp = Carbon::now()->timestamp;
            $num_str = sprintf("%06d", $count_application);
            //dd("GR-".$timestamp."-".$num_str);
            return "GR-".$timestamp."-".$num_str;
            
        }
        
        private function get_client_id(){
            return 48;
        }

        private function upload_file($file=null, $location="Grievance")
        {
            try{
                //dd($file);
                $mimeArr = explode("/",$file->getMimeType());
                $file_type = ($mimeArr[0] == "video")?"video":(($mimeArr[0]=='image')?"image":(($mimeArr[0]=='audio')?"audio":"file"));
                $file_ext  = $file->getClientOriginalExtension();
                $fileName = $file->getClientOriginalName();
                $current_timestamp = Carbon::now()->timestamp;
                $directory = Carbon::now()->format("Y/m/d");
                
                $filename = $current_timestamp."_". $file->getClientOriginalName(). '.' . $file_ext;
                
                $path = $location."/".$directory."/".$file_type;
                $complete_path = $path."/".$filename;
                
                $upload_file = $file->move(public_path($path), $filename);
                $file_upload_info = [
                    'file_type' => $file_type,
                    'file_name' => $filename,
                    'file_path' => $complete_path,
                    'file_ext' => $file_ext
                ];
                //dd($file_upload_info);
                return $file_upload_info;
            } catch (Exception $ex){
                throw $ex;
            }
        }
        
        private function format_str_date_for_db($data){
            return date('Y-m-d',strtotime($data));
        }

        private function get_employee_lm($emp_id, $department_id){
            $line_managers = DepartmentManager::where('department_hierarchy_id', $department_id)->get()->pluck('empid');
            //dd($line_managers);
            return $line_managers;
            //return [17282,17278];
        }
        private function get_department_hr($department_id){
            return [57523,55572];
        }
        private function getLoginUserRole(){
            $user_id = Auth::user()->id;
            $client_id = Auth::user()->cid;
            $roles = Employee::User_Roles_v2_3($user_id,$client_id)->pluck('name');
            //dd($roles);
            if(in_array( "Line Manager" ,$roles->toArray() ) || in_array( "LM" ,$roles->toArray() )){
                $role_name = "Line Manager";
            }else{
                $role_name = $roles->first();
            }
            //dd($role_name);
            return $role_name;
        }
        private function getEvidenceSectionType($is_raised_by = null)
        {
            $role = $this->getLoginUserRole();
            $section_type = "detail_section";
            if(!$is_raised_by && ($role == "LM" || $role == "Line Manager"))
                $section_type = "lm_section";
            
            return $section_type;

        }
        private function formatApplication($application , $role)
        {
            //dd($application->toArray());
            //if($role == 'Employee' && isset($application->detail_section) && $application->detail_section){
            if(isset($application->detail_section) && $application->detail_section != null){
                $application->detail_section['videos'] = $this->evidencefiles('video',$application->application_id, $application->detail_section->created_by);
                $application->detail_section['images'] = $this->evidencefiles('image',$application->application_id, $application->detail_section->created_by);
                $application->detail_section['audio'] = $this->evidencefiles('audio',$application->application_id, $application->detail_section->created_by);
                $application->detail_section['file'] = $this->evidencefiles('file',$application->application_id, $application->detail_section->created_by);
            }

            if(($role == 'Line Manager' || $role == 'LM') && (isset($application->login_lm_section) && $application->login_lm_section != null)){
                $application->login_lm_section['videos'] = $this->evidencefiles('video',$application->application_id, $application->login_lm_section->created_by);
                $application->login_lm_section['images'] = $this->evidencefiles('image',$application->application_id, $application->login_lm_section->created_by);
                $application->login_lm_section['audio'] = $this->evidencefiles('audio',$application->application_id, $application->login_lm_section->created_by);
                $application->login_lm_section['file'] = $this->evidencefiles('file',$application->application_id, $application->login_lm_section->created_by);
            }

            if($role == 'HR' && isset($application->lm_section) && count($application->lm_section) > 0){
                foreach($application->lm_section as $lm_section){
                    $lm_section['videos'] = $this->evidencefiles('video',$application->application_id, $lm_section->created_by);
                    $lm_section['images'] = $this->evidencefiles('image',$application->application_id, $lm_section->created_by);
                    $lm_section['audio'] = $this->evidencefiles('audio',$application->application_id, $lm_section->created_by);
                    $lm_section['file'] = $this->evidencefiles('file',$application->application_id, $lm_section->created_by);
                }
                
            }

            return $application;
        }

        private function evidencefiles($type, $application_id, $created_by)
        {
            //$files = GRApplicationEvidence::where(['application_id'=>$application_id, 'created_by'=>$created_by,'evidence_type'=>$type])->get();
            $files = GRApplicationEvidence::where('application_id',$application_id)->where('created_by',$created_by)->where('evidence_type',$type)->get();
            //dd($type,$application_id,$created_by); 
            return $files;
        }
    /******************************************************* ************************************************************/
}