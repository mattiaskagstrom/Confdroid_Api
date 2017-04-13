<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:21
 */
class SqlSetting
{
    private $dblocation, $query;

    function __construct($dblocation, $query)
    {
        $this->dblocation = $dblocation;
        $this->query = $query;
    }

    public function getObject(){
        $object["dblocation"] = $this->dblocation;
        $object["query"] = $this->query;
        return $object;
    }

    /**
     * @return mixed
     */
    public function getDblocation()
    {
        return $this->dblocation;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

}