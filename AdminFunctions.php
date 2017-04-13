<?php

/**
 * Created by IntelliJ IDEA.
 * User: Elias
 * Date: 2017-04-12
 * Time: 15:44
 */
class AdminFunctions
{
    private $dbc;
    function __construct($dbc)
    {
        $this->dbc = $dbc;
    }
}