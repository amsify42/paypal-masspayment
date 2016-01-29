<?php 
namespace Amsify42\PaypalMassPayment;
use       Config;

class LaravelPaypalMassPayment {

    private $authentication   = 'api_signature';

    private $environment      = 'sandbox';

    private $operation_type   = 'nvp';

    private $api_vesion       = '51.0';

    private $receiver_type    = 'email';

    private $currency         = 'USD';

    private $api_username     = '';

    private $api_password     = '';

    private $api_certificate  = '';

    private $api_signature    = '';


    private $method_name      = 'MassPay';


    function  __construct() {
        $this->authentication   = $this->getConfig('authentication');
        $this->environment      = $this->getConfig('environment');
        $this->operation_type   = $this->getConfig('operation_type');
        $this->api_vesion       = $this->getConfig('api_vesion');
        $this->receiver_type    = $this->getConfig('receiver_type');
        $this->currency         = $this->getConfig('currency');


        $this->api_username     = $this->getCredential('api_username');
        $this->api_password     = $this->getCredential('api_password');
        $this->api_certificate  = $this->getCredential('api_certificate');
        $this->api_signature    = $this->getCredential('api_signature');
    }



    public function executeMassPay($emailSubject, $paymentArray) {

        $receiversLenght = count($paymentArray);

        // Add request-specific fields to the request string.
        $paymentString   = '&EMAILSUBJECT='.$emailSubject;
        $paymentString  .= '&RECEIVERTYPE='.$this->receiver_type;
        $paymentString  .= '&CURRENCYCODE='.$this->currency;

        $receiversArray  = array();

        for($i = 0; $i < $receiversLenght; $i++) {

         $receiversArray[$i] = $paymentArray[$i];

        }

        foreach($receiversArray as $i => $receiverData) {

         $paymentString  .= $this->createPaymentString($receiverData, $i);

        }

        $this->executePayment($paymentString);

    }    



    private function executePayment($paymentString) {


         $API_Endpoint = $this->generateEndPointURL();
         $httpResponse = $this->getCurlHttpResponse($API_Endpoint, $paymentString);

         if(!$httpResponse){
          echo $this->method_name . ' failed: ' . curl_error($ch) . '(' . curl_errno($ch) .')';
         }

         // Extract the response details.
         $httpResponseAr       = explode("&", $httpResponse);
         $httpParsedResponseAr = array();

         foreach ($httpResponseAr as $i => $value){
          $tmpAr = explode("=", $value);
          if(sizeof($tmpAr) > 1){
           $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
          }
         }

         if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)){
          exit("Invalid HTTP Response for POST request({$requestString}) to $API_Endpoint.");
         }
         
         return $httpParsedResponseAr;
    }


    private function getCurlHttpResponse($API_Endpoint, $paymentString) {

        // Set the curl parameters.
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
         curl_setopt($ch, CURLOPT_VERBOSE, 1);

         // Turn off the server and peer verification (TrustManager Concept).
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

         // If the authentication type is api_certificate
         if($this->authentication == 'api_certificate') {
         curl_setopt($ch, CURLOPT_SSLCERT, getcwd() . $this->api_certificate);   
         }

         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_POST, 1);

         // Set the API operation, version, and API signature in the request.
         $requestString = $this->generateRequestURL($this->method_name, $paymentString);

         // Set the request as a POST FIELD for curl.
         curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString."&".$paymentString);

         // Get response from the server.
         $httpResponse = curl_exec($ch);

         return $httpResponse;
    }


    private function generateRequestURL($method_name, $paymentString) {

            $str  = 'METHOD='.$method_name;
            $str .= '&VERSION='.urlencode($this->api_vesion);
            $str .= '&PWD='.urlencode($this->api_password);
            $str .= '&USER='.urlencode($this->api_username);

            // If the authentication type is api_signature
            if($this->authentication == 'api_signature') {
            $str .= '&SIGNATURE='.urlencode($this->api_signature);                
            } 
            
            return $str;
    }


    private function generateEndPointURL() {

        $endpoint_url    = 'https://api';
        $endpoint_suffix = ($this->operation_type == 'soap') ? '2.0' : 'nvp';

        if($this->authentication == 'api_certificate') {

            if($this->environment == 'sandbox') {    
                $endpoint_url .= '.sandbox.paypal.com/'.$endpoint_suffix;    
            }
            
            else if($this->environment == 'live') {
                $endpoint_url .= '.paypal.com/'.$endpoint_suffix;    
            }    

        } 

        else if($this->authentication == 'api_signature') {

            if($this->environment == 'sandbox') {    
                $endpoint_url .= '-3t.sandbox.paypal.com/'.$endpoint_suffix;    
            }
            
            else if($this->environment == 'live') {
                $endpoint_url .= '-3t.paypal.com/'.$endpoint_suffix;    
            }          

        }

        return $endpoint_url;
    }



    private function createPaymentString($receiverData, $i) {

         $receiverType   = $this->getReceiverType($this->receiver_type, $i);

         $receiverEmail  = urlencode($receiverData[$receiverType['type']]);
         $amount         = urlencode($receiverData['Amount']);
         $uniqueID       = urlencode($receiverData['UniqueId']);
         $note           = urlencode($receiverData['Note']);

         $paymentString  = '&'.$receiverType['param'].'='.$receiverEmail;
         $paymentString .= '&'.$this->filterParam('amount', $i).'='.$amount;
         $paymentString .= '&'.$this->filterParam('uniqueid', $i).'='.$uniqueID;
         $paymentString .= '&'.$this->filterParam('note', $i).'='.$note;

         return $paymentString;
    }


    private function filterParam($param, $i) {

        $setParam = '';

        if($param == 'amount') {

            if($this->operation_type == 'soap') {
                $setParam = 'Amount';
            } else {
                $setParam = 'L_AMT'.$i;
            }

        }

        if($param == 'uniqueid') {

            if($this->operation_type == 'soap') {
                $setParam = 'UniqueId';
            } else {
                $setParam = 'L_UNIQUEID'.$i;
            }
            
        }

        if($param == 'note') {

            if($this->operation_type == 'soap') {
                $setParam = 'Note';
            } else {
                $setParam = 'L_NOTE'.$i;
            }
            
        }

        return $setParam;

    }



    private function getReceiverType($type, $i) {

          $receiverType          = array();  
          $receiverType['type']  = '';
          $receiverType['param'] = '';

          if($type == 'email') {
            $receiverType['type']  = 'ReceiverEmail';

            if($this->operation_type == 'soap') {
                $receiverType['param'] = 'ReceiverEmail';
            } else {
                $receiverType['param'] = 'L_EMAIL'.$i;
            }

          }  

          if($type == 'phone' && $this->environment == 'live') {
            $receiverType['type']  = 'ReceiverPhone';

            if($this->operation_type == 'soap') {
                $receiverType['param'] = 'ReceiverPhone';
            } else {
                $receiverType['param'] = 'L_RECEIVERPHONE'.$i;
            }

          }  

          if($type == 'id') {
            $receiverType['type']  = 'ReceiverID';
            
            if($this->operation_type == 'soap') {
                $receiverType['param'] = 'ReceiverID';
            } else {
                $receiverType['param'] = 'L_RECEIVERID'.$i;
            }

          }

          return $receiverType;  
       
    }



    private function getConfig($var) {

        $configVal = Config::get('paypal-masspayment.'.$var);

        if($configVal != '') {
           return strtolower($configVal); 
        }

        return $this->$var;
    }


    private function getCredential($var) {

        $configVal = '';

        $environment = strtolower($this->environment);
        if($environment == 'sandbox' || $environment == 'live') {
            $configVal = Config::get('paypal-masspayment.'.$this->environment.'.'.$var);

            if($configVal != '') {
            return $configVal; 
            }
        }

        return $configVal;
    }

}
