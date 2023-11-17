<?php

namespace Modules\Grievance\Grievance\Backend\Entities;

use Illuminate\Database\Eloquent\Model;

class GRApplicationEvidence extends Model
{
    protected $table = 'gr_application_evidences';

    protected $primaryKey = 'evidence_id';

    protected $fillable = [
        "evidence_id",
        "application_id",
        "section_type",
        "evidence_type",
        "text_description",
        "file_name",
        "file_path",
        "file_ext",
        "created_by",
        "created_at",
        "modified_by",
        "modified_at"

    ];

    public $timestamps = false;
    protected $casts = [
        'application_from_date'  => 'date:d-M-Y',
        'application_to_date' => 'date:d-M-Y',
        'created_at' => 'date:d-M-Y',
        'modified_at' => 'date:d-M-Y'
    ];

    public function getEvidenceFilePathAttribute($value){
        return url('/')."/".$value;
    }

    public function application(){
        return $this->belongsTo(GRApplication::class,'evidence_application_id');
    }
}
