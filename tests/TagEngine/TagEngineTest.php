<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\TagEngine;

use LordSimal\CustomHtmlElements\Error\RegexException;
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
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
            ],
        ];
        $instance = TagEngine::getInstance($options);
        $this->assertInstanceOf(TagEngine::class, $instance);

        $sameInstance = TagEngine::getInstance();
        $this->assertSame($instance, $sameInstance);
    }

    public function testThrowsRegexException(): void
    {
        $options = [
            'tag_directories' => [
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
        ];
        $instance = TagEngine::getInstance($options);

        $test = '';
        $pcreLimit = 100000;
        for($i = 0; $i < $pcreLimit; $i++) {
            $test .= '<c-tag>Test';
        }
        for($i = 0; $i < $pcreLimit; $i++) {
            $test .= '</c-tag>';
        }

        try {
            $instance->parse($test);
        } catch (RegexException $e) {
            $this->assertEquals('Backtrack limit was exhausted', $e->getMessage());
            $this->assertNotEmpty($e->getRegex());
            $this->assertNotEmpty($e->getSource());
        }
    }
}
