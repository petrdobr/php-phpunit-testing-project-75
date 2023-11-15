<?php
namespace App;
use GuzzleHttp\Client;
class Handler
{
    private $args;
    public function __construct($args)
    {
        $this->args = $args;
    }

    public function echo()
    {
        $client = new Client(['base_uri' => 'http://hexlet.io/']);
        print_r($this->args);
        $response = $client->request('GET', '');
        return $response->getBody();
    }

    public function handler()
    {
        //code
    }
}