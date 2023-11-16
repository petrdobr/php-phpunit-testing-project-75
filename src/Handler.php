<?php
namespace App;
use GuzzleHttp\Client;
class Handler
{
    private $args;
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    public function handleOptions(): void
    {
        $helpOptions = ['-h', '--help', '--h', '?'];
        $helpMessage = 'text will come' . PHP_EOL;
        if (count($this->args) == 1 or in_array($this->args[1], $helpOptions)) {
            echo $helpMessage;
        } else {
            //add check if this is url?
            $parsedURL = parse_url($this->args[1]);
            //make new connect, call a function to connect;
            //make a filename from the parsed url;
            //check if option -o is passed in
            //if yes save file to a new directory
            //if no save file to current directory
            //(how to get rid of if statements and allow to expand the commands easily?)
        }
    }

    public function makeRequest($connect, $options)
    {

    }

    public function echo()
    {
        /*
        $client = new Client(['base_uri' => 'http://hexlet.io/']);
        print_r($this->args);
        $response = $client->request('GET', '');
        return $response->getBody();
        */
    }

    public function handler()
    {
        //code
    }
}