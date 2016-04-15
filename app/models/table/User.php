<?php
/*
 * Created on Feb 14, 2008
 *
 *  Built for web
 *  Fuse IQ -- todd@fuseiq.com
 *
 */

require_once('ITechTable.php');


class User extends ITechTable
{
   const ADMIN_USER = 1;
        const FMOH_USER = 2;
        const PARTNER_USER = 3;
        const STATE_USER = 4;
        const LGA_USER = 5;
        protected $_name = 'user';
	protected $_primary = 'id';

	public function insert(array $data) {
	    if ( isset($data['password']) )
	    	$data['password'] = md5($data['password']);

	    return parent::insert($data);
	}

	public function update(array $data,$where) {
	    if ( isset($data['password']) and $data['password'] )
	    	$data['password'] = md5($data['password']);

	    return parent::update($data,$where);
	}

	public function recordLogin($userRow) {
		if ( $userRow->id ) {
			$this->update(array('timestamp_last_login' => new Zend_Db_Expr('NOW()')), 'id = '.$userRow->id);
		}
	}

	public function updateLocale($locale, $id) {
		if ( $id ) {
			$this->update(array('locale' => $locale), 'id = '.$id);
		}
	}

    public function createAuthIdentity($userRow)
        {
            $identity = new stdClass;
            $identity->id = $userRow->id;
            $identity->username = $userRow->username;
            $identity->first_name = $userRow->first_name;
            $identity->last_name = $userRow->last_name;

            $identity->role = $userRow->role;
            $identity->province_id = $userRow->province_id;
            $identity->district_id = $userRow->district_id;
            $identity->region_c_id = $userRow->region_c_id;

            $identity->email = $userRow->email;
            $identity->locale = $userRow->locale;

            return $identity;
        }

 	static public function isUnique($username = false, $email = false) {
		$rtn = array();

		$userTable = new User();
    	$select = $userTable->select();

    	if ( $username )
    		$select->orWhere("username = ?",$username);

    	if ( $email )
    		$select->orWhere("email = ?",$email);

      	$rowset = $userTable->fetchAll($select);
		foreach ($rowset as $row) {
			if ( $row->email == $email ) {
				$rtn['email'] = 'found';
			}

			if ( $row->username == $username ) {
				$rtn['username'] = 'found';
			}
		}

		return $rtn;
 	}

 	public function hasPS($userid) {
 		// CHECK IF USER HAS PRE-SERVICE ACCESS
		$db = Zend_Db_Table_Abstract::getDefaultAdapter (); 
		$select = $db->query("select * from user_to_acl WHERE user_id = " . $userid . " AND acl_id = 'pre_service'");
		$row = $select->fetch();
		if ($row !== false){
			return true;
		} else {
			return false;
		}
 	}

 	public function hasIS($userid) {
 		// CHECK IF USER HAS IN-SERVICE ACCESS
		$db = Zend_Db_Table_Abstract::getDefaultAdapter (); 
		$select = $db->query("select * from user_to_acl WHERE user_id = " . $userid . " AND acl_id = 'in_service'");
		$row = $select->fetch();
		if ($row !== false){
			return true;
		} else {
			return false;
		}
 	}

 	/**
 	 * Called by ITechController
 	 * To view ACLs in an action function use Zend_Auth::getInstance()->getIdentity()->acls; or ITechController::_getACLs();
 	 */
 	static public function getACLs($user_id) {
	    $rtn = array();
		if ( $user_id ) {
			$userTable = new User();
	    	$select = $userTable->select()->setIntegrityCheck(false);
	    	$select->join(array('uacl' => 'user_to_acl'), 'uacl.user_id = user.id', 'uacl.acl_id');
	    	$select->where('uacl.user_id = ?',$user_id);

	      	$rowset = $userTable->fetchAll($select);
			foreach ($rowset as $row) {
				$rtn []= $row->acl_id;
			}
		}

		return $rtn;

 	}
        
        public function fetchAllUsers(){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
            $select = $db->select()
                         ->from (array('user'=>'user'));
            
            //echo $select->__toString();exit;
            $result = $db->fetchAll($select);
            return $result;
            
        }
        public function get_userid(){
              $auth = Zend_Auth::getInstance();
              $return  = 0;
                if ($auth->hasIdentity()) {
                    // Identity exists; get it
                    $identity = $auth->getIdentity();
                  // $identify = $identity;

                    foreach($identity as $identify){
                       $details_user[] = $identify;
                    }
                    //print_r($identity); exit;
                    $user = $details_user[0];
                    //$province_id = $details_user[5];
                    //$district_id = $details_user[6];
                    //$region_c_id = $details_user[7];
                    //$role = $details_user[4];
                    $return  =  $this->get_user_role($user);
                   
                } 
           return $return; 
        }
        
        public function deleteUsers(){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
           //$sql = "DELETE FROM user WHERE id IN ('189')";
            
           //$db->query($sql);
        }
        public function get_user_role($user){
            
             $db = Zend_Db_Table_Abstract::getDefaultAdapter ();

		$sql = "SELECT  role,province_id,district_id,region_c_id FROM user WHERE id ='".$user."'";
		$result = $db->fetchAll($sql);
                //print_r($result);
                //echo 'User id is '.$user;
                
                $role = $result[0]['role'];
                return $role;
        }
        public function is_user_an_admin(){
           $role = $this->get_userid();
           //echo user::ADMIN_USER;
           if($role==User::ADMIN_USER){
               return true;
           }
           else{
               
               return false;
           }
            
        }
          public function isUserAnFMOH(){
           $role = $this->get_userid();
           //echo user::ADMIN_USER;
           if($role==User::FMOH_USER){
               return true;
           }
           else{
               
               return false;
           }
            
        }
        public function isUserAnLga(){
           $role = $this->get_userid();
           //echo user::ADMIN_USER;
           if($role==User::LGA_USER){
               return true;
           }
           else{
               
               return false;
           }
              
        }
         public function isUserAState(){
           $role = $this->get_userid();
           //echo user::ADMIN_USER;
           if($role==User::STATE_USER){
               return true;
           }
           else{
               
               return false;
           }
              
        }
        
         public function isUserAPartner(){
           $role = $this->get_userid();
           //echo user::ADMIN_USER;
           if($role==User::PARTNER_USER){
               return true;
           }
           else{
               
               return false;
           }
              
        }
        public function UserAccessRoleAllowed(){
            if($this->is_user_an_admin() || $this->isUserAnFMOH()){
                return true;
            }else{
                return false;
            }
        }
        
}
