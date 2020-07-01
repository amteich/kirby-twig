# Using your own functions in templates

If you need to expose PHP functions (or static class methods) to your Twig templates, you can list them with those options:

```php

return [
  'mgfagency.twig.env.functions' => [
  ],
  'mgfagency.twig.env.filters' => [
  ],
  'mgfagency.twig.env.tests' => [
  ],
];
```

As with any option in Kirby, you should define these options in your `site/config/config.php`. Let’s show how each option works.

## Exposing a function

The expected syntax for these configuration options is:

```php
'mgfagency.twig.env.functions' => [
  'myFunctionName' => $someFunction
],
```

Where:

-   `myFunctionName` is any name you want (only letters and underscores), and is the name that will be available in your Twig templates.
-   `$someFunction` can be a string, or a Closure.

Let’s use more tangible examples.

### Using a function name (string)

If you have a custom function defined in a plugin file (e.g. `site/plugins/myplugin.php`):

```php
<?php
/**
 * Returns a welcoming message
 * @param  string $who
 * @return string
 */
function sayHello($who='') {
  return 'Hello' . (is_string($who) ? ' ' . $who : '');
}
```

You can make it available as a Twig function:

```php
'mgfagency.twig.env.functions' => [
  'sayHello' => 'sayHello'
],
```

```twig
{# Prints 'Hello Jane' #}
{{ sayHello('Jane') }}
```

Or you could expose it as a Twig filter:

```php
'mgfagency.twig.env.filters' => [
  'sayHello' => 'sayHello'
],
```

```twig
{# Prints 'Hello Jane' #}
{{ 'Jane'|sayHello }}
```

I recommend sticking to the Twig function syntax, and only using Twig’s built-in filters. Of course, you should do what you like best.

### Using an anonymous function

Also anonymous functions (called closures in PHP) are accepted:

```php
'mgfagency.twig.env.functions' => [
  'sayHello' => function($who='') {
    return 'Hello' . (is_string($who) ? ' ' . $who : '');
  }
],
```

### Exposing static methods

You can also expose static methods, using the string syntax:

```php
'mgfagency.twig.env.functions' => [
  'setCookie' => 'Cookie::set',
  'getCookie' => 'Cookie::get',
],
```

```twig
{% do setCookie('test', 'real value') %}

{# Prints 'real value' #}
{{ getCookie('test', 'fallback') }}
```

### Marking a function’s output as safe

By default, Twig escapes strings returned by functions, to avoid security attacks such as cross-site scripting. This is why you often need to ask Twig to ouptut a raw, unescaped string:

```twig
{{ page.text.kirbytext | raw }}
```

Alternatively, when declaring a Twig function you can mark it as safe for HTML output by adding a `*` before its name, like this:

```php
'mgfagency.twig.env.functions' => [
  '*sayHello' => 'sayHello'
],
```


## Exposing and using classes

If you need to use PHP classes in your templates, I recommend two approaches:

1. Do it in a controller instead, and feed the resulting content to your templates. (Kirby documentation: [https://getkirby.com/docs/guide/templates/controllers](Controllers).)
2. Write a custom function that returns a class instance.

Let’s look at an example of that second solution:

```php
// site/plugins/coolplugin/src/verycoolthing.php
class VeryCoolThing
{
  // class implementation
}

// site/config/config.php
'mgfagency.twig.env.functions' => [
  'getCoolStuff' => function(){
    return new VeryCoolThing();
  }
],
```

Then in your templates, you can use that function to get a class instance:

```twig
{% set coolThing = getCoolStuff() %}
```

This example is simplistic; in practice, you might need to pass some parameters around to instantiate your class.

Alternatively, you could define and expose a generic function that allows instantiating any (known) PHP class:

```php
// site/config/config.php

/**
 * Make a class instance for the provided class name and parameters.
 */
'mgfagency.twig.env.functions' => [
  'new' => function($name) {
    if (!class_exists($name)) {
      throw new Twig_Error_Runtime("Unknown class \"$name\"");
    }
    $args = array_slice(func_get_args(), 1);
    if (count($args) > 0) {
      $reflected = new ReflectionClass($name);
      return $reflected->newInstanceArgs($args);
    }
    return new $name;
  }
],
```

Then in Twig templates:

```twig
{% set coolThing = new('VeryCoolThing') %}
```
