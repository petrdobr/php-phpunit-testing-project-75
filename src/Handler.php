<?php
namespace App;
use GuzzleHttp\Client;
class Handler
{
    private array $args;
    private string $helpMessage = 'help text will come' . PHP_EOL;
    private string $versionMessage = 'version text will come' . PHP_EOL;
    private string $fileName;
    private string $urlToDownload = "";
    private string $fileDirectory = __DIR__;
    
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    public function handleOptions()
    {
        $optionsBasic = [
            '-h' => $this->helpMessage,
            '--help' => $this->helpMessage,
            '?' => $this->helpMessage,
            '-v' => $this->versionMessage,
            '--version' => $this->versionMessage 
        ];
        //if an option was entered from the list above then echo message and exit;
        if (array_key_exists($this->args[1], $optionsBasic)) {
            echo $optionsBasic[$this->args[1]];
            return false;
        }
        //try to parse the URL
        $parsedURL = parse_url($this->args[1]);
        //if parse_url couldn't parse the input it will return false and so we can stop here.
        if (!$parsedURL) {
            echo "This URL does not seem to be correct, please try again.";
            return false;
        }
        //store the URL to properties
        $this->urlToDownload = $this->args[1];
        //construct filename
        $parsedBody = str_replace('.', '-', $parsedURL["host"]);
        $parsedPath = str_replace('/', '-', $parsedURL["path"]);
        $this->fileName = $parsedBody . $parsedPath . '.html';

        //handle directory option
        $optionsSecondary = ['-o', '--output'];
        if (isset($this->args[2]) and in_array($this->args[2], $optionsSecondary)) {
            $this->fileDirectory = __DIR__ . $this->args[3];
        }
        return true; //all options are good, can continue
    }

    public function downloadPage(Client $connect, $clientClass)
    {
        $dataFromURL = $connect->get($this->urlToDownload)->getBody()->getContents();
        $filePath = $this->fileDirectory . '/' . $this->fileName;
        file_put_contents($this->fileName, $dataFromURL);
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getFileDirectory()
    {
        return $this->fileDirectory;
    }
}