<?php

namespace Wilkques\Database\Connections;

interface ResultInterface
{
    /**
     * @param int $mode
     * 
     * @return mixed|false
     *
     * @throws \Exception
     */
    public function fetch($mode = \PDO::FETCH_ASSOC);

    /**
     * @param int $mode
     * 
     * @return list<mixed>
     *
     * @throws \Exception
     */
    public function fetchAll($mode = \PDO::FETCH_ASSOC);

    /**
     * @return int
     */
    public function rowCount();
}
