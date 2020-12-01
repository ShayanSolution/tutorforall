<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceNotification;
use App\Models\Disbursement;
use App\Models\SessionPayment;
use App\Models\Setting;
use App\Models\TutorInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;
use Illuminate\Support\Facades\Mail;

class PaymentInvoices extends Command {

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'payment:invoices';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will check payment invoices ';

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
		Log::info('Payment Invoices called');
		$tutor_ids = Disbursement::where('invoice_id', null)->where('paymentable_type', SessionPayment::Class)->get();
		$tutor_ids = $tutor_ids->groupBy('tutor_id');
		$tutor_ids->each(function ($tutor_id) {

			$tutorInvoiceSettings        = Setting::where('group_name', 'tutor-invoice-settings')->get();
			$tutor_invoice_due_amount    = $tutorInvoiceSettings->firstWhere('slug',
				'tutor_invoice_due_amount')['value'];
			$tutor_invoice_generate_days = $tutorInvoiceSettings->firstWhere('slug',
				'tutor_invoice_generate_days')['value'];

			$lastInvoice = TutorInvoice::orderBy('created_at', 'desc')->where('tutor_id',
				$tutor_id[0]->tutor_id)->first();
			$days        = 0;

			if (!is_null($lastInvoice)) {
				$now  = Carbon::now();
				$date = Carbon::make($lastInvoice->created_at);
				$days = $now->diffInDays($date);
			}

			if ($tutor_id->sum('amount') >= $tutor_invoice_due_amount || $days > $tutor_invoice_generate_days) {
				$payment = collect(['cash' => 0, 'jazzcash' => 0, 'card' => 0, 'other_payment' => 0, 'total' => 0]);
				$tutor_id->each(function ($disbursement) use ($payment) {
					if ($disbursement->paymentable->transaction_platform == 'cash')
						$payment['cash'] = $payment['cash'] + $disbursement->amount;
					else if ($disbursement->paymentable->transaction_platform == 'jazzcash')
						$payment['jazzcash'] = $payment['jazzcash'] + $disbursement->amount;
					else if ($disbursement->paymentable->transaction_platform == 'card')
						$payment['card'] = $payment['card'] + $disbursement->amount;
					else
						$payment['other_payment'] = $payment['other_payment'] + $disbursement->amount;
					$payment['total'] = $payment['total'] + $disbursement->amount;
				});

				$dueDays = $tutorInvoiceSettings->firstWhere('slug', 'tutor_invoice_due_days')['value'];

				$commsionSettings = Setting::where('group_name', 'session-commision-percentage-settings')->first();

				$commission  = doubleval(($payment['total'] / 100) * $commsionSettings->value);
				$cost        = $payment['total'] - $commission;
				$payable     = $cost - $payment['cash'];
				$receiveable = $commission - ($payment['jazzcash'] + $payment['card']);

				$due_date = Carbon::now()->addDays($dueDays)->format('Y-m-d');
				$invoice  = TutorInvoice::create([
					'tutor_id'              => $tutor_id[0]->tutor_id,
					'amount'                => $payment['total'],
					'commission'            => $commission,
					'payable'               => $payable,
					'receiveable'           => $receiveable,
					'due_date'              => $due_date,
					'status'                => 'pending',
					'transaction_ref_no'    => null,
					'transaction_type'      => null,
					'transaction_platform'  => null,
					'transaction_status'    => null,
					'commission_percentage' => doubleval($commsionSettings->value),
					'cash_payment'          => $payment['cash'],
					'jazzcash_payment'      => $payment['jazzcash'],
					'card_payment'          => $payment['card'],
				]);
				Disbursement::where('tutor_id', $tutor_id[0]->tutor_id)->where('invoice_id', null)->update([
					'invoice_id' => $invoice->id
				]);
				Log::info("Inoivce generated for " . $invoice->tutor_id);
				$user                   = User::find($tutor_id[0]->tutor_id);
				$studentEmail           = $user->email;
				$Emailsubject           = env("SESSION_PAYMENT_MAIL_SUBJECT", "Tutor Payment Invoice");
				$tutorFirstName         = $user->firstName;
				$tutorLastName          = $user->lastName;
				$data['tutorFirstName'] = $tutorFirstName;
				$data['tutorLastName']  = $tutorLastName;
				$data['earning']        = $invoice->amount;
				$data['commission']     = $invoice->commission;
				$data['cash']           = $payment['cash'];
				$data['jazzcash']       = $payment['jazzcash'];
				$data['card']           = $payment['card'];
				if ($invoice->payable > 0) {
					$data['invoice_message']  = "Your pending amount is " . $invoice->payable . ' We will pay as per our policy';
					$data['due_date_message'] = "";
				} else {
					$data['invoice_message']  = "Your due amount is " . $invoice->receiveable;
					$data['due_date_message'] = "Invoice due date is " . Carbon::make($invoice->due_date)->format('d-m-y') .
						' please pay your invoice with in due date';
				}

				Mail::send('emails.tutorInvoice',
					$data,
					function ($message) use ($studentEmail, $Emailsubject, $tutorFirstName, $tutorLastName) {
						$message->to($studentEmail, $tutorFirstName . " " . $tutorLastName)->subject($Emailsubject);
					});
				Log::info("Email generated for " . $invoice->tutor_id);
				$job = new SendInvoiceNotification($invoice);
				dispatch($job);

				Log::info("Notification generated for " . $invoice->tutor_id);
			}
		});
	}
}
