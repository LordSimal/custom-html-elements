<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\TagEngine;

use LordSimal\CustomHtmlElements\TagEngine;
use PHPUnit\Framework\TestCase;

/**
 * @see \LordSimal\CustomHtmlElements\TagEngine
 */
class SimpleHtmlTest extends TestCase
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

    public function testSimple(): void
    {
        $element = '<div>This is a Test</div>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<div>This is a Test</div>
HTML;
        $this->assertSame($expected, $result);
    }

    public function testSimpleSelfClosing(): void
    {
        $element = '<input type="text" />';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<input type="text" />
HTML;
        $this->assertSame($expected, $result);
    }

    public function testNestedSimpleTagsWithAttribute(): void
    {
        $element = '<div class="myclass"><input type="text" name="name" /></div>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<div class="myclass"><input type="text" name="name" /></div>
HTML;
        $this->assertSame($expected, $result);
    }
}
