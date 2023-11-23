<?php
namespace App;

use GuzzleHttp\Client;
use DiDom\Document;

class Handler
{
    private array $args;
    private string $helpMessage = 'help text will come' . PHP_EOL;
    private string $versionMessage = 'Page Loader version 0.2b' . PHP_EOL;
    private string $fileName;
    private string $urlToDownload = "";
    private string $filePath; // includes filename
    private string $directory; // does not include filename
    
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    public function handleOptions(): bool
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
        //TODO: check for protocol http (in if statement above)
        $parsedBody = isset($parsedURL["host"]) ? str_replace('.', '-', $parsedURL["host"]) : "";
        $parsedPath = isset($parsedURL["path"]) ? str_replace('/', '-', $parsedURL["path"]) : ""; //TODO: add trim for '/' 
        $this->fileName = $parsedBody . $parsedPath . '.html';

        //set default path
        $this->directory = realpath("/home/hex/php-unit-project");
        $this->filePath = $this->directory . '/' . $this->fileName;

        //handle directory option
        $optionsSecondary = ['-o', '--output'];
        if (isset($this->args[2]) and in_array($this->args[2], $optionsSecondary)) {
            //if new directory was not passed in with the option then just ignore
            if (isset($this->args[3])) {
                $this->directory = realpath("/home/hex/php-unit-project") . $this->args[3];
                //check if it's already exists
                if (!file_exists($this->directory)) {
                    mkdir(realpath("/home/hex/php-unit-project") . $this->args[3]);
                }
                $this->filefilePath = $this->directory  . '/' . $this->fileName;
            }
        }

        return true; //all options are good, can continue
    }

    public function downloadPage(string $url, string $filePath, Client $client): void
    {
        $dataFromURL = $client->get($url)->getBody()->getContents();

        //make paths for supplementary contents
        $fileRelativePath = str_replace('.html', '', $this->fileName) . '/_files';
        $filesPath = $this->directory . $fileRelativePath;

        //work with contents of the page;
        $doc = new Document($dataFromURL);
        $fileNames = [];
        $pngImages = $doc->find('img[src$=png]');
        foreach ($pngImages as $element) {
            $fileName = $element->getAttribute('src');
            $fileName = explode('/', $fileName);
            var_dump($fileName[-1]);
            $fileNames[] = $fileName[-1];
            $newFilePath = $fileRelativePath . $fileName;
            $element->setAttribute('src', $newFilePath);
        }
        $jpgImages = $doc->find('img[src$=jpg]');
        //download
        foreach ($fileNames as $file) {
            $pathToDownloadFile = str_replace($this->fileName, '', $url) . $file;
            $client->request('GET', $pathToDownloadFile, ['sink' => $filesPath]);
        }
        file_put_contents($filePath, $dataFromURL);
        echo "Page was successfully downloaded into " . $filePath . PHP_EOL;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getUrl(): string
    {
        return $this->urlToDownload;
    }
}