<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model {

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
}
