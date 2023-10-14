<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\Tags;

use Exception;
use LordSimal\CustomHtmlElements\CustomTag;

class Disabled extends CustomTag
{
    public static string $tag = 'c-disabled';

    public bool $disabled = true;

    public function render(): string
    {
        throw new Exception('This should not be called');
    }
}
