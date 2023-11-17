<?php

namespace Modules\Grievance\Grievance\Backend\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class GRAplicationCreateRequest extends FormRequest
{
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "issue_type_id" => "required",
            "issue_raised_against_emp_id" => "required",
            "issue_raised_against_dept_id" => "required",
            "issue_raised_against_desig_id" => "required",
            "from_date" => "required",
            "to_date" => "required",
            "save_draft" => "required",
            "recipient_type" => "required",
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

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            "issue_type_id.required" =>"Issue type  Required Field",
            "issue_raised_by_emp_id.required" =>"issue raised by emp Required Field",
            "issue_raised_by_dept_id.required" =>"issue raised by dept Required Field",
            "issue_raised_by_desig_id.required" =>"issue raised by desig Required Field",
            "issue_raised_against_emp_id.required" =>"issue raised against emp Required Field",
            "issue_raised_against_dept_id.required" =>"issue raised against dept id Required Field",
            "issue_raised_against_desig_id.required" =>"issue raised against desig Required Field",
            "from_date.required" =>"from date Required Field",
            "to_date.required" =>"to date Required Field",
            "save_draft.required" =>"save draft Required Field",
            "recipient_type.required" =>"recipient type Required Field",
            "anonymous.required" =>"anonymous Required Field"
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        $response = new Response(['error' => $validator->errors()->first()], 422);
        throw new ValidationException($validator, $response);
    }
}

