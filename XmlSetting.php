<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:21
 */
class XmlSetting
{

    private $fileLocation, $regexp, $replaceWith;

    public function __construct($fileLocation, $regexp, $replaceWith)
    {
        $this->fileLocation=$fileLocation;
        $this->regexp = $regexp;
        $this->replaceWith = $replaceWith;
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
        $array["fileLocation"] = $this->fileLocation;
        $array["regexp"] = $this->regexp;
        $array["replaceWith"] = $this->replaceWith;
        return $array;
    }

}