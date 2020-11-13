<?php

namespace App\Tests\Service;

use App\Api\Label\StaticLabelApi;
use App\Model\Repository;
use App\Service\LabelNameExtractor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class LabelNameExtractorTest extends TestCase
{
    public function testExtractLabels()
    {
        $api = new StaticLabelApi();
        $extractor = new LabelNameExtractor($api, new NullLogger());
        $repo = new Repository('carsonbot-playground', 'symfony');

        $this->assertSame(['Messenger'], $extractor->extractLabels('[Messenger] Foobar', $repo));
        $this->assertSame(['Messenger'], $extractor->extractLabels('[messenger] Foobar', $repo));
        $this->assertSame(['Messenger', 'Mime'], $extractor->extractLabels('[Messenger][Mime] Foobar', $repo));
        $this->assertSame(['Messenger', 'Mime'], $extractor->extractLabels('[Messenger] [Mime] Foobar', $repo));
        $this->assertSame(['Messenger', 'Mime'], $extractor->extractLabels('[Messenger] Foobar [Mime] ', $repo));
    }
}
