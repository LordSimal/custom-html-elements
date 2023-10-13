# Custom HTML Elements
Allows you to create custom HTML elements to render more complex template parts with a simpler HTML representation

## Requirements

* PHP 8.1+

## Installation

```shell
composer require lordsimal/custom-html-elements
```

## Usage

This is an example representation of a custom HTML element you want to use:

```html
<c-youtube src="RLdsCL4RDf8"/>
```

So this would appear in a HTML output like this:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Example Page</title>
    <meta name="author" content="Kevin Pfeifer">
</head>
<body> 
    <c-youtube src="RLdsCL4RDf8"/>
</body>
</html>
```

To render this custom HTML element you need to do this:

```php
$htmlOutput = ''; // This variable represents what is shown above
$me = new \LordSimal\CustomHtmlElements\TagEngine([]
    'parse_on_shutdown' 	=> true,
    'tag_directories'       => [
        __DIR__.DIRECTORY_SEPARATOR.'Tags'.DIRECTORY_SEPARATOR,
        __DIR__.DIRECTORY_SEPARATOR.'OtherTagsFolder'.DIRECTORY_SEPARATOR,
    ],
    'sniff_for_buried_tags' => true
]);
echo $me->parse($htmlOutput);
```

The element logic can be placed in e.g. `Tags/Youtube.php` or `OtherTagsFolder/Youtube.php`:

```php
<?php
namespace App\Tags;

use LordSimal\CustomHtmlElements\CustomTag;

class Youtube extends CustomTag 
{
    public static string $tag = 'c-youtube';

    public function render(): string
    {
        return <<< HTML
        <iframe width="560" height="315" 
            src="https://www.youtube.com/embed/{$this->src}" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
        </iframe>
HTML;
    }
}
```

As you can see the main 2 parts are the 

```php
public static string $tag = 'c-youtube';
```

which defines what HTML tag is represented by this class and the `render()` method.

Inside the `render()` method you have access to all HTML attributes which you pass onto your element.

So e.g.

```html
<c-button type="primary" text="Click me" url="/something/stupid" />
```

would be accessible inside the `Button` class via

```php
class Button extends CustomTag
{
    public static string $tag = 'c-button';

    public function render(): string
    {
        $classes = ['c-button'];
        if ($this->type == 'primary') {
            $classes[] = 'c-button--primary';
        }
        $classes = implode(' ', $classes);
        return <<< HTML
            <a href="$this->url" class="$classes">$this->text</a>
HTML;
    }
}
```

## Accessing the inner content

You may want to create custom HTML elements like
```html
<c-github>Inner Content</c-github>
```

To access the `Inner Content` text inside your class you simply have to call `$this->content` like so

```php
class Github extends CustomTag
{
    public static string $tag = 'c-github';

    public function render(): string
    {
        return <<< HTML
            $this->content
HTML;
    }
}
```

## Self closing elements

By default every custom HTML element can be used either way:

```html
<c-youtube src="RLdsCL4RDf8"></c-youtube>
```
or
```html
<c-youtube src="RLdsCL4RDf8" />
```

both are rendered the same way.

## Rendering nested custom HTML elements

By default this library doesn't render nested custom HTML elements. To enable this feature you have to add this config while creating the TagEngine

```php
$tagEngine = new TagEngine([
    'tag_directories' => [
        __DIR__.DIRECTORY_SEPARATOR.'Tags'.DIRECTORY_SEPARATOR,
    ],
    'sniff_for_nested_tags' => true
]);
```

## Acknowledgements

This library is inspired by the following packages

* https://github.com/buggedcom/PHP-Custom-Tags
* https://github.com/SageITSolutions/MarkupEngine