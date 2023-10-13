<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\Tags;

use LordSimal\CustomHtmlElements\CustomTag;

/**
 * @property string $type
 * @property string $text
 * @property string $url
 */
class Button extends CustomTag
{
    public static string $tag = 'c-button';

    public function render(): string
    {
        $classes = ['c-button'];
        if ($this->type == 'primary') {
            $classes[] = 'c-button--primary';
        }
        $classes = implode(' ', $classes);

        return <<< HTML
			<a href="$this->url" class="$classes">$this->text</a>
HTML;
    }
}
