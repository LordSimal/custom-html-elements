<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test;

use LordSimal\CustomHtmlElements\TagEngine;
use PHPUnit\Framework\TestCase;

/**
 * @see \LordSimal\CustomHtmlElements\TagEngine
 */
class TagEngineTest extends TestCase
{
    protected TagEngine $tagEngine;

    protected function setUp(): void
    {
        $this->tagEngine = new TagEngine([
            'tag_directories' => [
                __DIR__ . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR,
                __DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
            ],
        ]);
    }

    /**
     * Test a tab with a simple attribute
     *
     * @return void
     */
    public function testTagWithAttribute(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8"></c-youtube>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test self-closing tag variant
     *
     * @return void
     */
    public function testTagWithAttributeSelfClosing(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8" />';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test multiple self-closing tag variants
     *
     * @return void
     */
    public function testMultipleTagsWithAttributeSelfClosing(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8" /><c-youtube src="RLdsCL4RDf8" />';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test tags work with multiple attributes
     *
     * @return void
     */
    public function testTagWithMultipleAttributes(): void
    {
        $element = '<c-button type="primary" text="Click me" url="/something/stupid" />';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			<a href="/something/stupid" class="c-button c-button--primary">Click me</a>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test tags work in other custom directories
     *
     * @return void
     */
    public function testTagInSubFolder(): void
    {
        $element = '<c-github></c-github>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			This is a render from a plugin tag
            
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test inner content is passed down to the tag and can be outputted
     *
     * @return void
     */
    public function testTagWithInnerContent(): void
    {
        $element = '<c-github>Inner Content</c-github>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			This is a render from a plugin tag
            Inner Content
HTML;
        $this->assertSame($expected, $result);
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

    /**
     * Test tag variant and normal HTML
     *
     * @return void
     */
    public function testTagWithAttributeAndNormalHTML(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8"></c-youtube><div>Test</div>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe><div>Test</div>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test self-closing tag variant and normal HTML
     *
     * @return void
     */
    public function testTagWithAttributeSelfClosingAndNormalHTML(): void
    {
        $element = '<c-youtube src="RLdsCL4RDf8" /><div>Test</div><input type="text"/>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe><div>Test</div><input type="text"/>
HTML;
        $this->assertSame($expected, $result);
    }

    /**
     * Test disabled tags
     *
     * @return void
     */
    public function testDisabledTag(): void
    {
        $element = '<c-disabled />';
        $result = $this->tagEngine->parse($element);
        $expected = '';
        $this->assertSame($expected, $result);
    }

    /**
     * Test no custom Tag
     *
     * @return void
     */
    public function testNoCustomTag(): void
    {
        $element = '<div>This is a Test</div>';
        $result = $this->tagEngine->parse($element);
        $expected = '<div>This is a Test</div>';
        $this->assertSame($expected, $result);
    }

    /**
     * Test output buffered
     *
     * @return void
     */
    public function testOutputBuffered(): void
    {
        $element = '<c-github></c-github>';
        ob_start();
        echo $element;
        $result = $this->tagEngine->parse();
        $expected = <<<HTML
			This is a render from a plugin tag
            
HTML;
        $this->assertSame($expected, $result);
    }

    public function testWithDivWrapped(): void
    {
        $element = '<div>
            <c-youtube src="RLdsCL4RDf8"/>
        </div>';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<div>
            			<iframe width="560" height="315" 
				src="https://www.youtube.com/embed/RLdsCL4RDf8" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
			</iframe>
        </div>
HTML;
        $this->assertSame($expected, $result);
    }

    public function testSimpleTag(): void
    {
        $element = '<input type="text" />';
        $result = $this->tagEngine->parse($element);
        $expected = <<<HTML
<input type="text" />
HTML;
        $this->assertSame($expected, $result);
    }
}
