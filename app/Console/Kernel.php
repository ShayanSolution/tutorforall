<?php

namespace App\Console;

use App\Console\Commands\BlockUnPaidInvoiceUsers;
use App\Console\Commands\PaymentInvoices;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands
		= [
			PaymentInvoices::Class,
			BlockUnPaidInvoiceUsers::class
		];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule) {
		$schedule->command('block:unpaid')->dailyAt('12 : 00');
		$schedule->command('payment:invoices')->dailyAt('12 : 05');
	}
}
