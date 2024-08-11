<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\TagEngine;

use LordSimal\CustomHtmlElements\TagEngine;
use PHPUnit\Framework\TestCase;

/**
 * @see \LordSimal\CustomHtmlElements\TagEngine
 */
class PassVariablesTest extends TestCase
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
     * Test a taf with a variable reference
     *
     * @return void
     */
    public function testPassVariableWorks(): void
    {
        $tagEngine = $this->tagEngine;
        $element = '<c-variable :myVar="$tagEngine" />';
        $result = $this->tagEngine->parse($element, compact('tagEngine'));
        $expected = <<<HTML
The passed down class was: LordSimal\CustomHtmlElements\TagEngine
HTML;
        $this->assertSame($expected, $result);
    }
}
