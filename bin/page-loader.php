<?php

namespace Downloader\Downloader;

use Hexlet\Code\Handler;
use GuzzleHttp\Client;

require_once __DIR__ . '/../vendor/autoload.php';
if (! function_exists(__NAMESPACE__ . '\downloadPage')) {
    $url = $argv[1];
    $directory = '/home/petr/tmp';
    $client = new Client();
    $handler = new Handler();
    $handler->setArgs($argv);
    if ($handler->handleOptions()) {
        //make new client, download page
        $url = $handler->getUrl();
        $directory = $handler->getDirectory();
        downloadPage($url, $directory, $client);
    }

    function downloadPage(string $url, string $directory, Client $client)
    {
        $handler = new Handler();
        $args = ['page-loader', $url, '-o', $directory];
        $handler->setArgs($args);
        $url = $handler->getUrl();
        $handler->downloadPage($url, $directory, $client);
    }
}
