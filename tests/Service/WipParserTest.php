<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\WipParser;
use PHPUnit\Framework\TestCase;

class WipParserTest extends TestCase
{
    public function testMatchTitle()
    {
        $this->assertTrue(WipParser::matchTitle('[WIP] foo'));
        $this->assertTrue(WipParser::matchTitle('WIP: bar'));
        $this->assertTrue(WipParser::matchTitle('(WIP) xas'));
        $this->assertTrue(WipParser::matchTitle('[WIP]foo'));
        $this->assertTrue(WipParser::matchTitle('[wip] foo'));

        $this->assertFalse(WipParser::matchTitle('Bar [WIP] foo'));
        $this->assertFalse(WipParser::matchTitle('FOOWIP: foo'));
    }
}
