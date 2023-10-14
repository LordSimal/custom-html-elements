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
    public string $content = '';

    /**
     * Class/Object Name
     *
     * @var string
     */
    public static string $tag = '';

    /**
     * Extensible Items
     *
     * @var object|array
     */
    public array|object $attributes = [];

    /**
     * @var bool
     */
    public bool $parsed = false;

    /**
     * @var bool
     */
    public bool $cached = false;

    /**
     * @var bool
     */
    public bool $disabled = false;

    /**
     * @var string
     */
    public string $parsedContent = '';

    /**
     * @var array|string
     */
    public string|array $innerMarkers = '';

    /**
     * Regex Search Pattern
     *
     * @var string
     */
    protected string $tagSearch = '';

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
    protected string $tagClose = '>';

    /**
     * Constructor
     *
     * @param string $block The source to parse
     */
    public function __construct(
        public string $block
    ) {
        if (str_ends_with($this->block, '/>')) {
            $this->isSelfClosing = true;
        }
        $tag = static::$tag;
        $this->tagSearch = "/<($tag)\s*([^$this->tagClose]*)/";
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
        if (in_array($var, ['parsed','parsedcontent','block','content','innermarkers'])) {
            $this->$var = $val ?? null;
        }
    }

    /**
     * @return string
     */
    abstract public function render(): string;
}
