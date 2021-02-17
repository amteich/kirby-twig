<?php

namespace amteich\Twig;

use Exception;
use Kirby\Cms\App;
use Kirby\Toolkit\F;

/**
 * Twig Template Component for Kirby
 *
 * This component class extends Kirby’s built-in Kirby\Cms\Template
 * class and implements custom file() and render() methods. When rendering
 * a Twig template, instead of calling Tpl::load, we call:
 * Kirby\Twig\TwigEnv::renderPath
 *
 * @package  Kirby Twig Plugin
 * @author   Florens Verschelde <florens@fvsch.com>
 */
class Template extends \Kirby\Cms\Template
{
    private static $twig;

    /**
     * Creates a new template object
     *
     * @param string $name
     * @param string $type
     * @param string $defaultType
     */
    public function __construct(string $name, string $contentType = 'html', string $defaultType = 'html')
    {
        parent::__construct($name, $contentType, $defaultType);
        $viewPath    = dirname($this->file());
        static::$twig = new Environment($viewPath);
    }

    /**
     * Returns the expected template file extension
     *
     * @return string
     */
    public function extension(): string
    {
        return 'twig';
    }

    public function isTwig(): bool
    {
        $length = strlen($this->extension());
        return substr($this->file(), -$length) === $this->extension();
    }

    /**
     * Detects the location of the template file
     * if it exists.
     *
     * @return string|null
     */
    public function file(): ?string
    {
        $usephp = option('amteich.twig.usephp', true);
        $type = $this->type();

        if ($this->hasDefaultType() === true) {

            $base = $this->root() . '/' . $this->name();
            $twig = $base . '.twig';
            $php  = $base . '.php';

            if ($usephp and !is_file($twig) and is_file($php)) {
                return F::realpath($php, $this->root());
            } else {
                try {
                    return F::realpath($twig, $this->root());
                } catch (Exception $e) {
                    $path = App::instance()->extension($this->store(), $this->name());

                    if ($path !== null) {
                        return $path;
                    }
                }
            }

        }

        $name = $this->name() . '.' . $type;
        $base = $this->root() . '/' . $name;
        $twig = $base . '.twig';
        $php  = $base . '.php';

        // only check existing files if PHP template support is active
        if ($usephp and !is_file($twig) and is_file($php)) {
            return F::realpath($php, $this->root());
        } else {
            try {
                return F::realpath($twig, $this->root());
            } catch (Exception $e) {
                // Look for the template with type extension provided by an extension.
                // This might be null if the template does not exist.
                return App::instance()->extension($this->store(), $name);
            }
        }
    }

    /**
     * @param array $data
     * @return string
     */
    public function render(array $data = []): string
    {
        if ($this->isTwig()) {
            return static::$twig->renderPath($this->name() . '.' . $this->extension(), $data, true);
        }
        return parent::render($data);
    }
}
