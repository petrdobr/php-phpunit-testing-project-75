<?php
namespace App;

use GuzzleHttp\Client;
use DiDom\Document;

class Handler
{
    private array $configs = [
        'defaultPath' => '/home/galiia/hex/php-unit-project',
        'helpMessage' => 'help text will come' . PHP_EOL,
        'versionMessage' => 'Page Loader version 0.2b' . PHP_EOL
    ];
    private array $args;
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
            '-h' => $this->configs['helpMessage'],
            '--help' => $this->configs['helpMessage'],
            '?' => $this->configs['helpMessage'],
            '-v' => $this->configs['versionMessage'],
            '--version' => $this->configs['versionMessage'] 
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
        $this->directory = $this->configs['defaultPath'];
        $this->filePath = $this->directory . '/' . $this->fileName;

        //handle directory option
        $optionsSecondary = ['-o', '--output'];
        if (isset($this->args[2]) and in_array($this->args[2], $optionsSecondary)) {
            //if new directory was not passed in with the option then just ignore
            if (isset($this->args[3])) {
                $this->directory = $this->configs['defaultPath'] . $this->args[3];
                //check if it's already exists
                if (!file_exists($this->directory)) {
                    mkdir($this->configs['defaultPath'] . $this->args[3]);
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
        $fileRelativePath = str_replace('.html', '', $this->fileName) . '_files';
        $filesPath = $this->directory . '/' . $fileRelativePath;
        if (!file_exists($filesPath)) {
            mkdir($filesPath);
            chmod($filesPath, 0777);
        }

        //work with additional files of the page;
        $doc = new Document($dataFromURL);
/*        $pngImages = $doc->find('img[src$=png]');
        $jpgImages = $doc->find('img[src$=jpg]');
        $images = array_merge($pngImages, $jpgImages);*/
        $images = $doc->find('img');
        foreach ($images as $element) {
            $fileURL = $element->getAttribute('src');
            if (str_contains($fileURL, 'http')) {
                $parsedFileURL = parse_url($fileURL);
                $pathToDownloadFile = $fileURL;
                $fileURL = str_replace($parsedFileURL['scheme'] . '://' . $parsedFileURL['host'], '', $fileURL);
            } else {
                $pathToDownloadFile = $url . $fileURL;
            }
            $newFilePath = $filesPath . '/' . str_replace('/', '-', trim($fileURL, '/'));
            $newFileRelativePath = $fileRelativePath . '/' . str_replace('/', '-', trim($fileURL, '/')); //to add into the document (see TODO)
            $element->setAttribute('src', $newFileRelativePath);

            try {
                $client->request('GET', $pathToDownloadFile, ['sink' => $newFilePath]);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        } 
        $changedDataFromURL = $doc->html();
        file_put_contents($filePath, $changedDataFromURL); 
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