<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditCard extends Model {
	use SoftDeletes;

	protected $fillable
		= [
			'user_id',
			'is_default',
			'brand_name',
			'name_on_card',
			'card_number',
			'token_id',
			'session_id',
			'agreement_id',
		];
	protected $softDelete = true;
}
