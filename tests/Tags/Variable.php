<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\Tags;

use LordSimal\CustomHtmlElements\CustomTag;

/**
 * @property string $src
 */
class Variable extends CustomTag
{
    public static string $tag = 'c-variable';

    public function render(): string
    {
        return 'The passed down class was: ' . get_class($this->myVar);
    }
}
