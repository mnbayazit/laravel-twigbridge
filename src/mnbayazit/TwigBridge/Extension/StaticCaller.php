<?php namespace mnbayazit\TwigBridge\Extension;

class StaticCaller {

    /** @var string */
    protected $className;
    /** @var array */
    protected $options;

    /**
     * Create a new StaticCaller instance.
     * @param string $className The class to call
     * @param array $options
     */
    public function __construct($className, $options = []) {
        $this->className = $className;
        $this->options = array_merge(array(
            'is_safe' => null,
            'charset' => 'UTF-8',
        ), $options);
    }

    /**
     * Dynamically call a method on the class
     *
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments) {

        $is_safe = $this->options['is_safe'] === true
            || (is_string($this->options['is_safe']) && preg_match('~\A(?:' . str_replace('~', '\~', $this->options['is_safe']) . ')\Z~', $method) > 0)
            || (is_array($this->options['is_safe']) && in_array($method, $this->options['is_safe']));

        $result = forward_static_call_array([$this->className, $method], $arguments);

        return $is_safe && (is_string($result) || method_exists($result, '__toString')) ? new \Twig_Markup($result, $this->options['charset']) : $result;
    }
}