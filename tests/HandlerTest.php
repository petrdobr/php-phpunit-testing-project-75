#!/usr/bin/env php
<?php

namespace App\Tests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use App\Handler;

class HandlerTest extends TestCase
{
    private $connect;
    private $handler;
    private $args;

    public function setUp(): void
    {
        $this->connect = $this->getMockBuilder(Client::class);
        $this->handler = new Handler();
    }

    public function testHelpOutput(): void
    {
        $args = ['page-loader.php', '-h'];
        $this->handler->setArgs($args);
        $helpMessage = 'help text will come' . PHP_EOL;
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

        //test default directory
        $args2 = ['page-loader.php', 'http://hexlet.io/page/com/'];
        $this->handler->setArgs($args2);
        $this->handler->handleOptions();
        $expectedDirectory = realpath(__DIR__ . '/../src/');
        $this->assertEquals($expectedDirectory, $this->handler->getFileDirectory());

        //test passed in directory
        $args3 = ['page-loader.php', 'http://hexlet.io/page/com/', '-o', '/tmp'];
        $this->handler->setArgs($args3);
        $this->handler->handleOptions();
        $expectedDirectory = realpath(__DIR__ . '/../src') . '/tmp';
        $this->assertEquals($expectedDirectory, $this->handler->getFileDirectory());
    }
}