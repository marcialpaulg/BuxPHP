<?php

require '../vendor/autoload.php';

use Marcialpaulg\BuxPhp\Bux;

$bux = new Bux([
    'auth_token' => '{auth-token}'              // your session token from bux.ph
]);

// get user payout profiles
$payout_profiles = $bux->payoutProfiles();

// get user info
$get_info = $bux->getInfo();

// get payment settings
$get_payment_settings = $bux->getPaymentSettings();

// get user transaction summary
$get_transaction_summary = $bux->getTransactionSummary();

// get user available balance
$get_available_balance = $bux->getAvailableBalance();

var_dump($payout_profiles, $get_info, $get_payment_settings, $get_transaction_summary, $get_available_balance);
