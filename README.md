

## Paypal Mass Payment for Laravel 5.0
### This is a laravel 5 package only for PayPal Mass Payment.


Installation:

Add the PaypalMassPayment package to your `composer.json` file

```json
{
    "require": {
        "amsify42/paypal-masspayment": "dev-master"
    }
}
```

### Service Provider

In your app config, add the `PaypalMassPaymentServiceProvider` to the providers array.

```php
'providers' => [
    'Amsify42\PaypalMassPayment\PaypalMassPaymentServiceProvider',
    ];
```


### Facade (optional)

If you want to make use of the facade, add it to the aliases array in your app config.

```php
'aliases' => [
    'PaypalMassPayment'	=> 'Amsify42\PaypalMassPayment\PaypalMassPaymentFacade',
    ];
```

### Publish file

```bash
$ php artisan vendor:publish
```
Now file with name paypalmasspayment.php will be copied in directory Config/ and you can add your settings

#### For what to use in all the options available in this config file go to [Using the Mass Payments API](https://developer.paypal.com/docs/classic/mass-pay/integration-guide/MassPayUsingAPI/)


### Add this line at the top of any class to use Test5

```php
use       PaypalMassPayment;
```

### Array of payments looks something like this

#### For what parameters to use in payment array [MassPay API Using NVP](https://developer.paypal.com/docs/classic/mass-pay/integration-guide/MassPayUsingAPI/#id101DEJ0100A) [MassPay API Using SOAP](https://developer.paypal.com/docs/classic/mass-pay/integration-guide/MassPayUsingAPI/#id101DEE00EBL) 

```php
$receivers = array(
		  0 => array(
		    'ReceiverEmail' => "something@somewhere.com", 
		    'Amount'        => "0.01",
		    'UniqueId'      => "id_001", 
		    'Note'          => " Test Streammer 1"), 
		  1 => array(
		    'ReceiverEmail' => "something@somewhere.com",
		    'Amount'        => "0.01",
		    'UniqueId'      => "id_002", 
		    'Note'          => " Test Streammer 2"), 
		);
		
$response = PaypalMassPayment::executeMassPay('Some Subject', $receivers);
```

### or you can directly call PaypalMassPayment without adding it at the top

```php
$response = \PaypalMassPayment::executeMassPay('Some Subject', $receivers);
```
#### For response codes and errors visit [MassPay Error Codes](https://developer.paypal.com/docs/classic/mass-pay/integration-guide/MassPayUsingAPI/#id101DEN0B0E9) 
