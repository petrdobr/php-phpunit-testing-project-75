<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use DiDom\Document;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

class Handler
{
    private array $configs = [
        'defaultPath' => '/home/petr/tmp', // default path to download file in
        'helpMessage' => 'help text will come' . PHP_EOL,
        'versionMessage' => 'Page Loader version 0.2b' . PHP_EOL
    ];
    private Logger $logger;
    private array $args;
    private string $webPageHTML;
    private bool $isSetCanonical = false;
    private string $fileName; // example: google-com-page.html
    private string $hostName; // example: google-com
    private string $urlToDownload = ""; // example: http://google.com/page
    private string $filePath; // includes filename, example: /home/project/google-com-page.html
    private string $directory; // does not include filename, example: /home/project
    private array $imagesPaths = []; //example: /home/project/google-com-page_files/google-com-path-image.jpg
    private array $filesPaths = []; //example: /home/project/google-com-page_files/google-com-path-script.js
    
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    public function handleOptions(): bool
    {

        //handle option
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
        $this->hostName = $parsedBody;

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
        $stream = new StreamHandler($directory . '/main.log', Level::Debug);
        $this->logger = new Logger('page-loader');
        $this->logger->pushHandler($stream);
        $this->logger->pushProcessor(new PsrLogMessageProcessor());

        $this->webPageHTML = $client->get($url)->getBody()->getContents();

        $filesDirectory = $directory . '/' . str_replace('.html', '', $this->fileName) . '_files'; //example: /home/project/google-com_files

        $this->downloadImages($url, $filesDirectory, $client);
        $this->downloadFiles($url, $filesDirectory, $client);

        $filePath = $directory . '/' . $this->fileName;
        file_put_contents($filePath, $this->webPageHTML);
        if ($this->isSetCanonical) {
            $filePath = $filesDirectory . '/' . $this->fileName;
            file_put_contents($filePath, $this->webPageHTML);
        }
        $this->logger->notice('Webpage was successfully downloaded');
        echo "Page was successfully downloaded into " . $filePath . PHP_EOL;
    }

    public function downloadImages($url, $filesDirectory, $client)
    {
        $filesRelativeDirectory = str_replace('.html', '', $this->fileName) . '_files'; // example: google-com_files
        $doc = new Document($this->webPageHTML);
        $images = $doc->find('img');
        if ($images != []) {
            if (!file_exists($filesDirectory)) {
                mkdir($filesDirectory);
                $this->logger->info('New directory was created: {dir}', ['dir' => $filesDirectory]);
            }
        }
        foreach ($images as $element) {
            $fileRelativeURL = $element->getAttribute('src'); // example: path/image.jpg
            if (str_contains($fileRelativeURL, 'http')) { 
                $urlToDownloadFile = $fileRelativeURL; // example: http://google.com/path/image.jpg
                $parsedFileURL = parse_url($fileRelativeURL);
                $fileRelativeURL = str_replace($parsedFileURL['scheme'] . '://' . $parsedFileURL['host'], '', $fileRelativeURL);
            } else {
                $urlToDownloadFile = $url . $fileRelativeURL; // example: http://google.com/path/image.jpg
            }
            //new file name example: google-com-path-image.jpg
            $newFileName = $this->hostName . '-' . str_replace('/', '-', trim($fileRelativeURL, '/'));
            $newFilePath = $filesDirectory . '/' . $newFileName; // example: /home/project/google-com_files/google-com-path-image.jpg
            $newFileRelativePath = $filesRelativeDirectory . '/' . $newFileName; // example: google-com_files/google-com-path-image.jpg
            $element->setAttribute('src', $newFileRelativePath); // change img src value in the HTML doc

            $this->imagesPaths[] = $newFilePath; // for tests

            try {
                $response = $client->request('GET', $urlToDownloadFile);
                file_put_contents($newFilePath, $response);
                $this->logger->info('Additional file {file} was successfully downloaded', ['file' => $newFilePath]);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                $this->logger->warning('Fail to download additional file {URL};' . PHP_EOL . 'msg', ['URL' => $urlToDownloadFile,
            'msg' => $e->getMessage()]);
            }
            
            $this->webPageHTML = $doc->html();
            $this->logger->info('<img> tags in the requested webpage were modified');
        } 

    }

    public function downloadFiles($url, $filesDirectory, $client)
    {
        $filesRelativeDirectory = str_replace('.html', '', $this->fileName) . '_files'; // example: google-com_files
        $doc = new Document($this->webPageHTML);
        $links = $doc->find('link');
        $scripts = $doc->find('script');
        if ($links != [] or $scripts != []) {
            if (!file_exists($filesDirectory)) {
                mkdir($filesDirectory);
                $this->logger->info('New directory was created: {dir}', ['dir' => $filesDirectory]);
            }
        }
        foreach ($links as $element) {
            $fileRelativeURL = $element->getAttribute('href'); // example: path/image.jpg
            if (str_contains($fileRelativeURL, 'http')) { 
                $urlToDownloadFile = $fileRelativeURL; // example: http://google.com/path/image.jpg
                $parsedFileURL = parse_url($fileRelativeURL);
                $fileRelativeURL = str_replace($parsedFileURL['scheme'] . '://' . $parsedFileURL['host'], '', $fileRelativeURL);
            } else {
                $urlToDownloadFile = $url . $fileRelativeURL; // example: http://google.com/path/image.jpg
            }
            if ($element->getAttribute('rel') == 'canonical') {
                $this->isSetCanonical = true;
                $element->setAttribute('href', $filesRelativeDirectory . '/' . $this->fileName);
                continue;
            }
            //new file name example: google-com-path-image.jpg
            $newFileName = $this->hostName . '-' . str_replace('/', '-', trim($fileRelativeURL, '/'));
            $newFilePath = $filesDirectory . '/' . $newFileName; // example: /home/project/google-com_files/google-com-path-image.jpg
            $newFileRelativePath = $filesRelativeDirectory . '/' . $newFileName; // example: google-com_files/google-com-path-image.jpg
            $element->setAttribute('href', $newFileRelativePath); // change img src value in the HTML doc

            $this->filesPaths[] = $newFilePath; // for tests

            try {
                $response = $client->request('GET', $urlToDownloadFile);
                file_put_contents($newFilePath, $response);
                $this->logger->info('Additional file {file} was successfully downloaded', ['file' => $newFilePath]);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                $this->logger->warning('Fail to download additional file {URL};' . PHP_EOL . 'msg', ['URL' => $urlToDownloadFile,
                'msg' => $e->getMessage()]);
            }
        }

        foreach ($scripts as $element) {
            $fileRelativeURL = $element->getAttribute('src'); // example: path/image.jpg
            if (str_contains($fileRelativeURL, 'http')) { 
                $urlToDownloadFile = $fileRelativeURL; // example: http://google.com/path/image.jpg
                $parsedFileURL = parse_url($fileRelativeURL);
                $fileRelativeURL = str_replace($parsedFileURL['scheme'] . '://' . $parsedFileURL['host'], '', $fileRelativeURL);
            } else {
                $urlToDownloadFile = $url . $fileRelativeURL; // example: http://google.com/path/image.jpg
            }
             //new file name example: google-com-path-image.jpg
             $newFileName = $this->hostName . '-' . str_replace('/', '-', trim($fileRelativeURL, '/'));
             $newFilePath = $filesDirectory . '/' . $newFileName; // example: /home/project/google-com_files/google-com-path-image.jpg
             $newFileRelativePath = $filesRelativeDirectory . '/' . $newFileName; // example: google-com_files/google-com-path-image.jpg
             $element->setAttribute('src', $newFileRelativePath); // change img src value in the HTML doc

            $this->filesPaths[] = $newFilePath; // for tests

            try {
                $response = $client->request('GET', $urlToDownloadFile);
                file_put_contents($newFilePath, $response);
                $this->logger->info('Additional file {file} was successfully downloaded', ['file' => $newFilePath]);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                $this->logger->warning('Fail to download additional file {URL};' . PHP_EOL . 'msg', ['URL' => $urlToDownloadFile,
                'msg' => $e->getMessage()]);
            }
        }
        
        $this->webPageHTML = $doc->html();
        $this->logger->info('<link> and <script> tags in the requested webpage were modified');
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

    public function getImagesPaths(): array
    {
        return $this->imagesPaths;
    }

    public function getFilesPaths(): array
    {
        return $this->filesPaths;
    }
}