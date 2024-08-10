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

    /**
     * Test simple html element
     *
     * @return void
     */
    public function testSimple(): void
    {
        $element = '<div>This is a Test</div>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<div>This is a Test</div>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test simple html element with attribute
     *
     * @return void
     */
    public function testSimpleSelfClosing(): void
    {
        $element = '<input type="text" />';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<input type="text" />
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test nested simple html element with attribute
     *
     * @return void
     */
    public function testNestedSimpleTagsWithAttribute(): void
    {
        $element = '<div class="myclass"><input type="text" name="name" /></div>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<div class="myclass"><input type="text" name="name" /></div>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Make sure rendering a whole HTML document works as well
     *
     * @return void
     */
    public function testWholeHtmlDocument(): void
    {
        $element = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>Test</title>
    </head>
    <body>
        <div>This is a Test</div>
    </body>
</html>
HTML;
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>Test</title>
    </head>
    <body>
        <div>This is a Test</div>
    </body>
</html>
HTML;
        $this->assertSame($expected, $result);
    }
}
