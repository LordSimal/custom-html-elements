# Custom HTML Elements

[![Latest Stable Version](https://poser.pugx.org/lordsimal/custom-html-elements/v)](https://packagist.org/packages/lordsimal/custom-html-elements) [![Total Downloads](https://poser.pugx.org/lordsimal/custom-html-elements/downloads)](https://packagist.org/packages/lordsimal/custom-html-elements) [![Latest Unstable Version](https://poser.pugx.org/lordsimal/custom-html-elements/v/unstable)](https://packagist.org/packages/lordsimal/custom-html-elements) [![License](https://poser.pugx.org/lordsimal/custom-html-elements/license)](https://packagist.org/packages/lordsimal/custom-html-elements) [![PHP Version Require](https://poser.pugx.org/lordsimal/custom-html-elements/require/php)](https://packagist.org/packages/lordsimal/custom-html-elements)[![codecov](https://codecov.io/gh/LordSimal/custom-html-elements/graph/badge.svg?token=dMo14KjnhP)](https://codecov.io/gh/LordSimal/custom-html-elements)

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
$engine = new \LordSimal\CustomHtmlElements\TagEngine::getInstance([
    'tag_directories' => [
        __DIR__.DIRECTORY_SEPARATOR.'Tags'.DIRECTORY_SEPARATOR,
        __DIR__.DIRECTORY_SEPARATOR.'OtherTagsFolder'.DIRECTORY_SEPARATOR,
    ],
]);
echo $engine->parse($htmlOutput);
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

To access the `Inner Content` text inside your class you simply have to call `$this->innerContent` like so

```php
class Github extends CustomTag
{
    public static string $tag = 'c-github';

    public function render(): string
    {
        return <<< HTML
            $this->innerContent
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

By default, this library renders nested custom HTML elements. So you don't need to worry about that.

## Disabling custom HTML elements

You have 2 ways how you can disable custom HTML elements:

### Disable all occurence of custom HTML elements

You can add the attributes 

```php
public bool $disabled = true;
```

to your Custom HTML Element class.

### Disable only specific occurence of custom HTML elements

You can add the attribute `disabled`, then it will not be rendered.

```html
<c-youtube src="RLdsCL4RDf8" disabled />
```

## More examples?

See all the different [TagEngine Tests](https://github.com/LordSimal/custom-html-elements/blob/main/tests/TagEngine/)

## Limitations

Since everything gets parsed via regex, there are some limitations related to how regex works.

E.g. if you have an enormous amount of HTML which needs to be parsed at once, it may be possible that the regex fails to parse the HTML correctly (e.g. [PREG_BACKTRACK_LIMIT_ERROR](https://www.php.net/manual/en/function.preg-last-error.php#refsect1-function.preg-last-error-returnvalues))

In this case you will have to find another way to encapsulate your HTML elements in PHP.

## Acknowledgements

This library is inspired by the following packages

* https://github.com/buggedcom/PHP-Custom-Tags
* https://github.com/SageITSolutions/MarkupEngine