<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionPayment extends Model
{
    protected $fillable = [
        'session_id',
        'transaction_ref_no',
        'transaction_type',
        'transaction_platform',
        'amount',
		'paid_amount',
        'insert_date_time',
        'transaction_status',
        'mobile_number',
        'cnic_last_six_digits',
        'wallet_payment',
    ];
}
