<?php
/*
* Created on Feb 27, 2008
*
*  Built for web
*  Fuse IQ -- todd@fuseiq.com
*
*/
require_once ('ReportFilterHelpers.php');
require_once ('models/table/User.php');
require_once ('models/ValidationContainer.php');
require_once ('Zend/Validate/EmailAddress.php');
require_once ('Zend/Mail.php');
require_once ('models/table/MultiOptionList.php');
require_once ('models/table/Report.php');
require_once('models/table/Helper.php');
require_once('models/table/Location.php');

class UserController extends ReportFilterHelpers {

	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array()) {
		parent::__construct ( $request, $response, $invokeArgs );
	}

	public function init() {
	}

	public function preDispatch() {
		parent::preDispatch ();

	}

	public function addAction() {

            
		if ((! $user_id = $this->isLoggedIn ()) or (! $this->hasACL ( 'add_edit_users' ))) {
			$this->doNoAccessError ();
		}
                //$auth = Zend_Auth::getInstance ();
                //$read = $auth->getStorage()->read();
                $report = new Report();
		$request = $this->getRequest ();
		$validateOnly = $request->isXmlHttpRequest ();
		if ($validateOnly)
		$this->setNoRenderer ();

		$userObj = new User ( );
		$userRow = $userObj->createRow ();

		$status = ValidationContainer::instance ();
		$userArray = $userRow->toArray ();
		$userArray['acls'] = User::getACLs ( $user_id ); //set acls
		$this->viewAssignEscaped ( 'user', $userArray );
                
                $locations = Location::getAll();
		$this->viewAssignEscaped('locations', $locations);
                //var_dump($locations); exit;
                
		if ($request->isPost()) {
                    //echo 'post requesttttt'; exit;
                    
                    $province_id = $this->getSanParam('province_id');
                    $province_id = $report->formatSelection($province_id);
                    $zone = $report->explodeGeogArray($province_id,"1");
                    
                    $district_id = $this->getSanParam('district_id');
                    $district_id = $report->formatSelection($district_id);
                    $state = $report->explodeGeogArray($district_id, "2");
                    
                    $region_c_id = $this->getSanParam('region_c_id');
                    $region_c_id = $report->formatSelection($region_c_id);
                    $localgovernment = $report->explodeGeogArray($region_c_id, "3");
               
                    
                    
                    $role = $this->getSanParam('role');
                    
                    //validate
                    $status->checkRequired ( $this, 'first_name', 'First name' );
                    $status->checkRequired ( $this, 'last_name', 'Surname' );
                    $status->checkRequired ( $this, 'username', 'Login' );
                    $status->checkRequired ( $this, 'email', 'Email' );
                    $status->checkRequired ( $this, 'role', 'Role');
                    

			//valid email?
			$validator = new Zend_Validate_EmailAddress ( );

			if (!$validator->isValid ( $this->_getParam ( 'email' ) )) {
				$status->addError ( 'email', 'That email address does not appear to be valid.' );
			}

			if (strlen ( $this->_getParam ( 'username' ) ) < 3) {
				$status->addError ( 'username', 'Usernames should be at least 3 characters in length.' );
			}
                        if($role==""){
                                $status->addError ( 'role', 'You need to select the role of the user you are about to create' );
			
                        }
			$status->checkRequired ( $this, 'password', 'Password' );
			//check unique username and email
			if ($uniqueArray = User::isUnique ( $this->getSanParam ( 'username' ), $this->_getParam ( 'email' ) )) {
				if (isset ( $uniqueArray ['email'] ))
				$status->addError ( 'email', 'That email address is already in use. Please choose another one.' );
				if (isset ( $uniqueArray ['username'] ))
				$status->addError ( 'username', 'That username is already in use. Please choose another one.' );
			}

			if (strlen ( $this->_getParam ( 'password' ) ) < 6) {
				$status->addError ( 'password', 'Passwords should be at least 6 characters in length.' );
			}
                        
                       /* if($province_id=="" && empty($district_id) && empty($region_c_id)){
                            $status->addError ( 'province_id', 'The Location of the user must be completely filled' );
                        
                            
                        }*/
                         if($role=="3" && empty($province_id)){
                            $status->addError ( 'province_id', 'Geo Zone is a required field for user with role Partner' );
                        
                        }
                        else if($role=="4" && empty($district_id)){
                           $status->addError ( 'district_c_id', 'State is a required field for user with role State' );
                         
                        }
                        else if($role=="5" && empty($region_c_id)){
                           $status->addError ( 'region_c_id', 'LGA is a required field for user with role LGA' );
                         
                        }
                        
			if ($status->hasError ()) {
				$status->setStatusMessage ( 'The user could not be saved.' );
			} else {
                            
                           
                            
                          
                            $details = $this->_getAllParams ();
                            if(!empty($district_id)){
                            $partnerLocation = json_encode($district_id);
                            }
                        
                            
                            if($role=="3" || $role ==3 ){
                            $details['province_id'] = ($zone[0]!="")?$zone[0] : 0; 
                               
                            $details['district_id'] = ($state[0]!="")?$state[0] : 0; 
                            
                            
                            $details['region_c_id'] = ($localgovernment[0]!="")?$localgovernment[0] : 0; 
                            
                            if($partnerLocation!=""){
                            $details['multiple_locations_id'] = $partnerLocation;
                            }else{
                                $details['multiple_locations_id'] = "";
                            }
                            }else{
                            $details['province_id'] = ($zone[0]!="")?$zone[0] : 0;
                            $details['district_id'] = ($state[0]!="")?$state[0] : 0; 
                            $details['region_c_id'] = ($localgovernment[0]!="")?$localgovernment[0] : 0;  
                            $details['multiple_locations_id']  ="";
                            }
                           
                         
                         //print_r($details);exit;
                           //print_r($userRow);exit;
                            //echo $zone.' state '.$details.' lga '.$lga;exit;
                             //print_r($this->_getAllParams ());exit;
				if ($this->_getParam ( 'send_email' )) {

					$view = new Zend_View ( );
					$view->setScriptPath ( Globals::$BASE_PATH . '/app/views/scripts/email' );
					$view->assign ( 'first_name', $this->_getParam ( 'first_name' ) );
					$view->assign ( 'username', $this->_getParam ( 'username' ) );
					$view->assign ( 'password', $this->_getParam ( 'password' ) );
					$text = $view->render ( 'text/new_account.phtml' );
					$html = $view->render ( 'html/new_account.phtml' );

					try {
						$mail = new Zend_Mail ( );
						$mail->setBodyText ( $text );
						$mail->setBodyHtml ( $html );
						$mail->setFrom ( Settings::$EMAIL_ADDRESS, Settings::$EMAIL_NAME );
						$mail->addTo ( $this->_getParam ( 'email' ), $this->_getParam ( 'first_name' ) . " " . $this->_getParam ( 'last_name' ) );
						$mail->setSubject ( 'New Account Created' );
						$mail->send ();
					} catch (Exception $e) {

					}

				}

                               
				self::fillFromArray ( $userRow, $details );
                               
				$userRow->is_blocked = 0;
				if ($id = $userRow->save ()) {
					$status->setStatusMessage ( 'The new user was created.' );
					$this->saveAclCheckboxes ( $id );
				} else {
					$status->setStatusMessage ( 'The user could not be saved.' );
				}

			}
		}

		if ($validateOnly) {
			$this->sendData ( $status );
		} else {
			$training_organizer_array = MultiOptionList::choicesList ( 'user_to_organizer_access', 'user_id', 0, 'training_organizer_option', 'training_organizer_phrase', false, false );
			$this->viewAssignEscaped ( 'training_organizer', $training_organizer_array );

			$this->view->assign ( 'status', $status );

			if ($this->hasACL ( 'pre_service' )) {
				$helper = new Helper();
				$this->view->assign ('showinstitutions',true);
				$this->view->assign ('institutions',$helper->getInstitutions());

 				$this->view->assign('showprograms', true);
                $this->view->assign('programs', $helper->getPrograms());

				// Getting current credentials
				$auth = Zend_Auth::getInstance ();
				$identity = $auth->getIdentity ();

				$this->view->assign ('userinstitutions',$helper->getUserInstitutions($user_id));
                $this->view->assign('userprograms', $helper->getUserPrograms($user_id));
			} else {
				$this->view->assign ('showinstitutions',false);
          		$this->view->assign('showprograms', false);
			}
		}
	}

	protected function saveAclCheckboxes($user_id) {
		//save Access Level stuff
		$acl = array ();
		// all acls available and training_organizer_all except: 'master_approver' - this is done on the approvers page
		//TA: added 7/22/2014 'acl_editor_tutor_specialty' and 'acl_editor_tutor_contract' to the list
		//TA:10: add to this list 'ps_edit_student', 'ps_view_student', 'ps_edit_student_grades', 'ps_view_student_grades'
		//TA:17: 09/19/2014 add 'acl_editor_commodityname'
		//TA:17:12: 10/03/2014 add 'acl_editor_commoditytype'
		//TA:17:12: 10/04/2014 add 'add_new_facility'
		//BS:#3,#4: add edit_partners, edit_mechanisms 20141014
		//RR:11/17/2014 add 'edit_studenttutorinst', 'acl_delete_ps_cohort', 'view_studenttutorinst', 'acl_delete_ps_student', 'acl_delete_ps_grades'
		$checkboxes = array('training_organizer_all','training_organizer_option_all', 'in_service', 'edit_course', 'view_course', 'edit_people', 
				'view_people', 'edit_facility', 'view_create_reports', 'employees_module', 'edit_country_options', 
				'add_edit_users', 'training_organizer_option_all', 'training_title_option_all', 'approve_trainings', 
				'admin_files', 'use_offline_app', 'pre_service', 'facility_and_person_approver', 'edit_evaluations', 
				'duplicate_training', 'acl_editor_training_category', 'acl_editor_people_qualifications', 
				'acl_editor_people_responsibility', 'acl_editor_training_organizer', 'acl_editor_people_trainer', 'acl_editor_training_topic', 
				'acl_editor_people_titles', 'acl_editor_training_level', 'acl_editor_people_trainer_skills', 'acl_editor_pepfar_category', 
		        'acl_editor_people_languages', 'acl_editor_funding', 'acl_editor_people_affiliations', 'acl_editor_recommended_topic', 'acl_editor_nationalcurriculum', 
		        'acl_editor_people_suffix', 'acl_editor_method', 'acl_editor_people_active_trainer', 'acl_editor_facility_types', 'acl_editor_ps_classes', 
		        'acl_editor_facility_sponsors', 'acl_editor_ps_cadres', 'acl_editor_ps_degrees', 'acl_editor_ps_funding', 'acl_editor_ps_institutions', 
		        'acl_editor_ps_languages', 'acl_editor_ps_nationalities', 'acl_editor_ps_joindropreasons', 'acl_editor_ps_sponsors', 'acl_editor_ps_tutortypes', 
		        'acl_editor_ps_coursetypes', 'acl_editor_ps_religions', 'add_edit_users', 'acl_admin_training', 'acl_admin_people', 'acl_admin_facilities', 
		        'acl_editor_refresher_course', 'import_training', 'import_training_location', 'import_facility', 'import_person', 'acl_editor_tutor_specialty', 
		        'acl_editor_tutor_contract', 'acl_editor_commodityname', 'acl_editor_commoditytype', 'add_new_facility',
		        'edit_employee', 'edit_partners', 'edit_mechanisms', 'edit_training_location','edit_studenttutorinst', 'acl_delete_ps_cohort', 'acl_delete_ps_grades', 'view_studenttutorinst',
				'acl_delete_ps_student', 'edit_commodity','view_commodity','acl_restrict'
		); 
		foreach ($checkboxes as $value) {
			$acl [$value] = ( ( $this->_getParam ( $value ) == $value || $this->_getParam($value) == 'on' ) ? $value : null);
		}
               // print_r($checkboxes);
               // print_r($acl);
                //echo '<br/><br/>';

		$checkboxes = array(
			//'ps_edit_student' => 'ps_view_student', //TA:10: added 8/15/2014
			//	'ps_edit_student_grades' => 'ps_view_student_grades', //TA:10: added 8/15/2014

			'edit_course'            => 'view_course',
			'edit_people'            => 'view_people',
		    'edit_facility'          => 'view_facility',
                   
		    // BS:#3,#4:20141015
		    'edit_employee'          => 'view_employee',
		    'edit_partners'          => 'view_partners',
		    'edit_mechanisms'        => 'view_mechanisms',
		    'edit_training_location' => 'view_training_location',
                    'edit_commodity'         => 'view_commodity',
                    
		);
               
              //   echo '<br/><br/>';
		foreach ($checkboxes as $key => $value) {
                    
			$acl [$value] = ( $this->_getParam ( $key ) == $value ? $value : null );
                      // print_r($this->_getParam ( $key ));
                      //echo '==>'.$value;
                        //echo '\n';
		}
                
                
               // print_r($acl);
               //echo '<br/><br/><br/>';
             
             //
            //print_r($this->_getParam ( 'training_organizer_option_id' ));
             //exit;
		//Helper2::jLog(print_r($acl,true));
		MultiOptionList::updateOptions ( 'user_to_acl', 'acl', 'user_id', $user_id, 'acl_id', $acl );
		MultiOptionList::updateOptions ( 'user_to_organizer_access', 'training_organizer_option', 'user_id', $user_id, 'training_organizer_option_id', $this->_getParam ( 'training_organizer_option_id' ) );

		// Capturing the institution access if necessary

		if ($this->hasACL ( 'pre_service' )) {
			// Getting current credentials
			$auth = Zend_Auth::getInstance ();
			$identity = $auth->getIdentity ();

			$helper = new Helper();
			//$helper->saveUserInstitutions($identity->id, is_array($this->_getParam ('institutionselect')) ? $this->_getParam ('institutionselect') : array());
			$helper->saveUserInstitutions($user_id, is_array($this->_getParam ('institutionselect')) ? $this->_getParam ('institutionselect') : array());
			$helper->saveUserPrograms($user_id, is_array($this->_getParam('programselect')) ? $this->_getParam('programselect') : array());
		}
                //exit;
	}
	


	public function logoutAction() {
		require_once ('Zend/Auth.php');
		$auth = Zend_Auth::getInstance ();
		$auth->clearIdentity ();
		$this->_redirect ( 'index/index' );
	}

	public function indexAction() {
		$this->_forward ( 'myaccount' );
	}

	public function searchAction() {

		if (! $this->isLoggedIn ())
		$this->doNoAccessError ();

		if (! $this->hasACL ( 'add_edit_users' )) {
			$this->doNoAccessError ();
		}

	}

	public function listAction() {

		if (! $this->isLoggedIn ())
		$this->doNoAccessError ();

		if (! $this->hasACL ( 'add_edit_users' )) {
			$this->doNoAccessError ();
		}

		require_once ('models/table/OptionList.php');
		$rowArray = OptionList::suggestionList ( 'user', array ('id', 'first_name', 'last_name', 'email', 'username', 'is_blocked' ), false, 1000 );

		$rtn = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$rtn [] = $val;
		}

		$this->sendData ( $rtn );
	}

	public function myaccountAction() {

		if (! $this->isLoggedIn ())
		$this->doNoAccessError ();

		if (! $user_id = $this->isLoggedIn ()) {
			$this->doNoAccessError ();
		}

		if ($this->view->mode == 'edit') {
			$user_id = $this->getSanParam ( 'id' );
		}
                $locations = Location::getAll();
                $report = new Report();
                
		$this->viewAssignEscaped('locations', $locations);
		$request = $this->getRequest ();
		$validateOnly = $request->isXmlHttpRequest ();
		if ($validateOnly)
		$this->setNoRenderer ();

		$user = new User ( );
		$userRow = $user->find ( $user_id )->current ();

		if ($request->isPost ()) {
			$status = ValidationContainer::instance ();
                   
                    $province_id = $this->getSanParam('province_id');
                    $province_id = $report->formatSelection($province_id);
                    $zone = $report->explodeGeogArray($province_id,"1");
                    
                    $district_id = $this->getSanParam('district_id');
                    $district_id = $report->formatSelection($district_id);
                    $state = $report->explodeGeogArray($district_id, "2");
                    
                    $region_c_id = $this->getSanParam('region_c_id');
                    $region_c_id = $report->formatSelection($region_c_id);
                    $localgovernment = $report->explodeGeogArray($region_c_id, "3");
                    
                    $role = $this->getSanParam('role');
                    
			//validate
			$status->checkRequired ( $this, 'first_name', 'First name' );
			$status->checkRequired ( $this, 'last_name', 'Surname' );
			$status->checkRequired ( $this, 'username', 'Login' );
			$status->checkRequired ( $this, 'email', 'Email' );

			//valid email?
			$validator = new Zend_Validate_EmailAddress ( );
			if (! $validator->isValid ( $this->_getParam ( 'email' ) )) {
				$status->addError ( 'email', 'That email address does not appear to be valid.' );
			}
			if (strlen ( $this->_getParam ( 'username' ) ) < 3) {
				$status->addError ( 'username', 'Usernames should be at least 3 characters in length.' );
			}

			//changing usernames?
			if ($this->_getParam ( 'username' ) != $userRow->username) {
				//check unique username and email
				if ($uniqueArray = User::isUnique ( $this->getSanParam ( 'username' ) )) {
					if (isset ( $uniqueArray ['username'] ))
					$status->addError ( 'username', 'That username is already in use. Please choose another one.' );
				}
			}
			//changing email?
			if ($this->_getParam ( 'email' ) != $userRow->email) {
				//check unique username and email
				if ($uniqueArray = User::isUnique ( false, $this->getSanParam ( 'email' ) )) {
					if (isset ( $uniqueArray ['email'] ))
					$status->addError ( 'email', 'That email address is already in use. Please choose another one.' );
				}
			}

			//changing passwords?
			$passwordChange = false;
			if (strlen ( $this->_getParam ( 'password' ) ) > 0 and strlen ( $this->_getParam ( 'confirm_password' ) ) > 0) {
				if (strlen ( $this->_getParam ( 'password' ) ) < 6) {
					$status->addError ( 'password', 'Passwords should be at least 6 characters in length.' );
				}
				if ($this->_getParam ( 'password' ) != $this->_getParam ( 'confirm_password' )) {
					$status->addError ( 'password', 'Password fields do not match. Please enter them again.' );
				}
				$passwordChange = true;
			}

                        
                        if($role=="3" && empty($province_id)){
                            $status->addError ( 'province_id', 'Geo Zone is a required field for user with role Partner' );
                        
                        }
                        else if($role=="4" && empty($district_id)){
                           $status->addError ( 'district_c_id', 'State is a required field for user with role State' );
                         
                        }
                        else if($role=="5" && empty($region_c_id)){
                           $status->addError ( 'region_c_id', 'LGA is a required field for user with role LGA' );
                         
                        }
			if ($status->hasError ()) {
				$status->setStatusMessage ( 'Your account information could not be saved.' );
			} else {
				$params = $this->_getAllParams ();
                                
                            if(!empty($district_id)){
                            $partnerLocation = json_encode($district_id);
                            }
                        
                            
                            if($role=="3" || $role ==3 ){
                            $params['province_id'] = ($zone[0]!="")?$zone[0] : 0; 
                               
                            $params['district_id'] = ($state[0]!="")?$state[0] : 0; 
                            
                            
                            $params['region_c_id'] = ($localgovernment[0]!="")?$localgovernment[0] : 0; 
                            
                            if($partnerLocation!=""){
                            $params['multiple_locations_id'] = $partnerLocation;
                            }else{
                            $params['multiple_locations_id'] = "";
                            }
                            }else{
                            $params['province_id'] = ($zone[0]!="")?$zone[0] : 0;
                            $params['district_id'] = ($state[0]!="")?$state[0] : 0; 
                            $params['region_c_id'] = ($localgovernment[0]!="")?$localgovernment[0] : 0;  
                            $params['multiple_locations_id']  ="";
                            }
                            
                            
				if (! $passwordChange) {
					unset ( $params ['password'] );
				}

				self::fillFromArray ( $userRow, $params );

				if ($userRow->save ()) {
					$status->setStatusMessage ( 'Your account information was saved.' );
					if ($this->view->mode == 'edit')
					$this->saveAclCheckboxes ( $user_id );

					if($passwordChange == true) {
						$email = $this->_getParam ( 'email' );
						if (trim($email) != '') {
							$view = new Zend_View ( );
							$view->setScriptPath ( Globals::$BASE_PATH . '/app/views/scripts/email' );
							$view->assign ( 'first_name', $this->_getParam ( 'first_name' ) );
							$view->assign ( 'username', $this->_getParam ( 'username' ) );
							$view->assign ( 'password', $this->_getParam ( 'password' ) );
							$text = $view->render ( 'text/password_changed.phtml' );
							$html = $view->render ( 'html/password_changed.phtml' );

							try {
								$mail = new Zend_Mail ( );
								$mail->setBodyText ( $text );
								$mail->setBodyHtml ( $html );
								$mail->setFrom ( Settings::$EMAIL_ADDRESS, Settings::$EMAIL_NAME );
								$mail->addTo ( $this->_getParam ( 'email' ), $this->getSanParam ( 'first_name' ) . " " . $this->getSanParam ( 'last_name' ) );
								$mail->setSubject ( 'Password Changed');
								$mail->send ();
							} catch (Exception $e) {

							}
						}
					}
				} else {
					$status->setStatusMessage ( 'Your account information could not be saved.' );
				}
			}

			if ($validateOnly) {
				$this->sendData ( $status );
			} else {
				$this->view->assign ( 'status', $status );
			}
		}

		$userArray = $userRow->toArray ();

		if ($this->view->mode == 'edit') {
			//set acls
			$acls = User::getACLs ( $user_id );
			$userArray ['acls'] = $acls;
                        if($userArray['role']=="3"){
    $userLocations = json_decode($userArray['multiple_locations_id'],true);
    //$zonesArray = $report->explodeGeogArray($userLocations, "1");
    //$stateArray = $report->explodeGeogArray($userLocations, "2");
$userArray['province_id'] = $userLocations;
$userArray['district_id'] = $userLocations;
}
		}

		$training_organizer_array = MultiOptionList::choicesList ( 'user_to_organizer_access', 'user_id', $user_id, 'training_organizer_option', 'training_organizer_phrase', false, false );
		$this->viewAssignEscaped ( 'training_organizer', $training_organizer_array );
		$this->viewAssignEscaped ( 'user', $userArray );

        if ($this->hasACL('pre_service')) {
            $helper = new Helper();
            $this->view->assign('showinstitutions', true);
            $this->view->assign('institutions', $helper->getInstitutions());
            $this->view->assign('showprograms', true);
            $this->view->assign('programs', $helper->getprograms());
            
            // Getting current credentials
            $auth = Zend_Auth::getInstance();
            $identity = $auth->getIdentity();
            
            $this->view->assign('userinstitutions', $helper->getUserInstitutions($user_id, false));
            $this->view->assign('userprograms', $helper->getUserprograms($user_id, false));
        } else {
            $this->view->assign('showprograms', false);
            $this->view->assign('showinstitutions', false);
        }

	}

	public function deleteAction() {
		if ((! $user_id = $this->isLoggedIn ()) or (! $this->hasACL ( 'add_edit_users' ))) {
			$this->doNoAccessError ();
		}

		$status = ValidationContainer::instance ();
		$id = $this->getSanParam ( 'id' );

		if ($user_id = $this->getSanParam ( 'id' )) {
			$user = new User ( );
			$rows = $user->find ( $user_id );
			$row = $rows->current ();
			if ($row) {
				$row->is_blocked = 1;
				$row->save ();
			}
			$status->setStatusMessage ( 'That user was blocked.' );
			$this->_redirect ( 'user/search' );
		} else if (! $user_id) {
			$status->setStatusMessage ( 'That user could not be found.' );
		}

		//validate
		$this->view->assign ( 'status', $status );

	}

	public function activateAction() {
		if ((! $user_id = $this->isLoggedIn ()) or (! $this->hasACL ( 'add_edit_users' ))) {
			$this->doNoAccessError ();
		}

		$status = ValidationContainer::instance ();
		$id = $this->getSanParam ( 'id' );

		if ($user_id = $this->getSanParam ( 'id' )) {
			$user = new User ( );
			$rows = $user->find ( $user_id );
			$row = $rows->current ();
			if ($row) {
				$row->is_blocked = 0;
				$row->save ();
			}
			$status->setStatusMessage ( t('That user was activated.') );
			$this->_redirect ( 'user/search' );

		} else if (! $user_id) {
			$status->setStatusMessage ( t( 'That user could not be found.' ) );
		}

		//validate
		$this->view->assign ( 'status', $status );


	}

	public function editAction() {
		if ((! $user_id = $this->isLoggedIn ()) or (! $this->hasACL ( 'add_edit_users' ))) {
			$this->doNoAccessError ();
		}

		if ($user_id = $this->getSanParam ( 'id' )) {
			$this->view->assign ( 'mode', 'edit' );
			//set template
                       // User::deleteUsers();
                      $locations = Location::getAll();
		$this->viewAssignEscaped('locations', $locations);

			return $this->myaccountAction ();
		} else {
			$status = ValidationContainer::instance ();
			$status->setStatusMessage ( 'That account could not be found' );
			$this->view->assign ( 'status', $status );
		}

	}

	public function forgotPasswordAction() {
		$request = $this->getRequest ();
		$validateOnly = $request->isXmlHttpRequest ();

		if ($validateOnly)
		$this->setNoRenderer ();

		$status = ValidationContainer::instance ();

		$this->view->assign ( 'complete', false );
		//$status->setStatusMessage ( t ( 'Starting...' ) );

		if ($this->_getParam ( 'send' )) {
			$status->checkRequired ( $this, 'email', t ( 'Email' ) );

			if (! $status->hasError ()) {

				//$this->view->assign ( 'test', "has error");

				$userTable = new User ( );
				$select = $userTable->select ();

				$select->where ( "email = ?", $this->_getParam ( 'email' ) );
                                $currentEmail = $this->_getParam ( 'email' );
				$row = $userTable->fetchRow ( $select );

				if (!$row) {
					$status->setStatusMessage ( 'This email could not be found in the database. Please enter your registered email.' );
					$this->view->assign ( 'complete', true );
                                       
                                        $this->view->assign('currentEmail',$currentEmail);
				}

				if ($row) {
					require_once ('models/Password.php');
					$newpass = Text_Password::create ( 8 );
					$row->password = $newpass;
					$result = $row->save ();
					if ($result > 0) {

						$view = new Zend_View ( );
						$view->assign ( 'base_url', Settings::$COUNTRY_BASE_URL );
						$view->setScriptPath ( Globals::$BASE_PATH . '/app/views/scripts/email' );
						$view->assign ( 'first_name', $row->first_name );
						$view->assign ( 'username', $row->username );
						$view->assign ( 'password', $newpass );
						$text = $view->render ( 'text/forgot.phtml' );
						$html = $view->render ( 'html/forgot.phtml' );
                                                
						try {
							$mail = new Zend_Mail ( );
							$mail->setBodyText ( $text );
							$mail->setBodyHtml ( $html );
							$mail->setFrom ( Settings::$EMAIL_ADDRESS, Settings::$EMAIL_NAME );
							$mail->addTo ( $row->email, $row->username );
							$mail->setSubject ( 'Password Change Requested');
							$mail->send ();
						} catch (Exception $e) {

						}

						$status->setStatusMessage ( t ( 'Your new password has been sent. Please check your email for further instructions.' ) );
						//$this->view->assign ( 'complete', true );
					} else {
						$status->setStatusMessage ( t ( 'Mail send error.' ) );
					}
				}
			}
		}

		if ($validateOnly) {
			$this->sendData ( $status );
		} else {
			$this->view->assign ( 'status', $status );
		}
	}

	public function loginAction() {
		require_once ('Zend/Auth/Adapter/DbTable.php');

		$request = $this->getRequest ();
		$validateOnly = $request->isXmlHttpRequest ();

		$userObj = new User ( );
		$userRow = $userObj->createRow ();

		if ($validateOnly)
		$this->setNoRenderer ();

		$status = ValidationContainer::instance ();

		if ($request->isPost ()) {
			// if a user's already logged in, send them to their account home page
			$auth = Zend_Auth::getInstance ();

			if ($auth->hasIdentity ()){
				#				$this->_redirect ( 'select/select' );
			}

			$request = $this->getRequest ();



			// determine the page the user was originally trying to request
			$redirect = $this->_getParam ( 'redirect' );

			//if (strlen($redirect) == 0)
			//    $redirect = $request->getServer('REQUEST_URI');
			if (strlen ( $redirect ) == 0){
				if($this->hasACL('pre_service')){
					#					$redirect = 'select/select';
				}
			}

			// initialize errors
			$status = ValidationContainer::instance ();

			// process login if request method is post
			if ($request->isPost ()) {

				// fetch login details from form and validate them
				$username = $this->getSanParam ( 'username' );
				$password = $this->_getParam ( 'password' );
				if (! $status->checkRequired ( $this, 'username', t ( 'Login' ) ) or (! $this->_getParam ( 'send_email' ) and ! $status->checkRequired ( $this, 'password', t ( 'Password' ) )))
				$status->setStatusMessage ( t ( 'The system could not log you in.' ) );

				if (! $status->hasError ()) {

					// setup the authentication adapter
					$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
					$adapter = new Zend_Auth_Adapter_DbTable ( $db, 'user', 'username', 'password', 'md5(?)' );
					$adapter->setIdentity ( $username );
					$adapter->setCredential ( $password );

					// try and authenticate the user
					$result = $auth->authenticate ( $adapter );

					if ($result->isValid ()) {
						$user = new User ( );
						$userRow = $user->find ( $adapter->getResultRowObject ()->id )->current ();

						if($user->hasPS($userRow->id)){
							$redirect = $redirect ? $redirect : "select/select";
						}

						if ( $userRow->is_blocked ) {
							$status->setStatusMessage( t('That user account has been disabled.'));
							$auth->clearIdentity ();
						} else {
							// create identity data and write it to session
							$identity = $user->createAuthIdentity ( $userRow );
							$auth->getStorage ()->write ( $identity );

							// record login attempt
							$user->recordLogin ( $userRow );

							// send user to page they originally request
							$this->_redirect ( $redirect );

						}

					} else {

						$auth->clearIdentity ();
						switch ($result->getCode ()) {

							case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND :
							$status->setStatusMessage ( t ( 'That username or password is invalid.' ) );

							break;

							case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID :
							$status->setStatusMessage ( t ( 'That username or password is invalid.' ) );

							break;

							default :
							throw new exception ( 'login failure' );
							break;
						}
					}

				}
			}

		}

		if ($validateOnly) {
			$this->sendData ( $status );
		} else {
			$this->view->assign ( 'status', $status );
		}

	}

}

