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
        return $this->block;
    }
}
