<?php

require '../vendor/autoload.php';

use Marcialpaulg\BuxPhp\Bux;

$bux = new Bux([
    'auth_token' => '{auth-token}'                                                          // your session token from bux.ph
]);

// Create Request Payment
$payment_request = $bux->generatePaymentLink([
    'amount' => '1000',                                                                     // amount that we want to charge
    'description'=> 'For the followers',                                                    // payment description
    'email' => 'im.codename@gmail.com',                                                     // client's email address
    'name' => 'Marcial Paul Gargoles',                                                      // client's full name
    'phone' => '09270000000',                                                               // client's mobile/phone number
    'expiry' => '2',                                                                        // expire time in hours for this transaction
]);

var_dump($payment_request);
