<?php

namespace Wilkques\Database\Queries\Grammar\Drivers;

use Wilkques\Database\Queries\Grammar\Grammar;
use Wilkques\Database\Queries\Grammar\GrammarInterface;

class MySql extends Grammar implements GrammarInterface
{    
    /**
     * @return string
     */
    public function lockForUpdate()
    {
        return "FOR UPDATE";
    }

    /**
     * @return string
     */
    public function sharedLock()
    {
        return "LOCK IN SHARE MODE";
    }
}