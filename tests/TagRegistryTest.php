<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test;

use LordSimal\CustomHtmlElements\Error\TagNotFoundException;
use LordSimal\CustomHtmlElements\TagRegistry;
use LordSimal\CustomHtmlElements\Test\Tags\Button;
use PHPUnit\Framework\TestCase;

/**
 * @see \LordSimal\CustomHtmlElements\TagRegistry
 */
class TagRegistryTest extends TestCase
{
    public function testRegisterAndGet(): void
    {
        TagRegistry::register(Button::class);
        $class = TagRegistry::getTag('c-button');
        $this->assertSame($class, Button::class);
    }

    public function testRegisterAndGetAll(): void
    {
        TagRegistry::register(Button::class);
        $tags = TagRegistry::getTags();
        $this->assertSame($tags['c-button'], Button::class);
    }

    public function testGetUnknownTag(): void
    {
        $this->expectException(TagNotFoundException::class);
        $this->expectExceptionMessage('Tag Unknown was not found.');
        TagRegistry::getTag('Unknown');
    }
}
