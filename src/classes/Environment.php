<?php

namespace amteich\Twig;

use Kirby;
use Response;
use Kirby\Cms\App;
use Kirby\Toolkit\Html;
use Kirby\Toolkit\Tpl;

use \Twig\Environment as Twig_Environment;
use \Twig\TwigFunction as Twig_Function;
use \Twig\TwigFilter as Twig_Filter;
use \Twig\TwigTest as Twig_Test;
use \Twig\Extension\DebugExtension as Twig_Extension_Debug;
use \Twig\Error\Error as Twig_Error;
use \Twig\Loader\FilesystemLoader as Twig_Loader_Filesystem;


/**
 * Wrapper for the Twig_Environment class, setting up an instance
 * with Kirby-specific configuration.
 *
 * @package  Kirby Twig Plugin
 * @author   Florens Verschelde <florens@fvsch.com>
 */
class Environment
{
    /** @var Twig_Environment */
    public $twig = null;

    /** @var boolean */
    public $debug = false;

    /** @var TwigEnv */
    private static $instance = null;

    /**
     * Kirby helper functions to expose as simple Twig functions
     *
     * We're exposing all helper functions documented in
     * https://getkirby.com/docs/cheatsheet#helpers
     * with just a few exceptions (sending email, saving files…)
     *
     * Prefix the function name with '*' to mark the
     * function's output as safe (avoiding HTML escaping).
     *
     * @var array
     */
    private $defaultFunctions = [
      '*attr' => 'attr',
      'asset' => 'asset',
      'collection' => 'collection',
      '*csrf' => 'csrf',
      '*csrf_field' => 'csrf_field',
      '*honeypot_field' => 'honeypot_field',
      '*css' => 'css',
      // Skipping: e - Twig syntax is simple: {{ condition ? 'a' : 'b' }}
      '*esc' => 'esc',
      'error' => 'amteich\Twig\Functions::error',
      'get' => 'get',
      '*gist' => 'gist',
      'go' => 'go',
      'gravatar' => 'gravatar',
      '*h' => 'h',
      '*html' => 'html',
      '*image' => 'image',
      'invalid' => 'invalid',
      '*js' => 'js',
      'kirby' => 'kirby',
      '*kirbytag' => 'kirbytag',
      '*kirbytags' => 'kirbytags',
      '*kirbytext' => 'kirbytext',
      '*markdown' => 'markdown',
      'option' => 'option',   // Get config value => 'option'
      'memory' => 'memory',
      '*multiline' => 'multiline',
      'page' => 'page',
      'pages' => 'pages',
      'param' => 'param',
      'params' => 'params',
      '*pattern' => 'pattern',
      // Skipping: r - Same reason as for ecco/e
      'timestamp' => 'timestamp',
      'site' => 'site',
      'size' => 'size',
      'slug' => 'Str::slug',
      '*smartypants' => 'smartypants',
      '*snippet' => 'snippet',
      '*strftime' => 'strftime',
      '*svg' => 'svg',
      't' => 't',
      'tc' => 'tc',
      '*twitter' => 'twitter',
      'u' => 'u',
      'url' => 'url',
      'url_build' => 'Url::build',
      '*video' => 'video',
      '*vimeo' => 'vimeo',
      '*widont' => 'widont',
      '*youtube' => 'youtube',
    ];

    /**
     * Default twig tests
     *
     * @var array
     */
    private $defaultTests = [
      'of_type' => 'amteich\Twig\Tests::of_type',
    ];

    private $templateDir = null;

    /**
     * Prepare the Twig environment
     * @throws Twig_Error_Loader
     */
    public function __construct(string $viewPath = '')
    {
        $kirby = Kirby::instance();
        $this->debug = option('debug');
        $this->templateDir = $kirby->root('templates');

        // Get environment & user config
        $options = [
            'core' => [
                'debug' => $this->debug,
                'strict_variables' => option('amteich.twig.strict', $this->debug),
                'autoescape' => option('amteich.twig.autoescape', 'html'),
                'cache' => false
            ],
            'namespace' => [
                'templates' => $this->templateDir,
                'snippets' => kirby()->roots()->snippets(),
                'plugins' => kirby()->roots()->plugins(),
            ],
            'paths' => [],
            'function' => array_merge(
                $this->defaultFunctions,
                option('amteich.twig.env.functions', [])
            ),
            'extension' => option('amteich.twig.env.extensions', []),
            'filter' => option('amteich.twig.env.filters', []),
            'test' => array_merge(
              $this->defaultTests,
              option('amteich.twig.env.tests', [])
          ),
        ];

        // Set cache directory
        if (option('amteich.twig.cache')) {
            $options['core']['cache'] = kirby()->roots()->cache() . '/twig';
        }

        // Set up the template loader
        $loader = new Twig_Loader_Filesystem($this->templateDir);

        // add site templates dir if environment got initialized by a plugintemplate
        if ($this->templateDir != $kirby->root('templates')) {
            $loader->addPath($kirby->root('templates'));
        }

        // is viewpath in a plugin, add the pluginpath
        if ($viewPath != $this->templateDir) {
            $loader->addPath($viewPath);
        }

        $options['namespace'] = array_merge(
            $options['namespace'],
            option('amteich.twig.namespaces', [])
        );

        $canSkip = ['snippets', 'plugins', 'assets'];
        foreach ($options['namespace'] as $key=>$path) {
            if (!is_string($path)) continue;
            if (in_array($key, $canSkip) && !file_exists($path)) continue;
            $loader->addPath($path, $key);
        }

        $options['paths'] = option('amteich.twig.paths', []);
        foreach ($options['paths'] as $path) {
            $loader->addPath($path);
        }

        // load plugin component paths
        foreach ($kirby->plugins() as $id => $plugin) {
            if (isset($plugin->extends()['twigcomponents']))
            {
                  $twigcomponents = $plugin->extends()['twigcomponents'];
                  if (is_callable($twigcomponents)) {
                    $twigcomponents = $twigcomponents();
                  }
                  $loader->addPath($twigcomponents);
              }
          }

        // Start up Twig
        $this->twig = new Twig_Environment($loader, $options['core']);

        // Enable Twig’s dump function
        $this->twig->addExtension(new Twig_Extension_Debug());

        // Plug in functions and filters
        foreach ($options['extension'] as $className) {
            $this->twig->addExtension(new $className());
        }
        foreach ($options['function'] as $name => $func) {
            $this->addCallable('function', $name, $func);
        }
        foreach ($options['filter'] as $name => $func) {
            $this->addCallable('filter', $name, $func);
        }
        foreach ($options['test'] as $name => $func) {
            $this->addCallable('test', $name, $func);
        }

        // Make sure the instance is stored / overwritten
        static::$instance = $this;
    }

    /**
     * Return a new instance or the cached instance if it exists
     * @return TwigEnv
     */
    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Render a Twig template from a file path,
     * similarly to how Tpl::load renders a PHP template
     * @param string $filePath
     * @param array $tplData
     * @param bool $return
     * @param bool $isPage
     * @return string
     * @throws Twig_Error
     */
    public function renderPath($filePath='', $tplData=[], $isPage=false)
    {
        // Remove the start of the templates path, since Twig asks
        // for a path starting from one of the registered directories.
        $path = ltrim(str_replace($this->templateDir, '',
            preg_replace('#[\\\/]+#', '/', $filePath)), '/');

        try {
            $content = $this->twig->render($path, $tplData);
        }
        catch (Twig_Error $err) {
            $content = $this->error($err, $isPage);
        }

        return $content;
    }

    /**
     * Render a Twig template from a string
     * @param  string $tplString
     * @param  array $tplData
     * @return string
     * @throws Twig_Error
     * @throws \Exception
     * @throws \Throwable
     */
    public function renderString($tplString='', $tplData=[])
    {
        try {
            return $this->twig->createTemplate($tplString)->render($tplData);
        }
        catch (Twig_Error $err) {
            return $this->error($err, false, $tplString);
        }
    }

    /**
     * Handle Twig errors, with different scenarios depending on if we're
     * rendering a full page or a fragment (e.g. when using the `twig` helper),
     * and if we're in debug mode or not.
     *
     *        | Page mode            | Fragment mode
     * -------|----------------------| --------------
     * Debug: | Custom error page    | Error message
     * -------|----------------------| --------------
     * Prod:  | Standard error page, | Empty string
     *        | or let error through |
     *
     * @param  Twig_Error $err
     * @param  boolean    $isPage
     * @param  string     $templateString
     * @return string|Response
     * @throws Twig_Error
     */
    private function error(Twig_Error $err, $isPage=false, $templateString=null)
    {
        if (!$this->debug) {
            if (!$isPage) return '';
            // Debug mode off: show the site's error page
            try {
                $kirby = Kirby::instance();
                $page = $kirby->site()->page($kirby->get('option', 'error'));
                if ($page) return $kirby->render($page);
            }
            // avoid loops
            catch (Twig_Error $err2) {
            }
            // Error page didn't exist or was buggy: rethrow the initial error
            // Can result in the 'fatal.php' white error page (in Kirby 2.4+
            // with Whoops active), or an empty response (white page).
            // That’s consistent with errors for e.g. missing base templates.
            throw $err;
        }

        // Gather information
        $sourceContext = $err->getSourceContext();
        $name = $sourceContext != null ? $sourceContext->getName() : '';
        $line = $err->getTemplateLine();
        $msg  = $err->getRawMessage();
        $path = null;
        $code = $templateString ? $templateString : '';
        if (!$templateString) {
            try {
                $source = $this->twig->getLoader()->getSourceContext($name);
                $path = $source->getPath();
                $code = $source->getCode();

            }
            catch (Twig_Error $err2) {}
        }

        // When returning a HTML fragment
        if (!$isPage && $this->debug) {
            $info = get_class($err) . ', line ' . $line . ' of ' .
                ($templateString ? 'template string:' : $name);
            $src  = $this->getSourceExcerpt($code, $line, 1, false);
            return '<b>Error:</b> ' . $info . "\n" .
                '<pre style="margin:0">'.$src.'</pre>' . "\n" .
                '➡ ' . $msg . "<br>\n";
        }

        // When rendering a full page with Twig: make a custom error page
        // Note for Kirby 2.4+: we don't use the Whoops error page because
        // it's not possible to surface Twig source code in it's stack trace
        // and code viewer. Whoops will only show the PHP method calls going
        // in in the Twig library. That's a know — but unresolved — issue.
        // https://github.com/filp/whoops/issues/167
        // https://github.com/twigphp/Twig/issues/1347
        // So we roll our own.
        $html = Tpl::load(dirname(__DIR__) . '/errorpage.php', [
            'title' => get_class($err),
            'subtitle' => 'Line ' . $line . ' of ' . ($path ? $path : $name),
            'message' => $msg,
            'code' => $this->getSourceExcerpt($code, $line, 6, true)
        ]);
        return new Response($html, 'html', 500);
    }

    /**
     * Extract a few lines of source code from a source string
     * @param string $source
     * @param int    $line
     * @param int    $plus
     * @param bool   $format
     * @return string
     */
    private function getSourceExcerpt($source='', $line=1, $plus=1, $format=false)
    {
        $excerpt = [];
        $twig  = Html::encode($source, false);
        $lines = preg_split("/(\r\n|\n|\r)/", $twig);
        $start = max(1, $line - $plus);
        $limit = min(count($lines), $line + $plus);
        for ($i = $start - 1; $i < $limit; $i++) {
            if ($format) {
                $attr = 'data-line="'.($i+1).'"';
                if ($i === $line - 1) $excerpt[] = "<mark $attr>$lines[$i]</mark>";
                else $excerpt[] = "<span $attr>$lines[$i]</span>";
            }
            else {
                $excerpt[] = $lines[$i];
            }
        }
        return implode("\n", $excerpt);
    }

    /**
     * Expose a function to the Twig environment as a function or filter
     * @param string $type
     * @param string $name
     * @param string|Closure $func
     */
    private function addCallable($type, $name, $func)
    {
        if (!is_string($name) || !is_callable($func)) {
            return;
        }
        $twname = trim($name, '*');
        $params = [];
        if (strpos($name, '*') === 0) {
            $params['is_safe'] = ['html'];
        }
        if ($type === 'function') {
            $this->twig->addFunction(new Twig_Function($twname, $func, $params));
        }
        if ($type === 'filter') {
            $this->twig->addFilter(new Twig_Filter($twname, $func, $params));
        }
        if ($type === 'test') {
            $this->twig->addTest(new Twig_Test($twname, $func));
        }
    }
}
