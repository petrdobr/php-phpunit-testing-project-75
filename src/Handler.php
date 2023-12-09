<?php
namespace App;

use GuzzleHttp\Client;
use DiDom\Document;

class Handler
{
    public array $configs = [
        'defaultPath' => '/home/galiia/hex/php-unit-project', // default path to download file in
        'helpMessage' => 'help text will come' . PHP_EOL,
        'versionMessage' => 'Page Loader version 0.2b' . PHP_EOL
    ];
    private array $args;
    private string $fileName; // example: google-com.html
    private string $urlToDownload = ""; // example: http://google.com
    private string $filePath; // includes filename, example: /home/project/google-com.html
    private string $directory; // does not include filename, example: /home/project
    private array $supplementaryFilesPaths = []; //example: /home/project/google-com_files/path-image.jpg
    
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

        //set directory and full filepath
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
                $this->filePath = $this->directory  . '/' . $this->fileName;
            }
        }
        //TODO add another if-statement for if args2 isset and not in sec options array, display error msg
        return true; //all options are good, can continue
    }

    public function downloadPage(string $url, string $directory, Client $client): void
    {
        $dataFromURL = $client->get($url)->getBody()->getContents();

        //make paths for supplementary contents
        $fileRelativePath = str_replace('.html', '', $this->fileName) . '_files'; // example: google-com_files
        $filesPath = $directory . '/' . $fileRelativePath; //example: /home/project/google-com_files

        //work with additional files;
        $doc = new Document($dataFromURL);
        $images = $doc->find('img');
        if ($images != []) {
            if (!file_exists($filesPath)) {
                mkdir($filesPath);
            }
        }
        foreach ($images as $element) {
            $fileRelativeURL = $element->getAttribute('src'); // example: path/image.jpg
            if (str_contains($fileRelativeURL, 'http')) { 
                $parsedFileURL = parse_url($fileRelativeURL);
                $urlToDownloadFile = $fileRelativeURL; // example: http://google.com/path/image.jpg
                $fileRelativeURL = str_replace($parsedFileURL['scheme'] . '://' . $parsedFileURL['host'], '', $fileRelativeURL);
            } else {
                $urlToDownloadFile = $url . $fileRelativeURL; // example: http://google.com/path/image.jpg
            }
            $newFilePath = $filesPath . '/' . str_replace('/', '-', trim($fileRelativeURL, '/')); // example: /home/project/google-com_files/path-image.jpg
            $newFileRelativePath = $fileRelativePath . '/' . str_replace('/', '-', trim($fileRelativeURL, '/')); // example: google-com_files/path-image.jpg
            $element->setAttribute('src', $newFileRelativePath); // change img src value in the HTML doc

            $this->supplementaryFilesPaths[] = $newFilePath; // for tests

            $this->downloadImages($urlToDownloadFile, $newFilePath, $client);

        } 
        $changedDataFromURL = $doc->html();
        $filePath = $directory . '/' . $this->fileName;
        file_put_contents($filePath, $changedDataFromURL); 
        echo "Page was successfully downloaded into " . $filePath . PHP_EOL;   
    }

    public function downloadImages($url, $filePath, Client $client)
    {
        try {
            $response = $client->request('GET', $url);
            file_put_contents($filePath, $response);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getUrl(): string
    {
        return $this->urlToDownload;
    }

    public function getSupplementaryFilesPaths(): array
    {
        return $this->supplementaryFilesPaths;
    }
}