<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Settings;

use App\Application\Settings\Settings;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function testGetReturnsValuesAndMap(): void
    {
        $map = ['a' => 1, 'b' => 'two'];
        $s = new Settings($map);

        $this->assertSame($map, $s->get());
        $this->assertSame(1, $s->get('a'));
        $this->assertSame('two', $s->get('b'));
    }
}
