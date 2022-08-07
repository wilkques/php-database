<?php

namespace Wilkques\Database\Queries\Grammar;

class MySql extends Grammar
{
    /**
     * @return static
     */
    public function lockForUpdate()
    {
        return $this->setLock(" FOR UPDATE");
    }

    /**
     * @return static
     */
    public function sharedLock()
    {
        return $this->setLock(" LOCK IN SHARE MODE");
    }
}