<?php 
namespace Amsify42\PaypalMassPayment;
use       Illuminate\Support\Facades\Facade;

/**
 * @see \Amsify42\PaypalMassPayment\LaravelPaypalMassPayment
 */
class PaypalMassPaymentFacade extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(){
        return 'Amsify42\PaypalMassPayment\LaravelPaypalMassPayment';
    }
}
