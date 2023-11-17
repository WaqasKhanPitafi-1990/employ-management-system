<?php

namespace Modules\Grievance\Grievance\Backend\Entities;

use Illuminate\Database\Eloquent\Model;

class GRApplicationRecipient extends Model
{
    protected $table = 'gr_application_recipients';

    protected $primaryKey = 'recipients_id';

    protected $fillable = [
        "recipient_id",
        "recipient_application_id",
        "recipient_emp_id",
        "recipient_status"
    ];

    public $timestamps = false;

    public function application(){
        return $this->belongsTo(GRApplication::class,'recipient_application_id');
    }
}
