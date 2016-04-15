<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Role
 *
 * @author Swedge
 */
require_once('ITechTable.php');
class Role extends ITechTable{
        protected $_name = 'role';
	protected $_primary = 'id';

	const ADMIN_USER = 1;
        const FMOH_USER = 2;
        const PARTNER_USER = 3;
        const STATE_USER = 4;
        const LGA_USER = 5;
        
        
}

?>
