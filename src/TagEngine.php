<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

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
        'cache_tags' => false, // cache for improved performance (requires cache_directory)
        'cache_directory' => false, // Location for cached tags
        'custom_cache_tag_class' => false, // override to manipulate tag cache (include methods getCache and cache)
    ];

    /**
     * Holds the regex pattern for matching custom tags
     *
     * @var string
     */
    protected string $regex = '/<%s-([\w-]+)\s*([^>]*)>(.*?)<\/%s-\1>|<%s-([\w-]+)\s*([^>]*)\/>/s';

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
        if ($options && is_array($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $prefix = $this->options['component_prefix'] ?? 'c';
        $this->regex = sprintf($this->regex, $prefix, $prefix, $prefix);
        $this->setDefaults();
    }

    /**
     * Process Default Options and Parse to static variables
     *
     * @return void
     */
    public function setDefaults(): void
    {
        if ($this->options['custom_cache_tag_class'] && !class_exists($this->options['custom_cache_tag_class'])) {
            $this->options['cache_tags'] = false;
        }

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

        if ($this->options['cache_tags']) {
            if (!$this->options['custom_cache_tag_class']) {
                if (!$this->options['cache_directory']) {
                    $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
                    $this->options['cache_directory'] = $path;
                } else {
                    if (!is_dir($this->options['cache_directory']) || !is_writable($this->options['cache_directory'])) {
                        $this->options['cache_tags'] = false;
                    }
                }
            } else {
                if (!class_exists($this->options['custom_cache_tag_class'])) {
                    $this->options['cache_tags'] = false;
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
        $componentName = $matches[1] ?: $matches[4]; // Tag name for both self-closing and normal tags
        $attributesString = $matches[2] ?: $matches[5] ?? ''; // Attributes for both self-closing and normal tags
        $content = $matches[3] ?? ''; // Inner content

        // Parse attributes
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
        $pattern = '/(\w+)=["\']([^"\']+)["\']/';
        preg_match_all($pattern, $attributesString, $matches, PREG_SET_ORDER);

        $attributes = [];
        foreach ($matches as $match) {
            $name = $match[1];
            $value = $match[2];

            // Check if it's a dynamic attribute (starts with ":")
            if (str_starts_with($name, ':')) {
                $varName = substr($name, 1); // remove the leading ":"
                $attributes[$varName] = $this->data[$value] ?? null;
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
        try {
            $class = TagRegistry::getTag(sprintf('%s-%s', $this->options['component_prefix'], $componentName));
            $tag = new $class($attributes, $innerContent);

            if ($tag->disabled) {
                return '';
            }

            return $tag->render();
        } catch (TagNotFoundException) {
            // do something?
        }

        return 'something went wrong';
    }
}
