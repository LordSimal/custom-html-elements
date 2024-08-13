<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

use LordSimal\CustomHtmlElements\Error\ConfigException;
use LordSimal\CustomHtmlElements\Error\TagNotFoundException;
use Spatie\StructureDiscoverer\Discover;

class TagEngine
{
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
    protected string $regex = '/<%s-([\w-]+)\s*([^>]*)\/\s*>|<%s-([\w-]+)\s*([^>]*)>(.*?)<\/%s-\3>/s';

    /**
     * Holds the data array which is passed to the custom tags
     *
     * @var array
     */
    protected array $data = [];

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
        $prefix = $this->options['component_prefix'] ?? 'c';
        $this->regex = sprintf($this->regex, $prefix, $prefix, $prefix);
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
     * Process Default Options and Parse to static variables
     *
     * @return void
     */
    protected function registerTags(): void
    {
        if ($this->options['tag_directories']) {
            foreach ($this->options['tag_directories'] as $tag_directory) {
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
     */
    public function parse(mixed $source = false, array $data = []): string
    {
        if ($source === false) {
            $source = ob_get_clean();
        }
        $this->data = $data;

        return preg_replace_callback($this->regex, [$this, 'replaceComponent'], $source);
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
