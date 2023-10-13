<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\Tags;

use LordSimal\CustomHtmlElements\CustomTag;

class Nested extends CustomTag
{
    public static string $tag = 'c-nested';

    public function render(): string
    {
        return <<< HTML
			<c-github></c-github>
HTML;
    }
}
