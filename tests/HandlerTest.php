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

    public function testHelpOptions(): void
    {
        $this->args = ['page-loader.php', '-h', '-o', '/tmp/var/temp.html'];
        $this->handler->setArgs($this->args);
        $helpMessage = 'text will come' . PHP_EOL;
        $this->handler->handleOptions();
        $this->expectOutputString($helpMessage);
        //clear $this->args just in case
        $this->args = [];
    }

    public function testOptions(): void
    {
        //should check for options handling;
        //let's say it gets options:
        //argv[0] - command itself
        //argv[1] - either --help or link, others ignored with error
        //argv[2] - should be -o; others ignored (need to check the taks)
        //argv[3] - if -o is passed in then this should be a directory where to save a file
        $this->assertEquals('a', 'a');
    }
}