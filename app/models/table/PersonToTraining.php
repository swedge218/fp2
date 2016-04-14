<?php
/*
 * Created on Feb 14, 2008
 *
 *  Built for web
 *  Fuse IQ -- todd@fuseiq.com
 *
 */
require_once('ITechTable.php');
require_once('User.php');
require_once('Helper2.php');
require_once('Location.php');

class PersonToTraining extends ITechTable
{
	protected $_primary = 'id';
  protected $_name = 'person_to_training';

  /**
   * Returns trainers in training session
   */
  public static function getParticipants($training_id) {
    $tableObj = new PersonToTraining();
     $user = new User();
    $personLocationWhere = "";
    $newLocations = array();
    $newLocation = "";
   $locationnew = new Location();
   
   if(!$user->UserAccessRoleAllowed()){
   $newLocations = $locationnew->userAccessLocations();
   $newLocation = implode(",",$newLocations);
   }
        if($newLocation!=""){
            
        $personLocationWhere = "AND f.location_id IN ($newLocation)";
        
    }
    

    $select = $tableObj->select()
        ->from(array('ptt' => $tableObj->_name), array('id', 'person_id', 'duration_days', 'certification', 'award_id', 'score_percent_change' => new Zend_Db_Expr('ROUND((spost.score_value - spre.score_value) / spre.score_value * 100)')))
        ->setIntegrityCheck(false)
        ->join(array('p' => 'person'), "p.id = ptt.person_id",array('first_name','middle_name', 'last_name','birthdate'))
        //->join(array('f' => 'facility'), "p.facility_id = f.id",array('facility_name', 'location_id')) //TA:17: 10/15/2014 - commented: display only persons with known facility id
        ->joinLeft(array('f' => 'facility'), "p.facility_id = f.id",array('facility_name', 'location_id')) //TA:17: 10/15/2014 
        ->joinLeft(array('pq' => 'person_qualification_option'), "p.primary_qualification_option_id = pq.id",array('qualification' => 'qualification_phrase'))
        ->joinLeft(array('pq2' => 'person_qualification_option'), "pq.parent_id = pq2.id",array('primary_qualification' => 'qualification_phrase'))
    //    ->joinLeft(array('pr' => 'person_responsibility_option'), "p.primary_responsibility_option_id = pr.id",array('primary_responsibility'=>'responsibility_phrase'))
   //     ->joinLeft(array('sr' => 'person_responsibility_option'), "p.secondary_responsibility_option_id = sr.id",array('secondary_responsibility'=>'responsibility_phrase'))
   //     ->joinLeft(array('l' => 'location'), "f.location_id = l.id",array('location_id'))
        ->joinLeft(array('spre' => 'score'), "spre.person_to_training_id = ptt.id AND spre.score_label = 'Pre-Test'", array('score_pre' => 'score_value'))
        ->joinLeft(array('spost' => 'score'), "spost.person_to_training_id = ptt.id AND spost.score_label = 'Post-Test'", array('score_post' => 'score_value'))
        ->joinLeft(array('scoreother' => 'score'), "scoreother.person_to_training_id = ptt.id AND scoreother.score_label != 'Post-Test' AND scoreother.score_label != 'Pre-Test'", array('score_other_k' => 'GROUP_CONCAT(scoreother.score_label)', 'score_other_v' => 'GROUP_CONCAT(scoreother.score_value)'))
        ->joinLeft(array('award'   => 'person_to_training_award_option'),        "award.id   = award_id"                  ,  array('award_phrase' => 'award_phrase'))
        ->joinLeft(array('budget'  => 'person_to_training_budget_option'),       "budget.id  = budget_code_option_id"     ,  array('budget_code_phrase' => 'budget_code_phrase'))
        ->joinLeft(array('viewloc' => 'person_to_training_viewing_loc_option'),  "viewloc.id = viewing_location_option_id",  array('location_phrase' => 'location_phrase'))
       // ->where("ptt.training_id = $training_id")
        ->where("ptt.training_id = $training_id and p.is_deleted=0 ".$personLocationWhere."") //TA:21: 09/29/2014
        ->group("ptt.id")
        ->order("last_name");
$sql = $select->__toString();
Helper2::jLog($sql);
//exit;
    return $tableObj->fetchAll($select);
  }

  
  public function getParticipantsCount($training_id){
      $tableObj = new PersonToTraining();
    $select = $tableObj->select()
              ->setIntegrityCheck(false)
              ->from(array('ptt'=>'person_to_training'))
              ->joinInner(array('p'=>'person'),"p.id=ptt.person_id")
              ->where("training_id = $training_id and p.is_deleted=0");
           $result = $tableObj->fetchAll($select);
           return sizeof($result);
}


  /**
   * Returns array of peron ids who took a course by a name
   */
  public static function getParticipantsByCourseName($training_title) {
    $tableObj = new PersonToTraining();

    $select = $tableObj->select()
        ->from(array('c' => 'course'), array())
        ->setIntegrityCheck(false)
        ->join(array('t' => 'training'), "t.training_title_option_id = c.id")
        ->join(array('ptt' => 'person_to_training'), "ptt.training_id = t.id", array('person_id'))
        ->join(array('tto' => 'training_title_option'), "tto.id = c.training_title_option_id", array('person_id'))
        ->where("tto.training_title_phrase = ?", "{$training_title}");

    $ids = array();
    $rows = $tableObj->fetchAll($select);
    foreach($rows as $r) {
      $ids[] = $r->person_id;
    }

    return $ids;
  }


  /**
   * Add person to training session
   */

  public function addPersonToTraining($person_id, $training_id,$certification="") {
      $data = array();
        Helper2::jLog('the first beginiing');
   	$select = $this->select()
                ->from($this->_name, array('doesExist' => 'COUNT(*)'))
                ->setIntegrityCheck(false)
                ->where("person_id = $person_id AND training_id = $training_id");
Helper2::jLog('this is after the query');
    $row = $this->fetchRow($select);
    Helper2::jLog('This is after the end statement of the query');

    if($row->doesExist) {
        Helper2::jLog('This is person does exist');
      return -1;
    } else {
   Helper2::jLog('This is the person does not exists '.$person_id." that e the person id");
      //make sure person isn't deleted
      $person = new Person();
      $prows = $person->find($person_id);
      $current = $prows->current();
      
      //Helper2::jLog('This is another development of  the page this is the current value '.printr($current->toArray(),true));
      
      if ($prows )
        $prow = $prows->current();
        Helper2::jLog('the first prows default if ');
      //if ( (!$prows) || (!$prow) || $prow->is_deleted )
      
  
    if ( (!$prows) || (!$prow) ){
         Helper2::jLog('the check point for the data');
         return 0;
         
    }
        
//return 0;


      $data['person_id'] = $person_id;
      $data['training_id'] = $training_id;

      $data['certification'] = $certification;
      $data['training_level_id'] = 0;
      //$data['duration_days'] = 20;
      //$data['viewing_location_id'] = 0;
      //$data['budget_option_id'] = 0;
 // echo 'This is the person to training method';
 //Helper2::jLog('This is the person to training method');
      try {
          //Helper2::jLog('this is the try method');
        return $this->insert($data);
         // return $errs[] = t("Trainer id is ").$certification;
									
      } catch(Zend_Exception $e) {
          Helper2::jLog($e->getMessage());
        error_log($e);
      }
    }
  }


public function updatePersonToTrainingRecord($clause,$value,$id){
            
             $data = array("$clause"=>"$value");
             $update = $this->update($data, 'id= '.$id);
             
             return $update;
             }
}
