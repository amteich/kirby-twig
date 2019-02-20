# Rendering a template in PHP: the `twig` helper

This plugin also enables a `twig` PHP function for rendering template files and strings, like this:

```php
<?php

// Render a simple template from the site/snippets directory
echo twig('@snippets/header.twig');

// Same, but passing some additionnal variables
echo twig('@snippets/header.twig', ['sticky'=>false]);

// Render a string
echo twig('Hello {{ who }}', ['who'=>'World!']);
```

If you work with Twig templates for pages, you might not need the `twig()` helper at all.
