<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:21
 */
class XmlSetting
{

    private $fileLocation, $regexp, $replaceWith, $id, $friendlyName;

    public function __construct($id, $fileLocation, $regexp, $replaceWith, $friendlyName)
    {
        $this->id = $id;
        $this->fileLocation=$fileLocation;
        $this->regexp = $regexp;
        $this->replaceWith = $replaceWith;
        $this->friendlyName = $friendlyName;
    }

    /**
     * @return mixed
     */
    public function getFileLocation()
    {
        return $this->fileLocation;
    }

    /**
     * @return mixed
     */
    public function getRegexp()
    {
        return $this->regexp;
    }

    /**
     * @return mixed
     */
    public function getReplaceWith()
    {
        return $this->replaceWith;
    }

    public function getObject(){
        $array["id"] = $this->id;
        $array["fileLocation"] = $this->fileLocation;
        $array["regexp"] = $this->regexp;
        $array["replaceWith"] = $this->replaceWith;
        $array["name"] = $this->friendlyName;
        return $array;
    }

    public function replaceVariable($varName, $newValue){
        $this->fileLocation = str_replace("{%" . $varName . "%}",$newValue,$this->fileLocation);
        $this->replaceWith = str_replace("{%" . $varName . "%}",$newValue,$this->replaceWith);
    }
}