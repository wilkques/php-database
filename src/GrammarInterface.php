<?php

namespace Wilkques\Database;

interface GrammarInterface
{
    /**
     * @param string $conditionQuery
     * 
     * @return static
     */
    public function withConditionQuery($conditionQuery);

    /**
     * @param string $key
     * @param string $condition
     * @param string $andOr
     * 
     * @return static
     */
    public function setConditionQuery($key, $condition, $andOr = "AND");

    /**
     * @return string
     */
    public function getConditionQuery();

    /**
     * @param mixed $conditionData
     * 
     * @return static
     */
    public function setConditionData($conditionData);

    /**
     * @return array
     */
    public function getConditionData();

    /**
     * @param array|string $key
     * @param string $condition
     * @param mixed $value
     * 
     * @return static
     */
    public function where($key, $condition = null, $value = null);

    /**
     * @param array|string $key
     * @param string $condition
     * @param mixed $value
     * 
     * @return static
     */
    public function orWhere($key, $condition = null, $value = null);

    /**
     * @param string $lock
     * 
     * @return static
     */
    public function setLock($lock = "");

    /**
     * @return static
     */
    public function lockForUpdate();

    /**
     * @return static
     */
    public function sharedLock();

    /**
     * @return string
     */
    public function getLock();
}