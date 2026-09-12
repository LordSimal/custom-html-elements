<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

use ReflectionObject;

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
        public string $innerContent = '',
    ) {
        // Overwrite properties with what is given in the attributes
        $reflection = new ReflectionObject($this);
        foreach ($attributes as $key => $value) {
            if (
                $reflection->hasProperty($key)
                && $reflection->getProperty($key)->isPublic()
                && !$reflection->getProperty($key)->isStatic()
                && $reflection->getProperty($key)->getDeclaringClass()->getName() !== self::class
            ) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Magic Method to return properties
     *
     * @param string $var property name
     * @return mixed property
     */
    public function __get(string $var): mixed
    {
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
