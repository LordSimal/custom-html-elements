<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\Tags;

use LordSimal\CustomHtmlElements\CustomTag;

/**
 * @property string $src
 */
class ClassProperties extends CustomTag
{
    public static string $tag = 'c-class-properties';

    public string $test = 'default';

    public function render(): string
    {
        return <<< HTML
            <div class="$this->test"></div>
HTML;
    }
}
