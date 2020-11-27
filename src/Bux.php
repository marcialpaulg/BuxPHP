<?php

namespace Marcialpaulg\BuxPhp;

/**
 * Class Bux
 *
 * @package Marcialpaulg\BuxPhp
 */

class Bux {

    /**
     * @const string The name of the environment variable that contains the api key.
     */
    const APP_KEY_ENV_NAME = 'BUX_APP_KEY';

    /**
     * @const string The name of the environment variable that contains the client id.
     */
    const CLIENT_ID_ENV_NAME = 'BUX_CLIENT_ID';

    /**
     * @const string The name of the environment variable that contains the client secret.
     */
    const CLIENT_SECRET_ENV_NAME = 'BUX_CLIENT_SECRET';

    /**
     * @const string Wordpress
     */
    const PLUGIN_CODE = 'WP';

    /**
     * @const string Bux.ph base api URL
     */
    const API_BASE_URL = 'https://api.bux.ph';

    /**
     * @var string The Default App Key to use with the request
     */
    protected $app_key;

    /**
     * @var string The Default Client ID to use with the request
     */
    protected $client_id;

    /**
     * @var string The Default Client Secret to use with the request
     */
    protected $client_secret;

    /**
     * @var string The Default API version we want to use
     */
    protected $version;

    /**
     * @var string The Default Auth Token to use with the request. This will over-ride the App Key settings
     */
    protected $auth_token;

    /**
     * @const string Minimum Amount that we can charge per transaction
     */
    const AMOUNT_MIN = '50';

    /**
     * @const string Maximum Amount that we can charge per transaction
     */
    const AMOUNT_MAX = '30000';

    /**
     * @const string Minimum Hours before the transaction will expire
     */
    const EXPIRY_HOURS_MIN = '2';

    /**
     * @const string Maximum Hours before the transaction will expire
     */
    const EXPIRY_HOURS_MAX = '168';

    /**
     * @const string Default Hours before the transaction will expire
     */
    const EXPIRY_HOURS_DEFAULT = '8';

    /**
     * @const string The default fee will be charge per transaction
     */
    const TXN_FEE_DEFAULT = '20';

    /**
     * @const array List of possible payment status.
     */
    const PAYMENT_STATUS = [
        'wc-pending',
        'wc-processing',
        'wc-on-hold',
        'wc-completed',
        'wc-cancelled',
        'wc-refunded',
        'wc-failed',
    
        'wc-pending-bux',
        'wc-paid-bux',
    
        'wc-pre-shipping',
        'wc-pre-transit',
        'wc-in-transit',
        'wc-delivered'
    ];

    /**
     * @var string The Expiry hours we want to use in our transactions
     */
    protected $expiry_hours;

    /**
     * @var string The Transaction fee we want to use in our transactions
     */
    protected $txn_fee;

    /**
     * @var string The URL we want to use for our Instant Payment Notification
     */
    protected $ipn_url;
    
    /**
     * Instantiates a new Bux super-class object.
     *
     * @param array $config
     *
     * @throws BuxException
     */
    public function __construct(array $config = [])
    {
        $config = array_merge([
            'app_key' => getenv(static::APP_KEY_ENV_NAME),
            'client_id' => getenv(static::CLIENT_ID_ENV_NAME),
            'client_secret' => getenv(static::CLIENT_SECRET_ENV_NAME),
            'expiry_hours' => static::EXPIRY_HOURS_DEFAULT,
            'txn_fee' => static::TXN_FEE_DEFAULT,
            'ipn_url' => null,
            'version' => 'v1'
        ], $config);

        if(empty($config['app_key']))
            throw new Exceptions\BuxException('Required "app_id" key not supplied in config and could not find fallback environment variable "' . static::APP_KEY_ENV_NAME.'"');

        if(empty($config['client_id']))
            throw new Exceptions\BuxException('Required "client_id" key not supplied in config and could not find fallback environment variable "' . static::CLIENT_ID_ENV_NAME.'"');

        if(empty($config['client_secret']))
            throw new Exceptions\BuxException('Required "client_secret" key not supplied in config and could not find fallback environment variable "' . static::CLIENT_SECRET_ENV_NAME.'"');

        $this->app_key = $config['app_key'];
        $this->client_id = $config['client_id'];
        $this->client_secret = $config['client_secret'];

        $this->expiry_hours = $config['expiry_hours'];
        $this->txn_fee = $config['txn_fee'];
        $this->ipn_url = $config['ipn_url'];
        $this->version = $config['version'];
    }

    /**
     * Generate a payment link
     *
     * @param array $request
     * @param bool $return_array
     *
     * @return array|object
     */
    public function paymentRequest(array $request, bool $return_array = true)
    {

        $request = array_merge([
            'amount' => null,
            'fee' => $this->txn_fee,
            'description'=> null,
            'order_id'=> null,
            'email' => null,
            'phone' => null,
            'name' => null,
            'expiry' => $this->expiry_hours,
            'notification_url' => null,
        ], $request);

        $request['client_id'] = $this->client_id;

        return $this->apiRequest('POST', 'woocommerce/checkout', $request, $return_array);
    }

    /**
     * Get payment info
     *
     * @param string $id
     * @param bool $return_array
     *
     * @return array|object
     */
    public function getPaymentInfo(string $id, bool $return_array = true)
    {
        return $this->apiRequest('GET', "payment/{$id}", [], $return_array);
    }

    /**
     * Check Payment Code
     *
     * @param string $order_id
     * @param string $description
     * @param bool $return_array
     *
     * @return array|object
     */
    public function checkPaymentCode(string $order_id, string $description = null, bool $return_array = true)
    {
        return $this->apiRequest('POST', 'check_code', [
            'description'=> $description,
            'order_id'=> $order_id,
            'mode'=> static::PLUGIN_CODE,
            'client_id' => $this->client_id
        ], $return_array);
    }

    /**
     * get checkout client url
     *
     * @param string $id
     * @param string $redirect_url
     *
     * @return string
     */
    public function checkoutUrl(string $id, string $redirect_url = null) : string
    {
        return 'https://bux.ph/checkout/'.$id.(!empty($redirect_url) ? '/?redirect_url='.urlencode($redirect_url) : '');
    }

    /**
     * validate IPN message
     *
     * @param array $param
     *
     * @return bool
     */
    public function isValidMessage(array $param) : bool
    {

        if (
            $param['client_id'] !== $this->client_id ||
            $param['signature'] !== sha1($param['order_id'].$param['status']."{".$this->client_secret."}")
        ) 
        return false;

        return true;
    }

    /**
     * API Request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @param bool $return_array
     *
     * @return array|object
     */
    public function apiRequest(string $method, string $endpoint, array $params = [], bool $return_array = true)
    {
        $c = curl_init();

        $opt = [
            CURLOPT_URL => implode('/', [
                static::API_BASE_URL, $this->version, 'api', $endpoint
            ]).'/',
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json'
            ],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_FAILONERROR => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_RESOLVE => []
        ];

        if(!empty($this->auth_token)) {
            $opt[CURLOPT_HTTPHEADER][] = 'authorization: Token '.$this->auth_token;
        } else if(!empty($this->app_key)) {
            $opt[CURLOPT_HTTPHEADER][] = 'x-api-key: '.$this->app_key;
        }

        $supported_methods = ['GET', 'POST', 'DELETE', 'PUT', 'UPDATE'];
        if(!in_array($method, $supported_methods))
            throw new Exceptions\BuxApiRequestException('Method is not supported ('.implode(',', $supported_methods).')');

        if($method === 'POST') {
            $opt[CURLOPT_POSTFIELDS] = (is_array($params) || is_object($params) ? json_encode($params) : $params); // support older php version
        }

        $opt[CURLOPT_CUSTOMREQUEST] = $method;

        curl_setopt_array($c, $opt);

        $d = curl_exec($c);
        curl_close($c);
        
        return json_decode($d, $return_array);
    }
}
