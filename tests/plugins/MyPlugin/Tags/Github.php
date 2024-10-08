<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\MyPlugin\Tags;

use LordSimal\CustomHtmlElements\CustomTag;

class Github extends CustomTag
{
    public static string $tag = 'c-github';

    public function render(): string
    {
        return <<< HTML
			This is a render from a plugin tag
            $this->innerContent
HTML;
    }
}
