<?php
namespace mnbayazit\TwigBridge\Extension;

use Twig_Environment;
use Twig_Extension;

class FacadeExtension extends Twig_Extension {
    protected $facades;
    protected $charset;

    public function __construct($facades) {
        $this->facades = $facades;
    }

    public function initRuntime(Twig_Environment $environment) {
        $this->charset = $environment->getCharset();
    }

    /**
     * {@inheritDoc}
     */
    public function getName() {
        return 'facade_extension';
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobals() {
        $globals = array();
        foreach($this->facades as $key => $val) {
            if(is_int($key)) {
                $className = $val;
                $options = [];
            } else {
                $className = $key;
                $options = $val;
            }
            $globals[$className] = new StaticCaller($className, array_merge(array(
                'is_safe' => null,
                'charset' => $this->charset,
            ), $options));
        }
        return $globals;
    }

}
