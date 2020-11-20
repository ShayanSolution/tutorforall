<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disbursement extends Model {

	protected $fillable
		= [
			'tutor_id',
			'type',
			'amount',
			'paymentable_type',
			'paymentable_id'
		];

	protected function paymentable() {
		return $this->morphTo();
	}
}
