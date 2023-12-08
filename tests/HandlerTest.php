#!/usr/bin/env php
<?php

namespace App\Tests;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;
use App\Handler;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamPrintVisitor;

class HandlerTest extends TestCase
{
    private $client;
    private Handler $handler;
    private $mockResponse;
    private $streamObject;
    private $root;
    private array $args;
    private string $stubInitialData;
    private string $stubChangedData;

    public function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->streamObject = $this->createMock(StreamInterface::class);
        $this->handler = new Handler();
        $this->root = vfsStream::setup('/home/galiia/hex/php-unit-project');

        //create stub with fake data and imitate chain of methods to get fake webpage content
        $this->stubInitialData = file_get_contents(realpath(__DIR__ . '/fixtures/testFile.html'));
        $this->stubChangedData = file_get_contents(realpath(__DIR__ . '/fixtures/changedFile.html'));
        $this->client->method('get')->willReturn($this->mockResponse);
        $this->mockResponse->method('getBody')->willReturn($this->streamObject);
        $this->streamObject->method('getContents')->willReturn($this->stubInitialData);

        $this->client->method('request')->willReturn($this->mockResponse);
    }

    public function testHelpOutput(): void
    {
        $args = ['page-loader.php', '-v'];
        $this->handler->setArgs($args);
        $helpMessage = 'Page Loader version 0.2b' . PHP_EOL;
        $this->handler->handleOptions();
        $this->expectOutputString($helpMessage);
    }

    public function testOtherOptions(): void
    {
        //test URL parsing
        $args1 = ['page-loader.php', 'http://hexlet.io/page/com/lala?key=value&stuff=1010'];
        $this->handler->setArgs($args1);
        $expectedFileName = 'hexlet-io-page-com-lala.html';
        $this->handler->handleOptions();
        $this->assertEquals($expectedFileName, $this->handler->getFileName());

        $args4 = ['page-loader.php', 'http://hexlet.io'];
        $this->handler->setArgs($args4);
        $expectedFileName = 'hexlet-io.html';
        $this->handler->handleOptions();
        $this->assertEquals($expectedFileName, $this->handler->getFileName());

        //test default directory
        $args2 = ['page-loader.php', 'http://hexlet.io/page/com'];
        $this->handler->setArgs($args2);
        $this->handler->handleOptions();
        $expectedDirectory = '/home/galiia/hex/php-unit-project/hexlet-io-page-com.html';
        $this->assertEquals($expectedDirectory, $this->handler->getfilePath());

        //test passed in directory
        $args3 = ['page-loader.php', 'http://hexlet.io/page/com', '-o', '/tmp'];
        $this->handler->setArgs($args3);
        $this->handler->handleOptions();
        $expectedDirectory = '/home/galiia/hex/php-unit-project/tmp/hexlet-io-page-com.html';
        $this->assertEquals($expectedDirectory, $this->handler->getfilePath());
    }

    public function testDownloadPage(): void
    {
        //pass in parameters with default path
        $args4 = ['page-loader.php', 'http://hexlet.io/page/com'];
        $this->handler->setArgs($args4);
        $this->handler->handleOptions();

        //get URL from args
        $url1 = $this->handler->getUrl();

        //download page to the fake virtual disk
        $filePath1 = vfsStream::url('home/galiia/hex/php-unit-project'); //. '/' . $this->handler->getFileName();
        $this->handler->downloadPage($url1, $filePath1, $this->client);

        vfsStream::inspect(new vfsStreamPrintVisitor());

        $fullFilePath1 = $filePath1 . '/' . $this->handler->getFileName();
        
        //check for file created at a passed in directory
        $this->assertFileExists($fullFilePath1);

        //check for contents to be correct
        $this->assertStringEqualsFile($fullFilePath1, $this->stubChangedData);

        //check for images to be downloaded
        $imageStubURL = 'http://hexlet.io/images/logos/logo.png'; // doesn't matter what url is here

        foreach ($this->handler->getSupplementaryFilesPaths() as $file) {
            echo $file . PHP_EOL; // check names
            $this->handler->downloadImages($imageStubURL, $file, $this->client);
            vfsStream::inspect(new vfsStreamPrintVisitor());
            $this->assertFileExists($file);
        }
/*
        //new args with a new directory passed in and new options
        $args5 = ['page-loader.php', 'http://hexlet.io/page/com', '-o', '/tmp'];
        $this->handler->setArgs($args5);
        $this->handler->handleOptions();
        $newfilePath = vfsStream::url('home/galiia/hex/php-unit-project' . '/tmp');
        mkdir($newfilePath);
        $filePath2 = $newfilePath . '/' . $this->handler->getFileName();
        $url2 = $this->handler->getUrl();

        $this->handler->downloadPage($url2, $filePath2, $this->client);
        $this->assertFileExists($filePath2);
        $this->assertStringEqualsFile($filePath2, $this->stubChangedData);
*/        
    }

    public function tearDown(): void
    {
        unset($this->root);
    }
}