<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Location2
 *
 * @author SWEDGE
 */
require_once ("Location.php");
class Location2 Extends Location {
    //put your code here
    private static $_locations = null;
    public static function getAll ($tracker=""){
      self::$_locations =  forward_static_call( array('Location','getAll'), $tracker);
//self::$locations  = Location::getAll($tracker);
return self::$_locations;
    }
}

?>
