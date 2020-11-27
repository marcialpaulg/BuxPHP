<?php

require '../vendor/autoload.php';

use marcialpaulg\BuxPh\Bux;

$bux = new Bux([
    'app_key' => '{app-key}',
    'client_id' => '{client-id}',
    'client_secret' => '{client-secret}'
]);

if(
    empty($_POST['client_id']) ||
    empty($_POST['signature']) ||
    empty($_POST['order_id']) ||
    empty($_POST['status'])
) die();

if($bux->isValidMessage([
    'client_id' => $_POST['client_id'],
    'signature' => $_POST['signature'],
    'order_id' => $_POST['order_id'],
    'status' => $_POST['status']
]) !== true) die();

// valid IPN message
// do more below
