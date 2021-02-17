# Options documentation

## Customizing the Twig environment

```php
// Define a directory as a Twig namespace, that can be used as:
//   {% include '@mynamespace/something.twig' %}
'amteich.twig.namespaces' => [
  'mynamespace' => kirby()->roots()->index() . '/mydirectory',
],

// Load an extension
'amteich.twig.extension.intl' => 'Twig\\Extra\\Intl\\IntlExtension',

// Expose an existing function in templates
'amteich.twig.env.functions' => [
  'myfunction' => 'myCustomFunction'
],

// Expose an existing function in templates as a filter
'amteich.twig.env.filters' => [
  'myfilter' => 'myCustomFilter'
],

// Expose a twig test function for templates
'amteich.twig.env.tests' => [
  'of_type' => function ($var, $typeTest) {
      switch ($typeTest)
      {
        default:
          return false;
          break;

        case 'array':
          return is_array($var);
          break;

        case 'bool':
          return is_bool($var);
          break;

        case 'string':
          return is_string($var);
          break;
      }
  },
],
```

See [Using your own functions in templates](functions.md) for details about Twig functions and filters.

## Advanced

```php
// Should we use .php templates as fallback when .twig
// templates don't exist? Set to false to only allow Twig templates
'amteich.twig.usephp' => true

// Use Twig’s PHP cache?
// Enabling Twig's cache can give a speed boost to pages with changing
// content (e.g. a search result page), because Twig will use a compiled
// version of the template when building the response.
// But if you have static text content in your Twig templates, you won’t
// see content changes until you manually remove the `site/cache/twig` folder.
'amteich.twig.cache' => false

// Disable autoescaping or specify autoescaping type
// http://twig.sensiolabs.org/doc/api.html#environment-options
'amteich.twig.autoescape' => true

// Should Twig throw errors when using undefined variables or methods?
// Defaults to the value of the 'debug' option
'amteich.twig.strict' => option('debug', false)
```
