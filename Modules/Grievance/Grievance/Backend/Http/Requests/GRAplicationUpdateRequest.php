<?php

namespace Modules\Grievance\Grievance\Backend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GRAplicationUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "issue_raised_by_emp_id" => "required",
            "issue_raised_by_dept_id" => "required",
            "issue_raised_by_desig_id" => "required",
            "issue_raised_against_emp_id" => "required",
            "issue_raised_against_dept_id" => "required",
            "issue_raised_against_desig_id" => "required",
            "from_date" => "required",
            "to_date" => "required",
            "save_draft" => "required",
            "status" => "required",
            "anonymous" => "required"
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
