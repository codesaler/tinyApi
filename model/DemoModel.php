<?php

namespace tiny\api\model;

/**
 * Class DemoModel
 *
 * @author LemonLone <lemonlone.com>
 */
class DemoModel extends AbstractModel
{
    /**
     * getVersion
     *
     * @return string
     */
    public function getVersion(): string
    {
        return 'v1.0.0';
    }
}