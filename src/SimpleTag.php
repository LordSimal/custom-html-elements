<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

use Override;

class SimpleTag extends CustomTag
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function render(): string
    {
        $tag = self::$tag;

        return "<$tag>$this->innerContent</$tag>";
    }
}
