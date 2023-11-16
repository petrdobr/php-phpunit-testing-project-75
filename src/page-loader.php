#!/usr/bin/env php
<?php
namespace App;
use App\Handler;

require __DIR__ . '/../vendor/autoload.php';

//Make an object to handle the command. Pass in the options. Handle options
$handler = new Handler();
$handler->setArgs($argv);
$handler->handleOptions();