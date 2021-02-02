<?php

namespace App\Console\Commands;

use App\Jobs\SendBlockNotification;
use App\Models\TutorInvoice;
use Carbon\Carbon;
use function foo\func;
use Illuminate\Console\Command;
use Log;

class BlockUnPaidInvoiceUsers extends Command {

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'block:unpaid';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This Command will Block pending invoice users';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle() {
		Log::info("Block user cron job called");
		$invoices = TutorInvoice::blockable()->get();
		$invoices->each(function ($invoice) {
            $message = "Your are blocked due to unpaid invoice. Please pay as soon as possible and start earning. Good day";
			Log::info("user blocking => " . $invoice->tutor_id);
			$invoice->tutor->is_blocked = 1;
			$invoice->tutor->is_online  = 0;
			$invoice->tutor->save();
			$job = new SendBlockNotification($invoice->tutor, $message);
			dispatch($job);
		});
	}
}
