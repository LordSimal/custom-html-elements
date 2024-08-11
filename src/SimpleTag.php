<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

class SimpleTag extends CustomTag
{
    /**
     * @inheritDoc
     */
    public function render(): string
    {
        $tag = self::$tag;

        return "<$tag>$this->innerContent</$tag>";
    }
}
