<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\TagEngine;

use LordSimal\CustomHtmlElements\TagEngine;

/**
 * @see \LordSimal\CustomHtmlElements\TagEngine
 */
class NestedTest extends SimpleHtmlTest
{
    protected TagEngine $tagEngine;

    protected function setUp(): void
    {
        $this->tagEngine = new TagEngine([
            'tag_directories' => [
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
        ]);
    }

    /**
     * Test nested tags
     *
     * @return void
     */
    public function testTagsNested(): void
    {
        $element = '<c-github><c-github></c-github></c-github>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			This is a render from a plugin tag
            <c-github></c-github>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Make sure nested tags get rendered by default
     *
     * @return void
     */
    public function testNestedContentRendersWithConfig(): void
    {
        $element = '<c-nested />';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
						This is a render from a plugin tag
            
HTML;
        $this->assertSame($expected, $result);
    }
}
