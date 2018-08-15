<?php

namespace tiny\api\controller;

/**
 * Class AbstractController
 *
 * @author LemonLone <lemonlone.com>
 */
abstract class AbstractController
{
    /**
     * @var array
     */
    private $request = [];

    /**
     * AbstractController constructor.
     *
     * @param array $request
     */
    public function __construct(array $request)
    {
        $this->request = $request;
    }

    /**
     * param
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed|null
     */
    protected function param(string $key, $defaultValue = null)
    {
        return $this->request[$key] ?: $defaultValue;
    }
}