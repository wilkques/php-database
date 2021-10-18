<?php

namespace Wilkques\Database;

interface GrammarInterface
{
    /**
     * @return string
     */
    public function getQuery();

    /**
     * @return static
     */
    public function getForPage();
}