<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImportTraining
 *
 * @author John
 */
require_once('Person.php');
//require_once ('controllers/ReportFilterHelpers.php');
//require_once ('app/controllers/ITechController.php');
class ImportTraining  {
    //put your code here
    public function getDbAdapter(){
        return $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    }
    //$db = $this->getDbAdapter();
    public function tryFindCadre($rows,$i){
        $db = $this->getDbAdapter();
        $cadre_id = 0;
        if(trim($rows[$i][6])){
	$cadre_id = $db->fetchOne ( "SELECT id FROM person_qualification_option WHERE LOWER(qualification_phrase_abbr) = '" . trim($rows[$i][6]) . "' LIMIT 1" );
        if($cadre_id==""){
	 $cadre_id = 0;
            }
	}
        return $cadre_id;
    }
    
    public function tryFindFaciityId($state_name,$lga_name,$facility_name){
        $db = $this->getDbAdapter();
        $facility_id =0;
        if ($facility_name) { 
										//find facility id only if state id and lga id are defined
 		if (!empty ( $state_name )){
 		$state_id = $db->fetchOne ( "SELECT id FROM location WHERE LOWER(location_name) = '" . $state_name . "' LIMIT 1" );
		if($state_id != null && !empty ( $lga_name )){
		$lga_id = $db->fetchOne (  "SELECT id FROM location WHERE LOWER(location_name) = '" . $lga_name . "' AND parent_id = $state_id  LIMIT 1" );
		if($lga_id != null && !empty ( $facility_name )){
		$facility_id = $db->fetchOne (  "SELECT id FROM facility WHERE LOWER(facility_name) = '" . $facility_name . "' AND location_id = $lga_id  LIMIT 1" );
			}
		 }
 	    }
                                                                   
	}
        if(empty($facility_id)){
            $facility_id = 0;
        }
        return $facility_id;
    }
    
//    public function parseTrainingDataToArray($values,$status){
//        $status = ValidationContainer::instance();
//        if (isset($values['training_title_option_id'])){$values['training_title_option_id']= $this->_importHelperFindOrCreate('training_title_option','training_title_phrase',$values['training_title_option_id']); }
//				if ($values['training_start_date']){$values['training_start_date'] = $this->_date_to_sql($values['training_start_date']); }
//				if ($values['training_end_date']) {$values['training_end_date'] = $this->_date_to_sql($values['training_end_date']); }
//				$values['training_organizer_option_id'] =0;
//                               // echo 'This is the file for the training organizer '.$values['training_organizer_phrase']."we have to add it here";exit;
//                               if (isset($values['training_organizer_phrase'])&& $values['training_organizer_phrase']!=""){
//                                    $values['training_organizer_option_id']= $this->_importHelperFindOrCreate('training_organizer_option','training_organizer_phrase',$values['training_organizer_phrase']);
//                                   
//                                    }else{
//                                        $values['training_organizer_option_id'] =0;
//                                    }
//                                    
//                                    if($values['training_organizer_option_id']==""){
//                                        $values['training_organizer_option_id'] =0;
//                                    }
//                                
//                                   // echo $values['training_organizer_option_id'];exit;
//				//default values
//				$values['has_known_participants'] = '1';
//				$values['comments'] = '';
//				$values['got_comments'] = '';
//				$values['objectives'] = '';
//				$values['is_approved'] = '1';
//				$values['is_tot'] = '0';
//				$values['is_refresher'] = '1';
//				$values['training_location_id'] =0; // trim($rows[12][2]); // by default 'unknown'
//                                
//                                $trainingLocationStateName = (trim($rows[12][2]));
//                                $trainingLocationStateName = str_replace("_"," ",$trainingLocationStateName);
//                                $LocationDefaultName = $trainingLocationStateName." default training location";
//                                $trainingLocationStateName = strtolower($trainingLocationStateName);
//                                     if($trainingLocationStateName=="fct"){
//                                         $trainingLocationStateName = strtolower("Federal Capital Territory");
//                                            }
//                                                                      
//                                $stateLocationid = $db->fetchOne ( "SELECT id FROM location WHERE LOWER(location_name) = '" . $trainingLocationStateName . "' LIMIT 1" );
//                                
//                                if(empty($stateLocationid)){
//                                    $status->addError ( 'training_location_id', t ( 'Your changes have not been saved: Training Location is not valid. '.$rows[12][2] ) );
//                                }
//                              
//                                $trainingLocationId = $db->fetchOne("SELECT id FROM training_location WHERE location_id= '".$stateLocationid."' AND is_deleted='0' LIMIT 1");
//                                
//                                if($trainingLocationId && $trainingLocationId!=""){
//                                    $values['training_location_id'] = $trainingLocationId;
//                                }else{
//                                    $trainingLocationName = $LocationDefaultName; 
//                                   $id = $trainingloc->insertIfNotFound($LocationDefaultName, $stateLocationid);
//                                   $values['training_location_id'] = $id;
//                                }
//                                return $values;
//                                
//    }
    public function parsePersonDataToArray($values_person,$rows,$status,$i){
        $status = ValidationContainer::instance();
        $p = new Person();
        $values_person['phone_mobile'] = trim($rows[$i][12]);
        $values_person['phone_home'] = trim($rows[$i][12]);
	$values_person['email'] = trim($rows[$i][13]);
        $values_person['middle_name'] = $p->clean($rows[$i][2]);
	//required fields 
	$values_person['first_name'] = $p->clean($rows[$i][1]);
	$values_person['last_name'] = $p->clean($rows[$i][3]);
        $values_person['birthdate'] = trim($rows[$i][4]);
        list($ye,$me,$de) = $status->dateFormatter($values_person['birthdate']);
            if($ye=="")$ye="0000";
            if($me=="")$me="00";
            if($de=="")$de ="00";
                                                   
        $values_person['birthdate'] = $ye.'-'.$me.'-'.$de;
            if(!($status->validateDate($values_person['birthdate']))){
        $values_person['birthdate'] = "0000-00-00";
        }
        $birthdate = $values_person['birthdate'];
        $values_person['gender'] = trim($rows[$i][5]);
            if(trim($rows[$i][5])==""){ $values_person['gender'] = 'na'; }
	$values_person['certification'] = trim($rows[$i][14]);
        // echo 'Certification is '.$values_person['certification']." ";
        $values_person['certification'] = trim($rows[$i][14]);
        $values_person['training_level_id'] = $values['training_level_option__id'];
	$values_person['primary_qualification_option_id'] = '0';
        $rows[$i][6] = strtolower($rows[$i][6]);
        $values_person['primary_qualification_option_id'] = $this->tryFindCadre($rows,$i);
	$qualification = $values_person['primary_qualification_option_id'];
	//if facility id not found then allow to add person with empty facility id
	$facility_name = strtolower(trim($rows[$i][9]));
	$lga_name = trim($rows[$i][8]);
        $lga_name = str_replace("_"," ",$lga_name);
        $lga_name = strtolower($lga_name);
	$state_name = strtolower(trim($rows[$i][7])); 
        $state_name = str_replace("_"," ",$state_name);
            if($state_name=="fct"){
            $state_name = strtolower("Federal Capital Territory");
            }
	$values_person['facility_id'] = '0';
	$certification = $values_person['certification'];
        $values_person['facility_id'] = $this->tryFindFaciityId($state_name,$lga_name,$facility_name);
        $facilityId = $values_person['facility_id'];
	//prinr_r($values_person);
        $mes_facility = '';
            if($facility_name) $mes_facility .= $facility_name;
            if($lga_name){
            if($mes_facility) $mes_facility .= ", ";
            $mes_facility .= $lga_name;
            }
            if($state_name){
            if($mes_facility) $mes_facility .= ", ";
            $mes_facility .= $state_name;
            }
            if($mes_facility !== ''){
            $mes_facility = "Facility not found '" . $mes_facility . "'";
            $to_fix = "to fix data";
            }else{
            $mes_facility = "Facility, LGA and State are required";
            $to_fix = "to fix data";
            }
            $mes_person = '';
            if($rows[$i][1]) $mes_person .= $rows[$i][1];
            if($rows[$i][2]){
            if($mes_person) $mes_person .= " ";
            $mes_person .= $rows[$i][2];
            }
            if($rows[$i][3]){
            if($mes_person) $mes_person .= " ";
            $mes_person .= $rows[$i][3];
            }
            if($values_person['facility_id'] == '0'){				
            $errs [] = $mes_facility . ". Person #" . $rows[$i][0] . " '" . $mes_person . "' has been added to training, but has not being assigned to any facility.";
            $to_fix = "to fix data";
            }
            return array($values_person,$errs,$to_fix,$mes_person,$mes_facility);
    }
    public function checkExcelRequiredFieldsPerson($rows,$errs,$i){
        $errs = array();
        if(!trim($rows[$i][1])){
		$errs[] = t("Could not add person to training. First name is required."). " Person #" . $rows[$i][0];
		$to_fix = "to fix data";
		
              }
		if(!trim($rows[$i][3])){
		$errs[] = t("Could not add person to training. Last name is required."). " Person: #" . $rows[$i][0];
		$to_fix = "to fix data";
		
	}
        if(!trim($rows[$i][6])){
		$errs[] = t("Could not add person to training. Cadre is required."). " Person: #" . $rows[$i][0];
		$to_fix = "to fix data";
		
									}
        return $errs;
    }
    
}
