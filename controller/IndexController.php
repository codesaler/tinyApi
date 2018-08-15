<?php

namespace tiny\api\controller;

/**
 * Class IndexController
 *
 * @author LemonLone <lemonlone.com>
 */
class IndexController
{
    /**
     * index
     *
     * @return array
     */
    public function index(): array
    {
        return [
            'version' => _model('demo')->getVersion()
        ];
    }
}