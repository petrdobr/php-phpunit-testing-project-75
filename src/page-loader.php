<?php
namespace App;
use App\Handler;

require 'Handler.php';
require '../vendor/autoload.php';

//make an object to handle the 
$handler = new Handler($argv);
echo $handler->echo();


