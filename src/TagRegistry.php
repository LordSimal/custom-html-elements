<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

use LordSimal\CustomHtmlElements\Error\TagNotFoundException;

class TagRegistry
{
    protected static array $listOfTags = [];

    /**
     * @param class-string<\LordSimal\CustomHtmlElements\CustomTag> $tagClass
     * @return void
     */
    public static function register(string $tagClass): void
    {
        self::$listOfTags[$tagClass::$tag] = $tagClass;
    }

    /**
     * @return array
     */
    public static function getTags(): array
    {
        return self::$listOfTags;
    }

    /**
     * @param string $tag
     * @return string
     * @throws \LordSimal\CustomHtmlElements\Error\TagNotFoundException
     */
    public static function getTag(string $tag): string
    {
        if (array_key_exists($tag, self::$listOfTags)) {
            return self::$listOfTags[$tag];
        }

        throw new TagNotFoundException(sprintf('Tag %s was not found.', $tag));
    }
}
