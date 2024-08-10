<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

abstract class CustomTag
{
    /**
     * Class/Object Name
     *
     * @var string
     */
    public static string $tag = '';

    /**
     * CustomTag constructor.
     *
     * @param array $attributes
     * @param string $innerContent
     */
    public function __construct(
        public array $attributes,
        public string $innerContent = ''
    ) {
    }

    /**
     * Magic Method to return readonly properties
     *
     * @param string $var property name
     * @return mixed property
     */
    public function __get(string $var): mixed
    {
        if (isset($this->$var)) {
            return $this->$var;
        }
        if (isset($this->attributes[$var])) {
            return $this->attributes[$var];
        }

        return null;
    }

    /**
     * @return string
     */
    abstract public function render(): string;
}
