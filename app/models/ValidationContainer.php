<?php
/*
 * Created on Mar 7, 2008
 *
 *  Built for itechweb
 *  Fuse IQ -- todd@fuseiq.com
 *
 */

/**
 * Serialized to JSON or displayed in a view
 */
class ValidationContainer {
	public $status = null;
	public $messages = array();
	public $redirect = null;
	public $obj_id = null;

	protected static $instance = null;

	public function __construct() {
		self::$instance = $this;
	}

	static public function instance() {
		if ( self::$instance )
			return self::$instance;

		return new ValidationContainer();
	}

	public function addError($fieldname, $msg) {
		$this->messages[$fieldname] = ' ' . $msg;
	}

	public function checkRequired($controller, $name, $textName) {
		$val = $controller->getRequest()->getParam($name);
		if ( ($val === null) or ($val == '') ) {
    			//$this->addError($name,' (required)');
          		$this->addError($name, $textName.' ('.t('required').')');
			return false;
		}
		return true;
	}
	
	public function checkPercentage($controller, $val, $textName) {		
		if ( $val > 100 ) {
			$this->addError($val, $textName.' ('.t(' > 100').')');
			return false;
		}
		return true;
	}

        public function isValidDateChecker($controller, $fieldname, $textName, $dateString){
            require_once('Zend/Date.php');
            $rtn = true;
            $current_date = time();
            $current_year = date('Y');
            $parts = explode('-',$dateString);
            $ye = $parts[0];
            $me = $parts[1];
            $de = $parts[2];
            
             $mod = $ye%4;
            if($mod==0){
              $febDay = "29";
             }else{
               $febDay = "28";
              }
              
            $monthDays = array("01"=>"31","02"=>$febDay,"03"=>"31","04"=>"30","05"=>"31","06"=>"30","07"=>"31","08"=>"31","09"=>"30","10"=>"31","11"=>"30","12"=>"31");
             
            if(array_key_exists($me,$monthDays)){
                
                if($de!='00' && $de<=$monthDays[$me]){
                    $rtn = true;
                   
                }
                else{
                    
                    $rtn = false;
                }
             
            }else{
                
                $rtn = false;
            }
            
           if($de=='0' || $de=='00'){
                    $rtn = false;
                    
                }
                
           if(intval($ye) > $current_year){
                    $rtn = false;
                }
                
           if(intval($ye) < 1980){
                    $rtn = false;
                }
                
          
            
            $rtn = $rtn and Zend_Date::isDate($dateString, 'Y-m-d');
            if ( !$rtn )
   			$this->addError($fieldname, $textName.' '.t('is not a valid date').'.');

		return $rtn;
        }
        public function dateFormatter($dateString){
             $endDates = explode("/",$dateString);
                            $de = $endDates[0];
                           
                            $me = $endDates[1];
                            $ye = $endDates[2];
                            if(strlen($ye)==2){
                                $ye = "20".$ye;
                            }
                            if(strlen($de)==1){
                                $de = "0".$de;
                            }
                            if(strlen($me)==1){
                                $me = "0".$me;
                            }
                 return array($ye,$me,$de);
        }
	public function isValidDate($controller, $fieldname, $textName, $dateString) {
		require_once('Zend/Date.php');
                           // print_r($dateString);exit;
		$rtn = true;

		$parts = explode('-',$dateString);
		if ( intval($parts[1]) > 12 )
			$rtn = false;

		$parts = explode('-',$dateString);
		if ( intval($parts[2]) > 31 )
			$rtn = false;

		$parts = explode('-',$dateString);
		if ( intval($parts[0]) > 2200 )
			$rtn = false;

		$parts = explode('-',$dateString);
		if ( intval($parts[0]) < 1900 )
			$rtn = false;

		$rtn = $rtn and Zend_Date::isDate($dateString, 'Y-m-d');

		if ( !$rtn )
   			$this->addError($fieldname, $textName.' '.t('is not a valid date').'.');

		return $rtn;
	}


	 public function validateDate($date) {
            return (bool)strtotime($date);

            }

	public function hasError() {
		return count($this->messages);
	}

	public function setStatusMessage($msg) {
		$this->status .= $msg;
	}

	public function setRedirect($location, $addBasePath = true) {
		$this->redirect = ($addBasePath ? Settings::$COUNTRY_BASE_URL :'' ).$location;
	}

	public function hasRedirect() {
		return $this->redirect;
	}

	public function setObjectId($id) {
		$this->obj_id = $id;
	}
}