<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorInvoice extends Model
{
    protected $fillable = [
        "tutor_id",
        "amount",
        "due_date",
        "status",
        "transaction_ref_no",
        "transaction_type",
        "transaction_platform",
        "transaction_status"
    ];
}
