<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\TagEngine;

use LordSimal\CustomHtmlElements\TagEngine;
use PHPUnit\Framework\TestCase;

/**
 * @see \LordSimal\CustomHtmlElements\TagEngine
 */
class TagEngineTest extends TestCase
{
    /**
     * Test singleton instance creation
     *
     * @return void
     */
    public function testGetInstance(): void
    {
        $options = [
            'tag_directories' => [
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
        ];
        $instance = TagEngine::getInstance($options);
        $this->assertInstanceOf(TagEngine::class, $instance);

        $sameInstance = TagEngine::getInstance();
        $this->assertSame($instance, $sameInstance);
    }
}
