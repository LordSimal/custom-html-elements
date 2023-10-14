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
        'tag_directories' => [], // Location for tag extensions
        'sniff_for_nested_tags' => false, // recursive search for tags
        'cache_tags' => false, // cache for improved performance (requires cache_directory)
        'cache_directory' => false, // Location for cached tags
        'custom_cache_tag_class' => false, // override to manipulate tag cache (include methods getCache and cache)
    ];

    protected string $searchReg = '';

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
            $searchRegex = '';
            foreach ($this->options['tag_directories'] as $tag_directory) {
                $classes = Discover::in($tag_directory)->classes()
                    ->extending(CustomTag::class)->get();
                /** @var \LordSimal\CustomHtmlElements\CustomTag|string $class */
                foreach ($classes as $class) {
                    if ($searchRegex != '') {
                        $searchRegex .= '|';
                    }
                    $searchRegex .= '\b' . $class::$tag . '\b';
                    TagRegistry::register($class);
                }
                // Also catch all other HTML elements
                $searchRegex .= '|\b\w*[>\s]\b';
            }
            $this->searchReg = "<($searchRegex)";
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
     * @return string The parsed $source value.
     */
    public function parse(mixed $source = false): string
    {
        if ($source === false) {
            $source = ob_get_clean();
        }
        $tags = $this->processTags($source);
        if (count($tags) > 0) {
            return $this->renderTags($tags);
        }

        return $source;
    }

    /**
     * Processes a tag by loading
     *
     * @param \LordSimal\CustomHtmlElements\CustomTag $tag The tag to parse.
     * @return string|bool The content of the tag.
     */
    protected function renderTag(CustomTag $tag): bool|string
    {
        if ($tag->disabled ?? false) {
            return ''; // return empty for disabled tag
        }

        $tag_data = false;
        $caching_tag = $tag->cache ?? true;
        if ($this->options['cache_tags'] && $caching_tag) { // Cache
            if ($this->options['custom_cache_tag_class']) {
                $tag_data = call_user_func_array([$this->options['custom_cache_tag_class'], 'getCache'], [$tag]);
            } else {
                $cache_file = $this->options['cache_directory'] . md5(serialize($tag));
                if (is_file($cache_file)) {
                    $tag_data = file_get_contents($cache_file);
                }
            }
            if ($tag_data) {
                $tag->cached = true;

                return $tag_data;
            }
        }
        $tag_data = $tag->render();

        if ($tag_data) {
            if ($this->options['sniff_for_nested_tags'] && $this->getLastTag($tag_data) !== false) {
                $tag_data = $this->parse(trim($tag_data));
            }

            if ($this->options['cache_tags'] === true && $caching_tag === true) {
                if ($this->options['custom_cache_tag_class'] !== false) {
                    call_user_func_array([$this->options['custom_cache_tag_class'], 'cache'], [$tag, $tag_data]);
                } else {
                    file_put_contents($this->options['cache_directory'] . md5(serialize($tag)), $tag_data);
                }
            }
        }

        return $tag_data;
    }

    /**
     * Loops and parses the found custom tags.
     *
     * @param array<\LordSimal\CustomHtmlElements\CustomTag> $tags An array of found custom tag data.
     * @return string|bool Returns false if there are no tags, string otherwise.
     */
    protected function renderTags(array $tags): string|bool
    {
        if ($tags) {
            $resultHtml = '';
            foreach ($tags as $tag) {
                $body = $this->renderTag($tag);
                $tag->parsedContent = $body;
                $tag->parsed = true;
                $resultHtml .= $body;
            }

            return $resultHtml;
        }

        return false;
    }

    /**
     * Utility Method to search for last allowable Tag not already processed
     *
     * @param string $subject
     * @return array|bool array of matched items or false if no match is present
     */
    protected function getLastTag(string $subject): bool|array
    {
        $PregMatch = '/' . $this->searchReg . '/';
        if (!preg_match_all($PregMatch, $subject, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        return $matches[0][count($matches[0]) - 1];
    }

    /**
     * Searches and parses a source for custom tags.
     *
     * @param string $source The source to search for custom tags in.
     * @return array<\LordSimal\CustomHtmlElements\CustomTag> An array of found tags.
     */
    public function processTags(string $source): array
    {
        $tags = [];

        // Sets Open Pos to end of HTML ($source)
        $eot = strlen($source);

        while ($eot && $eot > 0) {
            // Remaining HTML (moving Up)
            $currentSource = substr($source, 0, $eot);
            // Postion of "Opener"
            $eot = $this->getLastTag($currentSource);

            if (!$eot) { // No More Tags found
                $tag = new SimpleTag($source);
                $tags[] = $tag;
                break;
            } else { // Tag found (start from last find)
                $tagName = str_replace(['<','>'], '', $eot[0]);
                $eot = $eot[1];
                $closer = "</$tagName>";
                $currentSource = substr($source, $eot); // HTML from Last occurrence till end or Last processed Tag
                $nextDOM = strpos($currentSource, '<', 1); // Start of Next DOM Tag
                $nextCloseTag = strpos($currentSource, '/>'); // Close Bracket Loc

                if ($nextCloseTag !== false && ($nextCloseTag < $nextDOM || ($nextCloseTag && $nextDOM === false))) {
                    // Closing DOM is before the next DOM element (indicates <tag /> format)
                    $TagClose = $nextCloseTag + 2; // Update TagClose to include />
                } else {
                    // Traditional <tag></tag> format
                    $TagClose_begin = strpos($currentSource, $closer);
                    $TagClose = strpos($currentSource, '>', $TagClose_begin) + 1;
                }

                $tag_source = substr($currentSource, 0, $TagClose);
                try {
                    $class = TagRegistry::getTag($tagName);
                    $tag = new $class($tag_source);
                } catch (TagNotFoundException) {
                    $tag = new SimpleTag($tag_source);
                }

                $tags[] = $tag;

                // Update Source for next request
                $source = substr($source, 0, $eot) . substr($source, $eot + $TagClose);
            }
        }

        // Since we are parsing the source from the back to the front the array needs to be reversed to render it correctly
        return array_reverse($tags);
    }
}
