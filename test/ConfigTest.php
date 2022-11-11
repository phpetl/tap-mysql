<?php
declare(strict_types=1);

namespace PhpETLTest\Tap\Peak15;

use PhpETL\Tap\Peak15\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testLoadConfigFromFile()
    {
        $instance = Config::fromFile('test');
        $this->assertEquals('Test', $instance->orgName);
    }
}