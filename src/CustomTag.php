<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

abstract class CustomTag
{
    /**
     * Inner content HTML
     *
     * @var string
     */
    protected string $content = '';

    /**
     * Class/Object Name
     *
     * @var string
     */
    public static string $tag = '';

    /**
     * Used to represent Item
     *
     * @var string
     */
    protected string $placeholder = '';

    /**
     * Extensible Items
     *
     * @var object|array
     */
    protected array|object $attributes = [];

    /**
     * Defines if tag is <tag /> format
     *
     * @var bool
     */
    protected bool $isSelfClosing = false;

    /**
     * Used in defining tag format
     *
     * @var string
     */
    protected string $tagclose = '>';

    /**
     * @var bool
     */
    protected bool $parsed = false;

    /**
     * @var string
     */
    protected string $parsedcontent = '';

    /**
     * @var array|string
     */
    protected string|array $innermarkers = '';

    /**
     * Regex Search Pattern
     *
     * @var string
     */
    protected string $tagSearch = '';

    /**
     * Constructor
     *
     * @param string $block The source to parse
     * @param int $instance The instance id of the TagEngine
     * @param int $index The amount of tags
     */
    public function __construct(
        protected string $block,
        int $instance,
        int $index
    ) {
        $this->placeholder = '------@@%' . $instance . '-' . $index . '%@@------';
        if (str_ends_with($this->block, '/>')) {
            $this->isSelfClosing = true;
            //$this->tagclose = "\/>";
        }
        $tag = static::$tag;
        $this->tagSearch = "/<($tag)\s*([^$this->tagclose]*)/";
        $this->build($this->block);
    }

    /**
     * @param string $strBody The source to parse
     * @return void
     */
    protected function build(string $strBody): void
    {
        $matches = [];
        if (preg_match_all($this->tagSearch, $this->block, $matches) > 0) {
            $attribute_string = $matches[2][0];
            if (!$this->isSelfClosing) {
                $begin_len = strlen($matches[0][0] . '>');
                $cutStart = $begin_len;
                $this->content = substr($strBody, $cutStart, strlen($strBody) - (2 * $cutStart + 1));
            }

            $attributes = [];
            $pattern = "!([_\-A-Za-z0-9]*)(=\"|=\')([^\"|\']*)(\"|\')!is";
            if (preg_match_all($pattern, $attribute_string, $attributes) > 0) {
                foreach ($attributes[0] as $key => $row) {
                    $this->attributes[$attributes[1][$key]] = $attributes[3][$key];
                }
            }
        }
        $this->attributes = (object)$this->attributes;
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
        if (isset($this->attributes->$var)) {
            return $this->attributes->$var;
        }

        return null;
    }

    /**
     * Magic Method to set readonly properties
     *
     * @param mixed $var name of variable to set
     * @param mixed $val value to apply
     */
    public function __set(mixed $var, mixed $val): void
    {
        if (in_array($var, ['parsed','parsedcontent','block','content','placeholder','innermarkers'])) {
            $this->$var = $val ?? null;
        }
    }

    /**
     * @return string
     */
    abstract public function render(): string;
}
