<?php

namespace App\Tests\Service;

use App\Api\Label\StaticLabelApi;
use App\Model\Repository;
use App\Service\LabelNameExtractor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class LabelNameExtractorTest extends TestCase
{
    /**
     * @dataProvider provideLabels
     */
    public function testExtractLabels(array $expected, string $title)
    {
        $extractor = new LabelNameExtractor(new StaticLabelApi(), new NullLogger());
        $repo = new Repository('carsonbot-playground', 'symfony');

        $this->assertSame($expected, $extractor->extractLabels($title, $repo));
    }

    public static function provideLabels(): iterable
    {
        yield [['Messenger'], '[Messenger] Foobar'];
        yield [['Messenger'], '[messenger] Foobar'];
        yield [['Messenger', 'Mime'], '[Messenger][Mime] Foobar'];
        yield [['Messenger', 'Mime'], '[Messenger] [Mime] Foobar'];
        yield [['Messenger', 'Mime'], '[Messenger] Foobar [Mime]'];
    }
}
