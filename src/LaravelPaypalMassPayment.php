<?php 
namespace Amsify42\PaypalMassPayment;
use       Config;

class LaravelPaypalMassPayment {

    /**
     * Authentication
     */
    private $authentication     = 'api_signature';

    /**
     * Envorenment: live or sandbox
     */
    private $environment        = 'sandbox';

    /**
     * Operation Type
     */
    private $operation_type     = 'nvp';

    /**
     * API Version
     */
    private $api_version        = '51.0';

    /**
     * Receiver Type
     */
    private $receiver_type      = 'email';

    /**
     * Currency
     */
    private $currency           = 'USD';

    /**
     * API Username
     */
    private $api_username       = '';

    /**
     * API Password
     */
    private $api_password       = '';

    /**
     * API Certificate
     */
    private $api_certificate    = '';

    /**
     * API Signature
     */
    private $api_signature      = '';

    /**
     * Request String
     */
    private $requestString      = '';

    /**
     * Config
     */
    private $config             = array();  

    /**
     * Method Name
     */
    private $methodName         = 'MassPay';

    /**
     * Set all values from config
     */
    function  __construct() {
        $this->setConfigVar('authentication');
        $this->setConfigVar('environment');
        $this->setConfigVar('operation_type');
        $this->setConfigVar('api_version');
        $this->setConfigVar('receiver_type');
        $this->setConfigVar('currency');

        $this->setCredential('api_username');
        $this->setCredential('api_password');
        $this->setCredential('api_certificate');
        $this->setCredential('api_signature');
    }

    /**
     * Set custom config
     * @param array $config
     * @return object $this
     */
    public function setConfig($config = array()) {
        if(sizeof($config) > 0) {
            $this->config  = $config;
            $this->setConfigVar('authentication', true);
            $this->setConfigVar('environment', true);
            $this->setConfigVar('operation_type', true);
            $this->setConfigVar('api_version', true);
            $this->setConfigVar('receiver_type', true);
            $this->setConfigVar('currency', true);

            $this->setCredential('api_username', true);
            $this->setCredential('api_password', true);
            $this->setCredential('api_certificate', true);
            $this->setCredential('api_signature', true);
        }
        return $this;
    }

    /**
     * Print Config
     */
    public function printConfig() {
        echo '<table>';
        echo '<tr><td style="font-weight:bold;">authentication:</td><td>'.$this->authentication.'</td></tr>';
        echo '<tr><td style="font-weight:bold;">environment:</td><td>'.$this->environment.'</td></tr>';
        echo '<tr><td style="font-weight:bold;">operation_type:</td><td>'.$this->operation_type.'</td></tr>';
        echo '<tr><td style="font-weight:bold;">api_version:</td><td>'.$this->api_version.'</td></tr>';
        echo '<tr><td style="font-weight:bold;">receiver_type:</td><td>'.$this->receiver_type.'</td></tr>';
        echo '<tr><td style="font-weight:bold;">currency:</td><td>'.$this->currency.'</td></tr>';
        echo '<tr><td></td><td></td></tr>';
        echo '<tr><td style="font-weight:bold;">api_username:</td><td>'.$this->api_username.'</td></tr>';
        echo '<tr><td style="font-weight:bold;">api_password:</td><td>'.$this->api_password.'</td></tr>';
        echo '<tr><td style="font-weight:bold;">api_certificate:</td><td>'.$this->api_certificate.'</td></tr>';
        echo '<tr><td style="font-weight:bold;">api_signature:</td><td>'.$this->api_signature.'</td></tr>';
        echo '</table>';
    }

    /**
     * Execute mass payment
     * @param  string $emailSubject
     * @param  array $paymentArray
     * @return array
     */
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
            $paymentString .= $this->createPaymentString($receiverData, $i);
        }

        return $this->executePayment($paymentString);

    }    

    /**
     * Execute Payment
     * @param  string $paymentString
     * @return array
     */
    private function executePayment($paymentString) {

        $API_Endpoint = $this->generateEndPointURL();
        $httpResponse = $this->getCurlHttpResponse($API_Endpoint, $paymentString);

        if(!$httpResponse){
            echo $this->methodName . ' failed: ' . curl_error($ch) . '(' . curl_errno($ch) .')';
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
            throw new \Exception("Invalid HTTP Response for POST request(".$this->requestString.") to ".$API_Endpoint);
        }

        return $httpParsedResponseAr;
    }

    /**
     * Curl paypal call
     * @param  string $API_Endpoint
     * @param  string $paymentString
     * @return object
     */
    private function getCurlHttpResponse($API_Endpoint, $paymentString) {

        // Set the API operation, version, and API signature in the request.
         $this->requestString = $this->generateRequestURL($this->methodName, $paymentString);

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

         // Set the request as a POST FIELD for curl.
         curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestString);

         // Get response from the server.
         $httpResponse = curl_exec($ch);

         return $httpResponse;
    }

    /**
     * create request url
     * @param  string $methodName
     * @param  string $paymentString
     * @return string
     */
    private function generateRequestURL($methodName, $paymentString) {

        $str  = 'METHOD='.$methodName;
        $str .= '&VERSION='.urlencode($this->api_version);
        $str .= '&PWD='.urlencode($this->api_password);
        $str .= '&USER='.urlencode($this->api_username);

        // If the authentication type is api_signature
        if($this->authentication == 'api_signature') {
            $str .= '&SIGNATURE='.urlencode($this->api_signature);                
        } 
            
        return $str.'&'.$paymentString;
    }

    /**
     * Generate Endpoint URL
     * @return string
     */
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


    /**
     * Create Payment String
     * @param  array $receiverData
     * @param  integer $i
     * @return string
     */
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

    /**
     * Filter Param
     * @param  string $param
     * @param  integer $i
     * @return string
     */
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

    /**
     * Get Receiver Type
     * @param  string $type
     * @param  integer $i
     * @return array
     */
    private function getReceiverType($type, $i) {

        $receiverType          = array();  
        $receiverType['type']  = '';
        $receiverType['param'] = '';

        if($type == 'email') {
            $receiverType['type']       = 'ReceiverEmail';
            if($this->operation_type == 'soap') {
                $receiverType['param']  = 'ReceiverEmail';
            } else {
                $receiverType['param']  = 'L_EMAIL'.$i;
            }
        }  

        if($type == 'phone') {
            $receiverType['type']       = 'ReceiverPhone';
            if($this->operation_type == 'soap') {
                $receiverType['param']  = 'ReceiverPhone';
            } else {
                $receiverType['param']  = 'L_RECEIVERPHONE'.$i;
            }
        }  

        if($type == 'id') {
            $receiverType['type']       = 'ReceiverID';
            if($this->operation_type == 'soap') {
                $receiverType['param']  = 'ReceiverID';
            } else {
                $receiverType['param']  = 'L_RECEIVERID'.$i;
            }
        }

        return $receiverType;  
       
    }

    /**
     * Set Config Variable in object context
     * @param string  $var
     * @param boolean $custom
     */
    private function setConfigVar($var, $custom = false) {
        $configVal = Config::get('paypalmasspayment.'.$var);
        if($configVal) {
           if($custom) {
                if(isset($this->config[$var]))
                $this->$var = $this->config[$var];
           } else {
                $this->$var = $configVal;
           }
        }
    }

    /**
     * Set Credential in object context
     * @param string  $var
     * @param boolean $custom
     */
    private function setCredential($var, $custom = false) {
        $configVal = '';
        $environment = strtolower($this->environment);
        if($environment == 'sandbox' || $environment == 'live') {
            $configVal = Config::get('paypalmasspayment.'.$this->environment.'.'.$var);
            if($configVal) {
                if($custom) {
                    if(isset($this->config[$this->environment][$var]))
                    $this->$var = $this->config[$this->environment][$var];
                } else {
                    $this->$var = $configVal;
                }
            }
        }
    }

}
