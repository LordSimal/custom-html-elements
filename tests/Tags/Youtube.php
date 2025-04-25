<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\Tags;

use LordSimal\CustomHtmlElements\CustomTag;

/**
 * @property string $src
 * @property string $class
 * @property string $data_test_something
 */
class Youtube extends CustomTag
{
    public static string $tag = 'c-youtube';

    public function render(): string
    {
        return <<< HTML
			<iframe width="560" height="315" $this->data_test_something class="$this->class"
				src="https://www.youtube.com/embed/{$this->src}" 
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
				allowfullscreen>
				$this->innerContent
			</iframe>
HTML;
    }
}
