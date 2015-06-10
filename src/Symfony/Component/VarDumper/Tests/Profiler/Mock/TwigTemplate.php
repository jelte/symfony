<?php

namespace Symfony\Component\VarDumper\Tests\Profiler\Mock;

use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\Data;

class TwigTemplate extends \Twig_Template
{
    private $dumper;

    public function __construct(\Twig_Environment $environment, VarDumper $dumper)
    {
        parent::__construct($environment);
        $this->dumper = $dumper;
    }

    /**
     * Returns the template name.
     *
     * @return string The template name
     */
    public function getTemplateName()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function display(array $context, array $blocks = array())
    {
        $this->dumper->dump(new Data($context));
    }

    /**
     * Auto-generated method to display the template with the given context.
     *
     * @param array $context An array of parameters to pass to the template
     * @param array $blocks  An array of blocks to pass to the template
     */
    protected function doDisplay(array $context, array $blocks = array())
    {
    }

    public function getZero()
    {
        return 0;
    }

    public function getEmpty()
    {
        return '';
    }

    public function getString()
    {
        return 'some_string';
    }

    public function getTrue()
    {
        return true;
    }

    public function getDebugInfo()
    {
        return array(
            32 => 32,
        );
    }

    protected function doGetParent(array $context)
    {
    }

    public function getAttribute($object, $item, array $arguments = array(), $type = \Twig_Template::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
    {
        return parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
    }
}
