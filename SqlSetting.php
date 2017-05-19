<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:21
 */
class SqlSetting
{
    private $dblocation, $query, $id, $friendlyName;

    function __construct($id, $dblocation, $query, $friendlyName)
    {
        $this->id = $id;
        $this->dblocation = $dblocation;
        $this->query = $query;
        $this->friendlyName = $friendlyName;
    }

    public function getObject(){
        $object["id"] = $this->id;
        $object["dblocation"] = $this->dblocation;
        $object["query"] = $this->query;
        $object["name"] = $this->friendlyName;
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

    public function replaceVariable($varName, $newValue){
        $this->query = str_replace("{%" . $varName . "%}",$newValue,$this->query);
        $this->dblocation = str_replace("{%" . $varName . "%}",$newValue,$this->dblocation);
    }

}