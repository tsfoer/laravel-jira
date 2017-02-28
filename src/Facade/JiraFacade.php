<?php

namespace Univerze\Jira\Facade;

use Illuminate\Support\Facades\Facade;

class JiraFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'jira';
    }
}