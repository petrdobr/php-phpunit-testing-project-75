#!/usr/bin/env php
<?php
namespace App;
use App\Handler;
use GuzzleHttp\Client;

require __DIR__ . '/../vendor/autoload.php';

//Make an object to handle the command. Pass in the options. Handle options
$handler = new Handler();
$handler->setArgs($argv);
if ($handler->handleOptions()) {
    //make new client, download page
    $connect = new Client();
    $clientClass = $connect::class;
    $handler->downloadPage($connect, $clientClass);
}