<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TutorInvoice extends Model {

	protected $fillable
		= [
			"tutor_id",
			"amount",
			"commission",
			"payable",
			"receiveable",
			"due_date",
			"status",
			"transaction_ref_no",
			"transaction_type",
			"transaction_platform",
			"transaction_status",
			"commission_percentage",
			"cash_payment",
			"jazzcash_payment",
			"card_payment"
		];

	protected function tutor() {
		return $this->belongsTo(User::class, 'tutor_id');
	}

	public function scopeBlockable($query) {
		return $query->where('status', 'pending')->where('payable', '<', 0)->whereDate('due_date',
			'<',
			Carbon::now()->format('y-m-d'));
	}
}
