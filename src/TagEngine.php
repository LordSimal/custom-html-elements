<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

use LordSimal\CustomHtmlElements\Error\ConfigException;
use LordSimal\CustomHtmlElements\Error\RegexException;
use LordSimal\CustomHtmlElements\Error\TagNotFoundException;
use Spatie\StructureDiscoverer\Discover;

class TagEngine
{
    /**
     * @var self|null
     */
    private static ?TagEngine $instance = null;

    /**
     * Holds the options array
     *
     * @var array
     */
    protected array $options = [
        'component_prefix' => 'c', // Prefix for custom tags
        'tag_directories' => [], // Location for tag extensions
        'enable_cache' => false, // Enable caching
        'cache_dir' => '', // Location for cache files
    ];

    /**
     * Holds the regex pattern for matching custom tags
     *
     * @var string
     */
    protected string $regex;

    /**
     * Holds the data array which is passed to the custom tags
     *
     * @var array
     */
    protected array $data = [];

    /**
     * @var array
     */
    protected array $discovery_cache = [];

    /**
     * Initialize TagEngine
     *
     * @param array $options to override existing settings
     */
    public function __construct(array $options = [])
    {
        $this->options['tag_directories'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Tags' . DIRECTORY_SEPARATOR;
        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
        $this->setRegex();
        $this->registerTags();

        if ($this->options['enable_cache']) {
            if (empty($this->options['cache_dir'])) {
                throw new ConfigException('Please set a `cache_dir` config');
            }
            if (!is_dir($this->options['cache_dir']) || !is_writable($this->options['cache_dir'])) {
                throw new ConfigException('Cache directory does not exist or is not writable');
            }
        }
    }

    /**
     * @return self
     */
    public static function getInstance(?array $options = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($options ?? []);
        }

        return self::$instance;
    }

    /**
     * The regex pattern is quite insane, so let's break it down
     *
     * @return void
     */
    protected function setRegex(): void
    {
        $tagName = '([\w-]+)'; // Matches word characters and hyphens for tag names
        $whitespace = '\s*'; // Optional whitespace
        $quotedAttr = '(?:"[^"]*"|\'[^\']*\')'; // Matches quoted attributes (e.g., "value" or 'value')
        $unquotedAttr = '[^\'">]'; // Matches unquoted attribute values (excluding quotes and >)
        $attributes = "((?:$quotedAttr|$unquotedAttr)*)"; // Zero or more attributes
        $prefix = $this->options['component_prefix'] ?? 'c'; // Placeholder for tag prefix (e.g., 'c' in c-tag)

        // Self-closing tag pattern: <c-tag attributes/>
        $selfClosingOpen = "<$prefix-$tagName$whitespace$attributes$whitespace\/>";

        // Tag with content pattern: <c-tag attributes>content</c-tag>
        $contentOpen = "<$prefix-$tagName$whitespace$attributes>";
        $contentInner = '(.*?)'; // Non-greedy content between tags
        $contentClose = "<\/$prefix-\\3>"; // Closing tag, referencing the tag name from group 3
        $contentPattern = "$contentOpen$contentInner$contentClose";

        // Combine both patterns with alternation
        $this->regex = "/$selfClosingOpen|$contentPattern/s";
    }

    /**
     * Process Default Options and Parse to static variables
     *
     * @return void
     */
    protected function registerTags(): void
    {
        if ($this->options['tag_directories']) {
            foreach ($this->options['tag_directories'] as $tag_directory) {
                if (isset($this->discovery_cache[$tag_directory])) {
                    continue;
                }
                $this->discovery_cache[$tag_directory] = true;

                // This is quite expensive, so only do it once
                $classes = Discover::in($tag_directory)->classes()
                    ->extending(CustomTag::class)->get();
                /** @var \LordSimal\CustomHtmlElements\CustomTag|string $class */
                foreach ($classes as $class) {
                    TagRegistry::register($class);
                }
            }
        }
    }

    /**
     * Parses the source for any custom tags.
     *
     * @param mixed $source If false then it will capture the output buffer, otherwise if a string
     *   it will use this value to search for custom tags.
     * @param array $data An array of variables to pass to the custom tags.
     * @return string The parsed $source value.
     * @throws \LordSimal\CustomHtmlElements\Error\RegexException
     */
    public function parse(mixed $source = false, array $data = []): string
    {
        if ($source === false) {
            $source = ob_get_clean();
        }
        $this->data = $data;

        $result = preg_replace_callback($this->regex, [$this, 'replaceComponent'], $source);

        if ($result === null) {
            // Something went wrong with the regex and/or the content
            $errorCode = preg_last_error();
            $errors = [
                PREG_NO_ERROR => 'No error',
                PREG_INTERNAL_ERROR => 'Internal PCRE error',
                PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit was exhausted',
                PREG_RECURSION_LIMIT_ERROR => 'Recursion limit was exhausted',
                PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
                PREG_BAD_UTF8_OFFSET_ERROR => 'Bad UTF-8 offset',
                PREG_JIT_STACKLIMIT_ERROR => 'JIT stack limit exhausted',
            ];

            throw new RegexException($errors[$errorCode] ?? 'Unknown error', $source, $this->regex);
        }

        return $result;
    }

    /**
     * @param array $matches
     * @return string
     */
    protected function replaceComponent(array $matches): string
    {
        $componentName = $matches[1] ?: $matches[3]; // Tag name for both self-closing and normal tags
        $attributesString = $matches[2] ?: $matches[4] ?? ''; // Attributes for both self-closing and normal tags
        $content = $matches[5] ?? ''; // Inner content

        $attributes = $this->parseAttributes($attributesString);

        // If the component has content, process it (handles nested components)
        if ($content) {
            $content = preg_replace_callback($this->regex, [$this, 'replaceComponent'], $content);
        }

        $result = $this->renderComponent($componentName, $attributes, $content);

        // Render nested components
        do {
            $result = preg_replace_callback($this->regex, [$this, 'replaceComponent'], $result);
        } while (preg_match($this->regex, $result));

        return $result;
    }

    /**
     * @param string $attributesString
     * @return array
     */
    protected function parseAttributes(string $attributesString): array
    {
        // Regex to match attributes (both static and dynamic)
        $pattern = '/([:\w-]+)(?:=["\']([^"\']+)["\'])?/';
        preg_match_all($pattern, $attributesString, $matches, PREG_SET_ORDER);

        $attributes = [];
        foreach ($matches as $match) {
            $name = $match[1];
            $value = $match[2] ?? true; // If no value, set it to true

            $name = str_replace('-', '_', $name); // Replace hyphens with underscores so that it works with properties

            // Check if it's a dynamic attribute (starts with ":")
            if (str_starts_with($name, ':')) {
                $varName = substr($name, 1); // remove the leading ":"
                $dataName = substr($value, 1); // remove the leading "$"
                $attributes[$varName] = $this->data[$dataName] ?? null;
            } else {
                // Static attribute
                $attributes[$name] = $value;
            }
        }

        return $attributes;
    }

    /**
     * @param string $componentName
     * @param array $attributes
     * @param string $innerContent
     * @return string
     */
    protected function renderComponent(string $componentName, array $attributes, string $innerContent = ''): string
    {
        if ($this->options['enable_cache']) {
            $cacheKey = md5($componentName . serialize($attributes) . $innerContent);
            $cacheFile = $this->options['cache_dir'] . DIRECTORY_SEPARATOR . $cacheKey . '.html';
            if (file_exists($cacheFile)) {
                return file_get_contents($cacheFile);
            }
        }

        try {
            $class = TagRegistry::getTag(sprintf('%s-%s', $this->options['component_prefix'], $componentName));
            $tag = new $class($attributes, $innerContent);

            if ($tag->disabled) {
                return '';
            }
        } catch (TagNotFoundException) {
            $tag = new SimpleTag($attributes, $innerContent);
            $tag::$tag = $componentName;
        }

        $html = $tag->render();

        if ($this->options['enable_cache']) {
            file_put_contents($cacheFile, $html);
        }

        return $html;
    }
}
