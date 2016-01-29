<?php 

namespace   Amsify42\PaypalMassPayment;
use 	    Illuminate\Support\ServiceProvider;

class PaypalMassPaymentServiceProvider extends ServiceProvider {

	public function boot() {
		// this  for conig
		$this->publishes([
				__DIR__.'/config/paypal-masspayment.php' => config_path('paypal-masspayment.php'),
		]);
	}

	public function register() {
		$this->registerPaypalPayment();
		config([
				'config/paypal-masspayment.php',
			  ]);
	}

	private function registerPaypalPayment() {
		$this->app->bind('Amsify42\PaypalMassPayment\PaypalMassPaymentServiceProvider',function($app){
			return new PaypalMassPayment($app);
		});
	}


}
