#!/usr/bin/env php
<?php

namespace Hexlet\Code\Tests;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;
use Hexlet\Code\Handler;
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
        //instantiate necessary objects
        $this->client = $this->createMock(Client::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->streamObject = $this->createMock(StreamInterface::class);
        $this->handler = new Handler();
        $this->root = vfsStream::setup('/home/petr/tmp');

        //create stubs with fake data
        $this->stubInitialData = file_get_contents(realpath(__DIR__ . '/fixtures/testFile.html'));
        $this->stubChangedData = file_get_contents(realpath(__DIR__ . '/fixtures/changedFile.html'));

        //imitate methods to get fake webpage content
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

        $args4 = ['page-loader.php', 'http://ru.hexlet.io'];
        $this->handler->setArgs($args4);
        $expectedFileName = 'ru-hexlet-io.html';
        $this->handler->handleOptions();
        $this->assertEquals($expectedFileName, $this->handler->getFileName());

        //test default directory
        $args2 = ['page-loader.php', 'http://hexlet.io/page/com'];
        $this->handler->setArgs($args2);
        $this->handler->handleOptions();
        $expectedDirectory = '/home/petr/tmp/hexlet-io-page-com.html';
        $this->assertEquals($expectedDirectory, $this->handler->getfilePath());

        //test passed in directory
        $args3 = ['page-loader.php', 'http://hexlet.io/page/com', '-o', '/new'];
        $this->handler->setArgs($args3);
        $this->handler->handleOptions();
        $expectedDirectory = '/home/petr/tmp/new/hexlet-io-page-com.html';
        $this->assertEquals($expectedDirectory, $this->handler->getfilePath());
    }

    public function testDownloadPage(): void
    {
        //pass in parameters (default path)
        $args4 = ['page-loader.php', 'http://ru.hexlet.io/courses'];
        $this->handler->setArgs($args4);
        $this->handler->handleOptions();

        //get URL from args
        $url1 = $this->handler->getUrl();

        //download page to the fake virtual disk
        $directory1 = vfsStream::url('home/petr/tmp'); 
        $this->handler->downloadPage($url1, $directory1, $this->client);

        $fullFilePath1 = $directory1 . '/' . $this->handler->getFileName();
        
        //check for file created at a passed in directory
        $this->assertFileExists($fullFilePath1);

        //check for contents to be correct
        $this->assertStringEqualsFile($fullFilePath1, $this->stubChangedData);

        //check that images were downloaded
        foreach ($this->handler->getImagesPaths() as $file) {
            $this->assertFileExists($file);
        }

        //check that files (css, js) were downloaded
        foreach ($this->handler->getFilesPaths() as $file) {
            $this->assertFileExists($file);
        }

        //new args with a new directory passed in and new options
        $args5 = ['page-loader.php', 'http://ru.hexlet.io/courses', '-o', '/new'];
        $this->handler->setArgs($args5);
        $this->handler->handleOptions();
        $directory2 = vfsStream::url('home/petr/tmp' . '/new');
        mkdir($directory2);
        $url2 = $this->handler->getUrl();

        $this->handler->downloadPage($url2, $directory2, $this->client);
        $fullFilePath2 = $directory2 . '/' . $this->handler->getFileName();
        $this->assertFileExists($fullFilePath2);
        $this->assertStringEqualsFile($fullFilePath2, $this->stubChangedData);

        foreach ($this->handler->getImagesPaths() as $file) {
            $this->assertFileExists($file);
        }

        foreach ($this->handler->getFilesPaths() as $file) {
            $this->assertFileExists($file);
        }
        //visualize fake virtual directory with all downloaded files
        vfsStream::inspect(new vfsStreamPrintVisitor());
        
    }

/*    public function testDownloadPageNoImages(): void
    {
        //add tests with fixture with no <img> tags
    }*/

    public function tearDown(): void
    {
        unset($this->root);
    }
}