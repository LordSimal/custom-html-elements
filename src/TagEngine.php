<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements;

use DOMDocumentType;
use DOMNode;
use DOMText;
use LordSimal\CustomHtmlElements\Error\TagNotFoundException;
use Masterminds\HTML5;
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
            if ($this->options['sniff_for_nested_tags']) {
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
                if (!$tag->parsed) {
                    $body = $this->renderTag($tag);
                    $tag->parsedContent = $body;
                    $tag->parsed = true;
                }
                $resultHtml .= $tag->parsedContent;
            }

            return $resultHtml;
        }

        return false;
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

        $html5 = new HTML5();
        $dom = $html5->loadHTML($source);

        $this->recursiveParse($dom, $tags);

        return $tags;
    }

    /**
     * @param \DOMNode $domDocument
     * @param array $tags
     * @return void
     */
    protected function recursiveParse(DOMNode $domDocument, array &$tags): void
    {
        if ($domDocument->hasChildNodes()) {
            if (!empty($domDocument->tagName) && TagRegistry::hasTag($domDocument->tagName)) {
                $tags[] = $this->getTagFromHtml($domDocument);
            } else {
                foreach ($domDocument->childNodes as $childNode) {
                    $this->recursiveParse($childNode, $tags);
                }
            }
        } else {
            if ($domDocument instanceof DOMText) {
                $domDocument = $domDocument->parentNode;
            } elseif ($domDocument instanceof DOMDocumentType && $domDocument->name === 'html') {
                // Don't add <!DOCTYPE html>
                return;
            }

            $tag = $this->getTagFromHtml($domDocument);
            $tags[] = $tag;
        }
    }

    /**
     * @param \DOMNode $DOMNode
     * @return \LordSimal\CustomHtmlElements\CustomTag
     */
    protected function getTagFromHtml(DOMNode $DOMNode): CustomTag
    {
        $html = $DOMNode->ownerDocument->saveXML($DOMNode) ?: '';
        if (!empty($DOMNode->tagName)) {
            try {
                $class = TagRegistry::getTag($DOMNode->tagName);
                $tag = new $class($html);
            } catch (TagNotFoundException) {
                $tag = new SimpleTag($html);
            }
        } else {
            $tag = new SimpleTag($html);
        }

        return $tag;
    }
}
