<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\WipParser;
use PHPUnit\Framework\TestCase;

class WipParserTest extends TestCase
{
    /**
     * @dataProvider titlesProvider
     */
    public function testMatchTitle(bool $expected, string $title)
    {
        $this->assertSame($expected, WipParser::matchTitle($title));
    }

    public static function titlesProvider(): iterable
    {
        yield [true, '[WIP] foo'];
        yield [true, 'WIP: bar'];
        yield [true, '(WIP) xas'];
        yield [true, '[WIP]foo'];
        yield [true, '[wip] foo'];

        yield [false, 'Bar [WIP] foo'];
        yield [false, 'FOOWIP: foo'];
    }
}
