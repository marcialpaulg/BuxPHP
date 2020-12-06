<?php

require '../vendor/autoload.php';

use Marcialpaulg\BuxPhp\Bux;

$bux = new Bux([
    'app_key' => '{app-key}',
    'client_id' => '{client-id}',
    'client_secret' => '{client-secret}',
    'expiry_hours' => '12',                                                                 // will expire in 12 hours
    'txn_fee' => '20',                                                                      // Transaction fee per transaction
]);

// Create Request Payment
$payment_request = $bux->paymentRequest([
    'amount' => '1000',                                                                     // amount that we want to charge
    'description'=> 'For Web service',                                                      // payment description
    'order_id'=> '5001904',                                                                 // set your order id here
    'email' => 'im.codename@gmail.com',                                                     // client's email address
    'phone' => '09270000000',                                                               // client's mobile/phone number
    'name' => 'Marcial Paul Gargoles',                                                      // client's full name
    'expiry' => '2',                                                                        // expire time in hours for this transaction
    'fee' => '20',                                                                          // transaction fee for this transaction
    'notification_url' => $bux->muteDefaultWCIPN('https://example.com/api/ipn/buxph')       // IPN url for this transaction
]);

// checkout URL for the payee
$checkout_url = $bux->checkoutUrl($payment_request['uid']);

// get payment info
$payment_info = $bux->getPaymentInfo($payment_request['uid']);

var_dump($payment_request, $checkout_url, $payment_info);
