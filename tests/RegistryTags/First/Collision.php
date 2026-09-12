<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Test\RegistryTags\First;

use LordSimal\CustomHtmlElements\CustomTag;

class Collision extends CustomTag
{
    public static string $tag = 'c-collision';

    public function render(): string
    {
        return 'first';
    }
}
