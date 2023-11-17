<?php

namespace Modules\Grievance\Grievance\Backend\Entities;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\proll_client;
use App\Models\ProllEmployeeInfo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GRApplication extends Model
{
    protected $table = 'gr_applications';

    protected $primaryKey = 'application_id';

    protected $fillable = [
        "application_id",
        "application_no",
        "issue_type_id",
        "issue_raised_by_emp_id",
        "issue_raised_by_dept_id",
        "issue_raised_by_desig_id",
        "issue_raised_against_emp_id",
        "issue_raised_against_dept_id",
        "issue_raised_against_desig_id",
        "from_date",
        "to_date",
        "save_draft",
        "application_status",
        "anonymous",
        "client_id",
        "created_by",
        "create_at",
        "modified_by",
        "modified_at"
    ];

    public $timestamps = false;

    protected $casts = [
        'from_date'  => 'date:d-M-Y',
        'to_date' => 'date:d-M-Y',
        'create_at' => 'date:d-M-Y', 
        'modified_at' => 'date:d-M-Y'
    ];

    public function application_evidences(){
        return $this->hasMany(GRApplicationEvidence::class, 'application_id');
    }

    public function detail_section(){
        return $this->hasOne(GRApplicationEvidence::class, 'application_id')
            ->where('section_type','detail_section')->where('evidence_type', 'text');
    }

    public function lm_section(){
        return $this->hasMany(GRApplicationEvidence::class, 'application_id')
        ->where('section_type','lm_section')->where('evidence_type', 'text');
    }



    public function login_lm_section(){
        return $this->hasOne(GRApplicationEvidence::class, 'application_id')
        ->where('section_type','lm_section')->where('evidence_type', 'text')->where('created_by' , Auth::user()->id);
    }

    public function application_recipients(){
        return $this->hasMany(GRApplicationRecipient::class, 'recipient_application_id');
    }

    public function proll_client(){
        return $this->belongsTo(proll_client::class, 'client_id');
    }
    public function issue_raised_against_employee(){
        return $this->belongsTo(Employee::class, 'issue_raised_against_emp_id');
    }

    public function issue_raised_by_employee(){
        return $this->belongsTo(Employee::class, 'issue_raised_by_emp_id');
    }
    
    public function issue_raised_by_department(){
        return $this->belongsTo(Department::class, 'issue_raised_by_dept_id')->select('id', 'department_name');
    }

    public function issue_raised_by_designation(){
        return $this->belongsTo(Designation::class, 'issue_raised_by_desig_id', 'designation_id')->select('designation_id', 'designation_name');
    }

    public function issue_type(){
        return $this->belongsTo(GrIssueType::class,'issue_type_id', 'issue_type_id')->select('issue_type_id', 'issue_type_name');
    }
}
