<?php

namespace Wilkques\Database\Queries\Grammar;

interface GrammarInterface
{
    /**
     * @return string
     */
    public function lockForUpdate();

    /**
     * @return string
     */
    public function sharedLock();
}