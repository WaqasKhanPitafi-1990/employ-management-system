<?php

namespace Modules\Grievance\Grievance\Backend\Entities;

use Illuminate\Database\Eloquent\Model;

class GrIssueType extends Model
{
    protected $table = 'gr_issue_types';
    protected $fillable = [
        'issue_type_id', 
        'issue_type_unique_name', 
        'issue_type_name',
        'status',
        'client_id',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date'
    ];

    protected $primaryKey = 'issue_type_id';
}
