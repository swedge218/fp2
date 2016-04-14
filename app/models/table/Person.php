<?php
/*
 * Created on Feb 14, 2008
 *
 *  Built for web
 *  Fuse IQ -- todd@fuseiq.com
 *
 */

require_once('ITechTable.php');
require_once('Location.php');
require_once('Helper2.php');

class Person extends ITechTable
{
	protected $_name = 'person';
	protected $_primary = 'id';

	public function createRow(array $data = array()) {
		$row = parent::createRow($data);
		if ( !isset($data['active']) ) {
			$row->active = 'active';
		}

		return $row;
	}

	public static function isReferenced($id) {
		require_once('PersonToTraining.php');

		$participant = new PersonToTraining();
		$select = $participant->select();
		$select->where("person_id = ?",$id);
		if ( $participant->fetchRow($select) )
		return true;

		require_once('TrainingToTrainer.php');

		$trainer = new TrainingToTrainer();
		$select = $trainer->select();
		$select->where("trainer_id = ?",$id);
		if ( $trainer->fetchRow($select) ) {
			return true;
		}

		return false;
	}

	public function getPersonName($person_id) {
		$select = $this->select()
		->from($this->_name, array('first_name', 'middle_name','last_name','facility_id','is_deleted'))
		->where("id = $person_id");
		return $this->fetchRow($select);
	}

	//TA:17:16:1 where should use 'and' for all parameters:
	//find person by first and middle and last name 
        
        //TP:
        //improved this method such that the the name search is done on actual names using = operator
        //rather than the  like operator
	public static function tryFind ($first, $middle, $last)
	{
		$first = trim($first);
		$middle = trim($middle);
		$last = trim ($last);

		if ($first == '' && $middle == '' && $last == '')
			return null; 

		$p = new Person();
		$select = $p->select()->from($p->_name, array('id', 'first_name', 'middle_name','last_name','is_deleted'));

//		$select->where("first_name like ?", $first);
//		$select->where("middle_name like ?", $middle);
//		$select->where("last_name like ?", $last);
                
                $select->where("first_name = ?", $first);
		$select->where("middle_name = ?", $middle);
		$select->where("last_name = ?", $last);
                
                

		$res = $p->fetchRow($select);
               
                if($res->is_deleted==1 || $res->is_deleted=="1"){
                    self::updateUserRecord("is_deleted",0,$res->id);
                       }
                       // echo $res->is_deleted;exit;
		return $res->id ? $res->id : null;
	}
        public function updateUserRecord($clause,$value,$id){
            $p = new Person();
             $data = array("$clause"=>$value);
             $update = $p->update($data, 'id= '.$id);
             return $update;
             }
             public function checkQualififcationAndReturn($qual){
                 $qual = strtolower($qual);
                 if($qual=="chew"){
                   $qual = "community health extension worker";  
                 }else if($qual=="cho"){
                     $qual = "community health officer";
                 }
                 return strtolower($qual);
             }

	public static function suggestionList($match = false, $limit = 100, $middleNameLast = false, $priority = array('last_name','first_name','middle_name')) {
		if ( !$middleNameLast )
		$additionalCols = array('p.first_name','p.middle_name','p.last_name','p.id','f.facility_name','f.location_id', 'p.birthdate','q.qualification_phrase');
		else
		$additionalCols = array('p.first_name','p.last_name','p.middle_name','p.id','f.facility_name','f.location_id', 'p.birthdate','q.qualification_phrase');

		$rowArray = array();

		foreach( $priority as $keyrow ) {
			if ( count($rowArray) < $limit ) {
				$select = array('p.'.$keyrow.' as key');
				$select = array_merge($select, $additionalCols);
				$rows = self::suggestionQuery($match,$limit, $keyrow, $select);

				$rowArray  += $rows->toArray();
			}
		}

		return $rowArray;
	}

	public static function suggestionListByFirstName($match = false, $limit = 100, $middleNameLast = false) {
		return self::suggestionList($match,$limit,$middleNameLast, array('first_name','last_name','middle_name'));
	}

	public static function suggestionListByMiddleName($match = false, $limit = 100, $middleNameLast = false) {
		return self::suggestionList($match,$limit,$middleNameLast, array('middle_name','last_name','first_name'));
	}

	public static function suggestionFindDupes($match_last_name, $limit = 100, $middleNameLast = false, $fieldAndWhere = array()) {
    $additionalCols = array('p.first_name','p.last_name','p.middle_name','person_id' => 'p.id','f.facility_name','p.national_id', 'p.birthdate','p.gender', 'q.qualification_phrase', 'p.file_number');
		$rows = self::suggestionQuery($match_last_name, $limit, "last_name", $additionalCols, false, $fieldAndWhere);
		return $rows->toArray();
	}

	public static function suggestionQuery($match = false, $limit = 100, $field = 'last_name', $fieldsSelect = array('p.last_name','p.first_name','p.birthdate'), $fieldAdditional = false, $fieldAndWhere = false) {
                $location = new Location();
                $newLocation = $location->ImplodedUserAccessLocation();
		require_once('models/table/OptionList.php');
		$topicTable = new OptionList(array('name' => 'person'));

		$select = $topicTable->select()->distinct()
		->from(array('p' => 'person'),$fieldsSelect);

		if ( count($fieldsSelect) > 1 ) { //if there's only one field, then assume we just want distinct names and nothing else
			$select->setIntegrityCheck(false)
			->join(array('f' => 'facility'), "p.facility_id = f.id",array('facility_name','location_id'))
                        //->join(array('l' => 'location'), "f.location_id = l.id",array('location_id', 'p.birthdate'))
			;
		}
        if (array_search('q.qualification_phrase', $fieldsSelect))
            $select->setIntegrityCheck(false)->join(array('q' => 'person_qualification_option'), 'p.primary_qualification_option_id = q.id',array('q.qualification_phrase'));

		$select->where(' p.is_deleted = 0');

		//look for char start
		if ( $match ) {
			$select->where("$field LIKE ? ", $match.'%');
			if ($fieldAdditional) {
				$select->orWhere("$fieldAdditional LIKE ? ", $match.'%');
			}
		}

		if($fieldAndWhere) {
			foreach($fieldAndWhere as $fieldname => $matchstring) {
				$select->where("$fieldname LIKE ? ", $matchstring.'%');
			}
		}
if($newLocation!=""){
    $select->where("f.location_id IN ($newLocation)");
}
		//$select->where('trainer.is_deleted = 0 AND trainer.is_active = 1');

		$select->order("$field ASC");
		//	foreach($fieldsSelect as $otherfield) {
		$select->order( "last_name ASC" );
		$select->order( "first_name ASC" );
		$select->order( "middle_name ASC" );
		//	}
                

		if ( $limit )
		$select->limit($limit,0);
                //Helper2::jLog($select->__toString());
		$rows = $topicTable->fetchAll($select);
                 //Helper2::jLog(print_r($rows,true));
		return $rows;
	}


	public function update(array $data,$where) {
		//save a snapshot now
		require_once('History.php');
		$historyTable = new History('person');
		//cheezy way to get the id
		$parts = explode('=',$where[0]);
		$historyTable->insert($this, trim($parts[1]));

		$rslt = parent::update($data,$where);

		return $rslt;
	}
        
        public function clean($clear){
            $clear = strip_tags($clear);
// Clean up things like &amp;
$clear = html_entity_decode($clear);
// Strip out any url-encoded stuff
$clear = urldecode($clear);
// Replace non-AlNum characters with space
$clear = preg_replace('/[^A-Za-z0-9]/', '', $clear);
// Replace Multiple spaces with single space
$clear = preg_replace('/ +/', ' ', $clear);
// Trim the string of leading/trailing space
$clear = trim($clear);
return $clear;
            
        }
       
}

