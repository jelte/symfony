<?php

namespace Symfony\Component\Form\Extension\Profiler;

use Symfony\Component\Profiler\ProfileData\ProfileDataInterface;

class FormData implements ProfileDataInterface
{
    private $forms;
    private $nbErrors;

    public function __construct(array $forms, $nbErrors)
    {
        $this->forms = $forms;
        $this->nbErrors = $nbErrors;
    }

    public function getForms()
    {
        return $this->forms;
    }

    public function getNbErrors()
    {
        return $this->nbErrors;
    }
}