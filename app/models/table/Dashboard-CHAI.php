<?php
require_once('Dashboard.php');
require_once('Helper.php');
require_once('Helper2.php');
require_once('CoverageHelper.php');
require_once('DashboardHelper.php');



class DashboardCHAI extends Dashboard
{
	protected $_primary = 'id';

	public function fetchConsumptionDetails($dataName = null, $id, $where = null, $group = null, $useName = null) {
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    $output = array();
	    
	    $subSelect = new Zend_Db_Expr("(select facility_id, month(max(date)) as C_monthDate from commodity group by facility_id)");

		switch ($dataName) {
		    case 'geo':
		        $select = $db->select()
		        ->from('location');
		        if ($where) // comma seperated string for sql
		            $select = $select->where($where)
		            ->order('location_name');
		        $result = $db->fetchAll($select);
		        
		        foreach ($result as $row){
		        
		          $output[] = array(
		            "id" => $row['id'],
		            "name" => $row['location_name'],
		            "tier" => $row['tier'],
		            "parent_id" => $row['parent_id'],
		            "consumption" => 0,
		            "link" => Settings::$COUNTRY_BASE_URL . "/dashboard/dash3/id/" . $row['id'],
		            "type" => 1
		           );
		        }
		    break;
		    case 'location':
		        $select = $db->select()
		        ->from(array('f' => 'facility'), 
		          array(
		          'f.id as F_id', 
		          'f.facility_name as F_facility_name', 
		          'f.location_id as F_location_id', 
		          'l1.id as L1_id',
		          'l1.location_name as L1_location_name', 
		          'l2.id as L2_id', 
		          'l2.location_name as L2_location_name', 
		          'l2.parent_id as L2_parent_id', 
		          'l3.location_name as L3_location_name', 
		          'cno.commodity_name as CNO_commodity_name',
		          'ifnull(sum(c.consumption),0) as C_consumption' ))
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->joinLeft(array("c" => "commodity"), 'f.id = c.facility_id')
		        ->joinLeft(array("cno" => "commodity_name_option"), 'c.name_id = cno.id')
		        ->joinInner(array('mc' => $subSelect), 'f.id = mc.facility_id and month(c.date) = C_monthDate')
		        ->where($where)
		        ->group(array($group))
                ->order(array('L3_location_name', 'L2_location_name', 'L1_location_name', 'F_facility_name'));
		        
		        $result = $db->fetchAll($select);
		        
		      foreach ($result as $row){
		          
		        $_child_name = ($id ==  "") ? $row['L2_location_name'] : $row['L1_location_name'] ;
		        
		 	    $output[] = array(
		 	        "id" => $row[$group],
		 	        "name" => $row[$useName],
		 	        "tier" => $row['tier'],
		 	        "parent_id" => $row['L2_parent_id'],
		 	        "child_name" => $_child_name,
		 	        "commodity_name" => $row[CNO_commodity_name],
		 	        "consumption" => $row['C_consumption'],
		 	        "link" => Settings::$COUNTRY_BASE_URL . "/dashboard/dash3/id/" . $row['l2.parent_id'],
		 	        "type" => 1
		 	     );
		 	    
		      }
		    break;
		    case 'facility':
		          $select = $db->select()
		          ->from(array('f' => 'facility'), 
		          array(
		          'f.id as F_id', 
		          'f.facility_name as F_facility_name', 
		          'f.location_id as F_location_id', 
		          'l1.id as L1_id',
		          'l1.location_name as L1_location_name', 
		          'l2.id as L2_id', 
		          'l2.location_name as L2_location_name', 
		          'l2.parent_id as L2_parent_id', 
		          'l3.location_name as L3_location_name', 
		          'cno.commodity_name as CNO_commodity_name',
		          'ifnull(c.consumption,0) as C_consumption' ))
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->joinLeft(array('c' => "commodity"), 'f.id = c.facility_id')
		        ->joinLeft(array("cno" => "commodity_name_option"), 'c.name_id = cno.id')
		        ->joinInner(array('mc' => $subSelect), 'f.id = mc.facility_id and month(c.date) = C_monthDate')
		        ->where($where)
                ->order(array('L3_location_name', 'L2_location_name', 'L1_location_name', 'F_facility_name'));
		        
		        $result = $db->fetchAll($select);
		        
		      foreach ($result as $row){
		 	    $output[] = array(
		 	        "id" => $row[$group],
		 	        "name" => $row[$useName],
		 	        "tier" => $row['tier'],
		 	        "parent_id" => $row['L3_parent_id'],
		 	        "child_name" => $row['F_facility_name'],
		 	        "commodity_name" => $row[CNO_commodity_name],
		 	        "consumption" => $row['C_consumption'],
		 	        "link" => Settings::$COUNTRY_BASE_URL . "/dashboard/dash3/id/" . $row['l2.parent_id'],
		 	        "type" => 1
		 	     );
		      }
		        break;		    
		}
		return $output;
	}
	
	public function fetchTier( $id ) {
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    $output = array();
	    
	    $select = $db->select()
	    ->from('location')
        ->where('id = ' . $id);
	    
	    $result = $db->fetchRow($select);
	    
	    return $result['tier'];
	}
	
	public function fetchTitleData() {
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    $output = array();
	     
	    $select = $db->select()
	    ->from(array('l' => 'lc_view'),
	        array( 
	            'monthName(l.C_date) as month_name',
	            'year(l.C_date) as year'
	        ));
	     
	    $result = $db->fetchRow($select);
	     
	    return $result;
	    
	}
	

	
	public function fetchTitleDate() {
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	
	    //$select = $db->select()
	    //->from(array('c' => 'commodity'),
	        //array(new Zend_Db_Expr('MONTHNAME(max(c.date)) as month_name, YEAR(max(c.date)) as year' )));
            
            $select = $db->select()
                      ->from(array('c' => 'commodity'), 'MAX(c.date) as maxdate');
	
	    $result = $db->fetchRow($select); 
            
            $titleDate = array(
                            'month_name' => date('F', strtotime($result['maxdate'])),
                            'year' => date('Y', strtotime($result['maxdate']))
                        );

	    return $titleDate;
	}
	
	public function fetchTitleMethod($method) {
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    $output = array();
	    
	    $method = "'".$method."'";
	    $select = $db->select()
	    ->from(array('cno' => 'commodity_name_option'),
	           array( 'commodity_name' ))
	    ->where("external_id =  $method ");
	
	    $result = $db->fetchRow($select);
	
	    return $result;
	     
	}
        

	//TA:17:17 HR Summary chart
	public function fetchCSDetails1($date) {
            
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    $output = array();
	
	    // get last date where data were uploaded from DHIS2
	    if($date === null || empty($date)){
	        $result = $db->fetchAll("select max(date) as date from facility_report_rate");
	        $date = $result[0]['date'];
	    }
	
	    $output['last_date'] = $date;
	
	    $select = $db->select()
	    -> from(array('facility_report_rate' => 'facility_report_rate'),
	        array('count(*) as count'));
	
                    //TP: total_facility_count - denominator
	            $result = $db->fetchAll($select);
	            $output['total_facility_count'] = $result[0]['count'];
	
                    //the facilities that reported for the month refrenced by $date
	            $select = $db->select()
	            -> from(array('facility_report_rate' => 'facility_report_rate'),
	            array('count(*) as count'))
	            ->where("date='" . $date . "'");
	
	            $result = $db->fetchAll($select);
	            $output['total_facility_count_month'] = $result[0]['count'];
                    
	        $select = $db->select()
	       -> from(array('facility' => 'facility'), array('count(distinct facility.id) as count'))
               ->joinLeft(array('facility_report_rate' => "facility_report_rate"), 'facility.external_id = facility_report_rate.facility_external_id')
	       ->joinLeft(array('person' => "person"), 'facility.id = person.facility_id')
	       ->joinLeft(array('person_to_training' => "person_to_training"), 'person.id = person_to_training.person_id')
               ->joinLeft(array('training' => "training"), 'training.id = person_to_training.training_id')
	       ->where('training.training_title_option_id = 1');
	        
	        $sql = $select->__toString();
                    
	        $sql = str_replace('AS `count`,', 'AS `count`', $sql);
	        $sql = str_replace('`facility_report_rate`.*,', '', $sql);
	        $sql = str_replace('`person`.*,', '', $sql);
	        $sql = str_replace('`person_to_training`.*,', '', $sql);
	        $sql = str_replace('`training`.*', '', $sql);
                //echo 'CS1: ' . $sql . '<br/>'; return;
         $result = $db->fetchAll($sql);
	     $output['larc_facility_count'] = $result[0]['count'];
             
             
	        $select = $db->select()
           -> from(array('facility' => 'facility'), array('count(distinct facility.id) as count'))
           ->joinLeft(array('facility_report_rate' => "facility_report_rate"), 'facility.external_id = facility_report_rate.facility_external_id')
           ->joinLeft(array('person' => "person"), 'facility.id = person.facility_id')
           ->joinLeft(array('person_to_training' => "person_to_training"), 'person.id = person_to_training.person_id')
           ->joinLeft(array('training' => "training"), 'training.id = person_to_training.training_id')
	               ->where('training.training_title_option_id = 2');
	        
	        $sql = $select->__toString();
	        $sql = str_replace('AS `count`,', 'AS `count`', $sql);
	        $sql = str_replace('`facility_report_rate`.*,', '', $sql);
	        $sql = str_replace('`person`.*,', '', $sql);
	        $sql = str_replace('`person_to_training`.*,', '', $sql);
	        $sql = str_replace('`training`.*', '', $sql);
	
         $result = $db->fetchAll($sql);
	     $output['fp_facility_count'] = $result[0]['count'];
	
	
	
	     $select = $db->select()
	     -> from(array('facility' => 'facility'), array('count(distinct facility.id) as count'))
	         ->joinLeft(array('facility_report_rate' => "facility_report_rate"), 'facility.external_id = facility_report_rate.facility_external_id')
	         ->joinLeft(array('person' => "person"), 'facility.id = person.facility_id')
	         ->joinLeft(array('person_to_training' => "person_to_training"), 'person.id = person_to_training.person_id')
	         ->joinLeft(array('training' => "training"), 'training.id = person_to_training.training_id')
	         ->joinLeft(array('commodity' => "commodity"), 'facility.id = commodity.facility_id')
	         ->joinLeft(array('commodity_name_option' => "commodity_name_option"), 'commodity.name_id = commodity_name_option.id')
	         ->where('training.training_title_option_id = 1')
             ->where("commodity_name_option.external_id='DiXDJRmPwfh'");
	     
	     $sql = $select->__toString();
	     $sql = str_replace('AS `count`,', 'AS `count`', $sql);
	     $sql = str_replace('`facility_report_rate`.*,', '', $sql);
	     $sql = str_replace('`person`.*,', '', $sql);
	     $sql = str_replace('`person_to_training`.*,', '', $sql);
	     $sql = str_replace('`training`.*,', '', $sql);
	     $sql = str_replace('`commodity`.*,', '', $sql);
	     $sql = str_replace('`commodity_name_option`.*', '', $sql);
	     
     $result = $db->fetchAll($sql);
	 $output['larc_consumption_facility_count'] = $result[0]['count'];
	
     $select = $db->select()
             -> from(array('facility' => 'facility'), array('count(distinct facility.id) as count'))
	         ->joinLeft(array('facility_report_rate' => "facility_report_rate"), 'facility.external_id = facility_report_rate.facility_external_id')
	         ->joinLeft(array('person' => "person"), 'facility.id = person.facility_id')
             ->joinLeft(array('person_to_training' => "person_to_training"), 'person.id = person_to_training.person_id')
	         ->joinLeft(array('training' => "training"), 'training.id = person_to_training.training_id')
	         ->joinLeft(array('commodity' => "commodity"), 'facility.id = commodity.facility_id')
	         ->joinLeft(array('commodity_name_option' => "commodity_name_option"), 'commodity.name_id = commodity_name_option.id')
	         ->where('training.training_title_option_id = 2')
	         ->where("commodity_name_option.external_id='ibHR9NQ0bKL'");
     
     $sql = $select->__toString();
     $sql = str_replace('AS `count`,', 'AS `count`', $sql);
     $sql = str_replace('`facility_report_rate`.*,', '', $sql);
     $sql = str_replace('`person`.*,', '', $sql);
     $sql = str_replace('`person_to_training`.*,', '', $sql);
     $sql = str_replace('`training`.*,', '', $sql);
     $sql = str_replace('`commodity`.*,', '', $sql);
     $sql = str_replace('`commodity_name_option`.*', '', $sql);
      
	 $result = $db->fetchAll($sql);
	 $output['fp_consumption_facility_count'] = $result[0]['count'];
	
	 $select = $db->select()
	 -> from(array('facility' => 'facility'), array('count(distinct facility.id) as count'))
	 ->joinLeft(array('facility_report_rate' => "facility_report_rate"), 'facility.external_id = facility_report_rate.facility_external_id')
	 ->joinLeft(array('person' => "person"), 'facility.id = person.facility_id')
	 ->joinLeft(array('person_to_training' => "person_to_training"), 'person.id = person_to_training.person_id')
	 ->joinLeft(array('training' => "training"), 'training.id = person_to_training.training_id')
	 ->joinLeft(array('commodity' => "commodity"), 'facility.id = commodity.facility_id')
	 ->joinLeft(array('commodity_name_option' => "commodity_name_option"), 'commodity.name_id = commodity_name_option.id')
	 ->where('training.training_title_option_id = 1')
	 ->where("commodity_name_option.external_id='DiXDJRmPwfh'")
     ->where("stock_out='Y'");
	 
	 $sql = $select->__toString();
	 $sql = str_replace('AS `count`,', 'AS `count`', $sql);
	 $sql = str_replace('`facility_report_rate`.*,', '', $sql);
	 $sql = str_replace('`person`.*,', '', $sql);
	 $sql = str_replace('`person_to_training`.*,', '', $sql);
	 $sql = str_replace('`training`.*,', '', $sql);
	 $sql = str_replace('`commodity`.*,', '', $sql);
	 $sql = str_replace('`commodity_name_option`.*', '', $sql);
	
	 $result = $db->fetchAll($sql);
     $output['larc_stock_out_facility_count'] = $result[0]['count'];
	
	 $select = $db->select()
     -> from(array('facility' => 'facility'), array('count(distinct facility.id) as count'))
	 ->joinLeft(array('facility_report_rate' => "facility_report_rate"), 'facility.external_id = facility_report_rate.facility_external_id')
	 ->joinLeft(array('person' => "person"), 'facility.id = person.facility_id')
	 ->joinLeft(array('person_to_training' => "person_to_training"), 'person.id = person_to_training.person_id')
	 ->joinLeft(array('training' => "training"), 'training.id = person_to_training.training_id')
	 ->joinLeft(array('commodity' => "commodity"), 'facility.id = commodity.facility_id')
	 ->joinLeft(array('commodity_name_option' => "commodity_name_option"), 'commodity.name_id = commodity_name_option.id')
	 ->where('training.training_title_option_id = 2')
	 ->where("commodity_name_option.external_id='JyiR2cQ6DZT'");
	 
	 $sql = $select->__toString();
	 $sql = str_replace('AS `count`,', 'AS `count`', $sql);
	 $sql = str_replace('`facility_report_rate`.*,', '', $sql);
	 $sql = str_replace('`person`.*,', '', $sql);
	 $sql = str_replace('`person_to_training`.*,', '', $sql);
	 $sql = str_replace('`training`.*,', '', $sql);
	 $sql = str_replace('`commodity`.*,', '', $sql);
	 $sql = str_replace('`commodity_name_option`.*', '', $sql);
	
	 $result = $db->fetchAll($sql);
	 $output['fp_stock_out_facility_count'] = $result[0]['count'];
	
	return $output;
	
    }
	
        
      /*TP: Rewriting the fetchCSDetails method
       * This time we will modularize and make use of views that will already filter the date
       * This method get the last DHIS2 download date and use it as argument for 
       * the 3 categories of calls: 
       */
      public function fetchCSDetails($date){          
           //$output = $this->fetchCSDetails1(null);
           //var_dump($output); 
           
           $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	   $output = array(); $helper = new Helper2();
	
	    // get last date where data were uploaded from DHIS2
	    if($date === null || empty($date)){
	        $result = $db->fetchAll("select max(date) as date from facility_report_rate");
	        $date = $result[0]['date'];
	    }
	
	    $output['last_date'] = $date;
            
            
            $select = $db->select()
	    -> from(array('facility_report_rate' => 'facility_report_rate'),
                    array('COUNT(DISTINCT(facility_external_id)) as count'));
            
            //TP: total_facility_count 
            $result = $db->fetchAll($select);
            $output['total_facility_count'] = $result[0]['count'];
	
            //the facilities that reported for the month refrenced by $date - denominator
            $select = $db->select()
            -> from(array('facility_report_rate' => 'facility_report_rate'),
            array('count(*) as count'))
            ->where("date='" . $date . "'");

            $result = $db->fetchAll($select);
            $output['total_facility_count_month'] = $result[0]['count'];
            
            $output['larc_facility_count'] = $helper->getReportingFacilityWithTrainedHW($date, 'larc');
            $output['fp_facility_count'] = $helper->getReportingFacilityWithTrainedHW($date, 'fp');
            
            $output['larc_consumption_facility_count'] = $helper->getReportingConsumptionFacilities($date, 'larc');
            $output['fp_consumption_facility_count'] = $helper->getReportingConsumptionFacilities($date, 'fp');
            
            $output['larc_stock_out_facility_count'] = $helper->getReportingStockedOutFacilitiesWithTrainedHWCount($date, 'larc');
            $output['fp_stock_out_facility_count'] = $helper->getReportingStockedOutFacilitiesWithTrainedHWCount($date, 'fp');
            //var_dump($output); exit;
            
            return $output;
            
      }  
  
        
  public function fetchCLNDetails($dataName = null, $id = null, $where = null, $group = null, $useName = null) {
	    
	    //file_put_contents('c:\wamp\logs\php_debug.log', 'fetchCLNDetails >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
	    //var_dump('all= ', $dataName, $where, $group, $useName, 'END');
	    //$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);
	    
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    $output = array();
	
	    switch ($dataName) {
	        case 'geo':
	            $select = $db->select()
	            ->from('location');
	            if ($where) // comma seperated string for sql
	                $select = $select->where($where)
	                ->order('location_name');
	            $result = $db->fetch($select);
	
	            foreach ($result as $row){
	
	                $output[] = array(
	                    "id" => $row['id'],
	                    "name" => $row['location_name'],
	                    "tier" => $row['tier'],
	                    "parent_id" => $row['parent_id'],
	                    "consumption" => 0,
	                    "link" => Settings::$COUNTRY_BASE_URL . "/dashboard/dash3/id/" . $row['id'],
	                    "type" => 1
	                );
	            }
	            break;
	        case 'location':
	            
                //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI 171>'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
	            //var_dump('all=', $dataName, $id, $where, $group, $useName, "END");
	            //$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);
	            
	    	    $create_view = $db->select()
                                      ->from(array('f' => 'facility'),
	           array(
	            'f.id as F_id',
	            'f.facility_name as F_facility_name',
	            //"replace(f.facility_name, '\'', '\\\'') as F_facility_name',",
	            'f.location_id as F_location_id',
	            'l1.id as L1_id',
	            'l1.location_name as L1_location_name',
	            'l2.id as L2_id',
	            'l2.location_name as L2_location_name',
	            'l2.parent_id as L2_parent_id',
	            'l3.location_name as L3_location_name',
	            'cno.id as CNO_id',
	            'cno.external_id as CNO_external_id',
	            'cno.commodity_name as CNO_commodity_name',
	            'c.date as C_date',
	         	'ifnull(sum(c.consumption),0) as C_consumption' ))
	         	->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
	    	    ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
	    	    ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
	    	    ->joinLeft(array("c" => "commodity"), 'f.id = c.facility_id')
	    	    ->joinLeft(array("cno" => "commodity_name_option"), 'c.name_id = cno.id')
	    	    ->joinInner(array('mc' => 'lc_view_subselect'), 'f.id = mc.C_facility_id and month(c.date) = month(mc.C_date) and year(c.date) = year(mc.C_date)')
	    	    ->where($where)
	    	    ->group(array($group))
	    	    ->order(array('L3_location_name', 'L2_location_name', 'L1_location_name', 'F_facility_name'));
	    
	    $sql = $create_view->__toString();
	    $sql = str_replace('`C_consumption`,', '`C_consumption`', $sql);
	    $sql = str_replace('`l1`.*,', '', $sql);
	    $sql = str_replace('`l2`.*,', '', $sql);
	    $sql = str_replace('`l3`.*,', '', $sql);
	    $sql = str_replace('`c`.*,', '', $sql);
	    $sql = str_replace('`cno`.*,', '', $sql);
	    $sql = str_replace('`mc`.*', '', $sql);
	    
	    try{
	        $sql='create or replace view lc_view as ('.$sql.')';
	        $db->fetchOne( $sql );
	    }
	    catch (Exception $e) {
	        //echo $e->getMessage();
	        //var_dump('error', $e->getMessage());
	        
	    }

	    $select = $db->select()
	    ->from(array('cv' => 'lc_view_extended_pivot_non_null'),
	        array(
	            'L1_location_name',
	            'L2_location_name',
	            'L3_location_name',
	            'ifnull(sum(cv.consumption1),0) as consumption1',
	            'ifnull(sum(cv.consumption2),0) as consumption2',
	            'ifnull(sum(cv.consumption3),0) as consumption3',
	            'ifnull(sum(cv.consumption4),0) as consumption4',
	            'ifnull(sum(cv.consumption5),0) as consumption5',
	            'ifnull(sum(cv.consumption6),0) as consumption6',
	            'ifnull(sum(cv.consumption7),0) as consumption7', ))
	    	->group(array($useName))
	    	->order(array('L3_location_name', 'L2_location_name', 'L1_location_name'));
	    
	    $result = $db->fetchAll($select);
	        
	    foreach ($result as $row){
	    
	        $output[] = array(
	            "L1_location_name" => $row['L1_location_name'],
	            "L2_location_name" => $row['L2_location_name'],
	            "L3_location_name" => $row['L3_location_name'],
	           // "location_name" => str_replace(' ', '', $row[$useName]),
	           // "location_name" => substr($row[$useName], 0, 16),
	            "location_name" => $row[$useName],
	            "consumption1" => $row['consumption1'],
	            "consumption2" => $row['consumption2'],
	            "consumption3" => $row['consumption3'],
	            "consumption4" => $row['consumption4'],
	            "consumption5" => $row['consumption5'],
	            "consumption6" => $row['consumption6'],
	            "consumption7" => $row['consumption7'],
	            "type" => 1
	        );
	    }
	            break;
	            
	        case 'facility':
	            
	            //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI 231>'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
	            //var_dump('all=', $dataName, $id, $where, $group, $useName, "END");
	            //$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);
	            
	            $create_view = $db->select()
	            ->from(array('f' => 'facility'),
	                array(
	                    'f.id as F_id',
	                    'f.facility_name as F_facility_name',
	                    //"replace(f.facility_name, '\'', '\\\'') as F_facility_name',",
	                    'f.location_id as F_location_id',
	                    'l1.id as L1_id',
	                    'l1.location_name as L1_location_name',
	                    'l2.id as L2_id',
	                    'l2.location_name as L2_location_name',
	                    'l2.parent_id as L2_parent_id',
	                    'l3.location_name as L3_location_name',
	                    'cno.id as CNO_id',
	                    'c.date as C_date',
                        'ifnull(sum(c.consumption),0) as C_consumption' ))	            	         	
                        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
	            	    ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
	            	    ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
	            	    ->joinLeft(array("c" => "commodity"), 'f.id = c.facility_id')
	            	    ->joinLeft(array("cno" => "commodity_name_option"), 'c.name_id = cno.id')
	            	    ->joinInner(array('mc' => 'lc_view_subselect'), 'f.id = mc.facility_id and month(c.date) = C_monthDate')
	            	    ->where($where)
	            	    ->group(array($group))
	            	    ->order(array('L3_location_name', 'L2_location_name', 'L1_location_name', 'F_facility_name'));
	            	    
	            	    $sql = $create_view->__toString();
	            	    $sql = str_replace('`C_consumption`,', '`C_consumption`', $sql);
	            	    $sql = str_replace('`l1`.*,', '', $sql);
	            	    $sql = str_replace('`l2`.*,', '', $sql);
	            	    $sql = str_replace('`l3`.*,', '', $sql);
	            	    $sql = str_replace('`c`.*,', '', $sql);
	            	    $sql = str_replace('`cno`.*,', '', $sql);
	            	    $sql = str_replace('`mc`.*', '', $sql);
	            	    
	            	    try{
	            	        $sql='create or replace view lc_view as ('.$sql.')';
	            	        $db->fetchOne( $sql );
	            	    }
	            	    catch (Exception $e) {
	            	        //echo $e->getMessage();
	            	    }
	            	    
	            	    
	                   if($useName == 'F_facility_name'){
	                       $select = $db->select()
	                       ->from(array('cv' => 'lc_view_extended_pivot_non_null'),
	                           array(
	                               'F_facility_name',
	                               'L1_location_name',
	                               'L2_location_name',
	                               'L3_location_name',
	                               'consumption1',
	                               'consumption2',
	                               'consumption3',
	                               'consumption4', 
	                               'consumption5',
	                               'consumption6',
	                               'consumption7',
	                           ));
	                   }
	                   else {
	            	    $select = $db->select()
	            	    ->from(array('cv' => 'lc_view_extended_pivot_non_null'),
	            	        array(
	            	            'F_facility_name',
	            	            'L1_location_name',
	            	            'L2_location_name',
	            	            'L3_location_name',
                        'ifnull(sum(cv.consumption1),0) as consumption1',
                        'ifnull(sum(cv.consumption1),0) as consumption2',
                        'ifnull(sum(cv.consumption2),0) as consumption3',
                        'ifnull(sum(cv.consumption3),0) as consumption4',
                        'ifnull(sum(cv.consumption4),0) as consumption5',
                        'ifnull(sum(cv.consumption5),0) as consumption6',
                        'ifnull(sum(cv.consumption6),0) as consumption7', ))
	            	    	->group(array('L1_location_name'))
	            	    	->order(array('L3_location_name', 'L2_location_name', 'L1_location_name'));
	                   }
	            	    
	            	    $result = $db->fetchAll($select);
	            	    
	            	    foreach ($result as $row){
	            	    
	            	        $output[] = array(
	            	            "L1_location_name" => $row['L1_location_name'],
	            	            "L2_location_name" => $row['L2_location_name'],
	            	            "L3_location_name" => $row['L3_location_name'],
	            	            "location_name" => $row[$useName], 0, 16,
	            	    "consumption1" => $row['consumption1'],
                        "consumption2" => $row['consumption2'],
                        "consumption3" => $row['consumption3'],
                        "consumption4" => $row['consumption4'],
                        "consumption5" => $row['consumption5'],
                        "consumption6" => $row['consumption6'],
                        "consumption7" => $row['consumption7'],
	            	            "type" => 1
	            	        );
	            	    }
	            break;
	    }
	    return $output;
	}
	
	public function fetchAMCDetails($where = null) {
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    
	    $create_view = $db->select()
	    ->from(array('c' => 'commodity'),
	        array(
	            'cno.id as CNO_id',
	            'cno.external_id as CNO_external_id',
	            'cto.id as CTO_id',
	            'c.date as date',
	            'f.id as F_id',
	            'f.facility_name as F_facility_name',
	            'f.location_id as F_location_id',
	            'l1.id as L1_id',
	            'l1.location_name as L1_location_name',
	            'l2.id as L2_id',
	            'l2.location_name as L2_location_name',
	            'l2.parent_id as L2_parent_id',
	            'l3.location_name as L3_location_name',
	            'ifnull(sum(c.consumption),0) as C_consumption' )) 
        	    ->joinLeft(array('f' => "facility"), 'f.id = c.facility_id')
        	    ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
	    	    ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
	    	    ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
	    	    ->joinLeft(array("cno" => "commodity_name_option"), 'c.name_id = cno.id')
	    	    ->joinLeft(array("cto" => "commodity_type_option"), 'c.type_id = cto.id')
	    	    ->where($where)
	    	    ->group(array('CNO_external_id', 'c.date'))
	    	    ->order(array('L3_location_name', 'L2_location_name', 'L1_location_name', 'F_facility_name'));
	    	    
	    	   $sql = $create_view->__toString();
	    	   $sql = str_replace('`C_consumption`,', '`C_consumption`', $sql);
	    	   $sql = str_replace('`f`.*,', '', $sql);
	    	   $sql = str_replace('`l1`.*,', '', $sql);
	    	   $sql = str_replace('`l2`.*,', '', $sql);
	    	   $sql = str_replace('`l3`.*,', '', $sql);
	    	   $sql = str_replace('`cno`.*,', '', $sql);
	    	   $sql = str_replace('`cto`.*', '', $sql);
	    	    
	    	    try{
	    	        $sql='create or replace view amc_view as ('.$sql.')';
	    	        $db->fetchOne( $sql );
	    	    }
	    	    catch (Exception $e) {
	    	        //echo $e->getMessage();
	    	        //var_dump('error', $e->getMessage());
	    	        
	    	    }
	    
	    $output = array();
	    
	    $orderClause = new Zend_Db_Expr("`c`.`date` desc limit 12");
	    
	    $select = $db->select()
	    ->from(array('cv' => 'amc_view_extended_pivot_non_null'),
	        array(
	            'monthname(cv.date) as month',
	            'cv.consumption1 as consumption1',
	            'cv.consumption2 as consumption2',
	            'cv.consumption3 as consumption3',
	            'cv.consumption4 as consumption4',
	            'cv.consumption5 as consumption5',
	            'cv.consumption6 as consumption6',
	            'cv.consumption7 as consumption7'))	            
	            ->order(array('cv.date desc'))
	            ->limit('12');
	    
	    
	    /*
	    select monthname(date) as month, sum(implant_consumption) as implant_consumption, sum(injectable_consumption) as injectable_consumption
	    from amc_view_extended_pivot_non_null
	    group by monthname(date)
	    order by date;
	    */
	    
	            
	    $result = $db->fetchAll($select);
	    
	    foreach ($result as $row){
	    
	        $output[] = array(
	            "commodity_name" => $row['commodity_name'],
	            "month" => $row['month'],
	            "consumption1" => $row['consumption1'],
	            "consumption2" => $row['consumption2'],
	            "consumption3" => $row['consumption3'],
	            "consumption4" => $row['consumption4'],
	            "consumption5" => $row['consumption5'],
	            "consumption6" => $row['consumption6'],
	            "consumption7" => $row['consumption7']
	        );
	    }
	
	    return array_reverse($output, true);
	}
	
	public function fetchHCWTDetails($where = null) {
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    $output = array();
	     
	    $select = $db->select()
	    ->from(array('hv' => 'hcwt_view_extended_pivot_non_null'),
	        array(
	            'month(hv.date) as month',
	            'hv.fp_trained as fp_trained',
                'hv.larc_trained as larc_trained'))
                ->group(array('month(hv.date)'))
		        ->order(array('hv.date desc'))
		        ->limit(array('12') );
		    
		    /*
select year(date) as year, sum(fp_trained) as fp_trained, sum(larc_trained) as larc_trained
from hcwt_view_extended_pivot_non_null 
group by year(date)
order by date;

		    */
		            
		    $result = $db->fetchAll($select);
		    
		    foreach ($result as $row){
		    
		        $output[] = array(
		            "month" => $row['month'],
		            "fp_trained" => $row['fp_trained'],
		            "larc_trained" => $row['larc_trained']
		        );
		    }
		
		    return $output;
		}
		
		public function fetchPercentFacHWProvidingStockOutDetails($cnoStockOutWhere = null, $geoWhere = null, $group = null, $useName = null ) {
		    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
		    
		    // national
		    $stock_out_sql = $db->select()
		    ->from(array('c' => 'commodity'),
		        array(
		            'l1.location_name as L1_location_name',
		            'l2.location_name as L2_location_name',
		            'l3.location_name as L3_location_name',
		            "count(distinct c.facility_id) as numer" ))
		        ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
		        ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->where($cnoStockOutWhere)
		        ->order($useName); 
		    
		    $sql = $stock_out_sql->__toString();
		    $sql = str_replace(' AS `numer`,',' AS `numer`', $sql);
		    $sql = str_replace('`cno`.*,', '', $sql);
		    $sql = str_replace('`f`.*,', '', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*', '', $sql);
		    
	    
		    $result = $db->fetchAll($sql);
		    
		    foreach ($result as $row){
		        $numer[] = array(
		            "location" => 'National',
		            "numer" => $row['numer'],
		            "color" => 'black',
		        );
		    }
		    
		    $select = $db->select()
		    ->from(array('f' => 'facility'),
		        array(
		            'l1.location_name as L1_location_name',
		            'l2.location_name as L2_location_name',
		            'l3.location_name as L3_location_name',
		            'count(distinct(frr.facility_external_id)) as denom' ))
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->order($useName);
		    
		    $sql = $select->__toString();
		    $sql = str_replace(' AS `denom`,',' AS `denom`', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*', '', $sql);
		    
		    $result = $db->fetchAll($sql);
		    
		    foreach ($result as $row){
		        $denom[] = array(
		            "location" => 'National',
		            "denom" => $row['denom'],
		            "color" => 'black',
		        );
		    }
		     		    
		    // geo, provided last 6 months in stock_out
		    
		    $stock_out_sql = $db->select()
		    ->from(array('c' => 'commodity'),
		        array(
		            'l1.location_name as L1_location_name',
		            'l2.location_name as L2_location_name',
		            'l3.location_name as L3_location_name',
		            "count(distinct c.facility_id) as numer" ))
		        ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
		        ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->where($cnoStockOutWhere)
		        ->where($geoWhere)
		        ->group($group)
		        ->order($useName); 
		    
		    $sql = $stock_out_sql->__toString();
		    $sql = str_replace(' AS `numer`,',' AS `numer`', $sql);
		    $sql = str_replace('`cno`.*,', '', $sql);
		    $sql = str_replace('`f`.*,', '', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*', '', $sql);
		    
	    
		    $result = $db->fetchAll($sql);
		    
		    foreach ($result as $row){
		        $numer[] = array(
		            "location" => $row[$useName],
		            "numer" => $row['numer'],
		            "color" => 'blue',
		        );
		    }
		    
		    $select = $db->select()
		    ->from(array('f' => 'facility'),
		        array(
		            'l1.location_name as L1_location_name',
		            'l2.location_name as L2_location_name',
		            'l3.location_name as L3_location_name',
		            'count(distinct(frr.facility_external_id)) as denom' ))
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->where($geoWhere)
		        ->group($group)
		        ->order($useName);
		    
		    $sql = $select->__toString();
		    $sql = str_replace(' AS `denom`,',' AS `denom`', $sql);
		    $sql = str_replace('`cno`.*,', '', $sql);
		    $sql = str_replace('`f`.*,', '', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*', '', $sql);
		    
		    $result = $db->fetchAll($sql);
		    
		    foreach ($result as $row){
		        $denom[] = array(
		            "location" => $row[$useName],
		            "denom" => $row['denom'],
		            "color" => 'blue',
		        );
		    }

		    foreach($denom as $i => $drow){
		        
		        foreach($numer as $j => $nrow){ // if missing numer elements
		            
                    if ($drow['location'] == $nrow['location'])
                        $output[] = array('location' => $drow['location'], 'percent' => $numer[$j]['numer']/ $drow['denom'], 'color' => $drow['color']);
		        }
		    }
		    
		    //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI fetchPercentFacHWProvidingStockOutDetails  >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
		    //var_dump('$numer= ', $numer, 'END');
		    //var_dump('$denom= ', $denom, 'END');
		    //var_dump('$output= ', $output, 'END');
		    //var_dump('$ouput= ', $output, 'END');
		    //$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);
		
		    return $output;
		}
		
		public function fetchPercentFacHWTrainedStockOutDetails($trainingWhere = null, $stockOutWhere = null, $sixMonthWhere = null, $geoWhere = null, $group = null, $useName = null ) {
		    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
		    $numer = array();
		    
		    // provided last 6 months in stock_out
		    //national
		    $stock_out_sql = $db->select()
		    ->from(array('c' => 'commodity'),
		        array("frr.facility_external_id as fei" ))
		        ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
		        ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->where($stockOutWhere)  // s.b.  cno.external_id in ('JyiR2cQ6DZT') and c.date = (select max(date) from commodity) )
		        ->where($geoWhere);
		    
		    $sql = $stock_out_sql->__toString();
		    $sql = str_replace('`frr`.`facility_external_id` AS `fei`,','`frr`.`facility_external_id` AS `fei`', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`cno`.*,', '', $sql);
		    $sql = str_replace('`cto`.*,', '', $sql);
		    $sql = str_replace('`f`.*,', '', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*', '', $sql);
		    
		    $training_sql = $db->select()
		    ->from(array('pt' => 'person_to_training'),
		        array(
		            "$useName as location",
           		    'count(distinct(frr.facility_external_id)) as numer' ))
   		        ->joinLeft(array('p' => "person"), 'pt.person_id = p.id')
   		        ->joinLeft(array('f' => "facility"), 'p.facility_id = f.id')
   		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
   		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
   		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
   		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
   		        ->joinLeft(array('t' => "training"), 'pt.training_id = t.id')
   		        ->joinInner(array('tto' => "training_title_option"), 't.training_title_option_id = tto.id')
   		        ->where($trainingWhere) // s.b. t.training_title_option_id = 1  
   		        //->where($geoWhere)
   		        ->where(" frr.facility_external_id in ( $sql ) " );
    		    //->group( "$useName" );
		    		    
		    		    $sql = $training_sql->__toString();
		    		    $sql = str_replace(' AS `numer`,',' AS `numer`', $sql);
		    		    $sql = str_replace('`p`.*,', '', $sql);
		    		    $sql = str_replace('`f`.*,', '', $sql);
		    		    $sql = str_replace('`frr`.*,', '', $sql);
		    		    $sql = str_replace('`l1`.*,', '', $sql);
		    		    $sql = str_replace('`l2`.*,', '', $sql);
		    		    $sql = str_replace('`l3`.*,', '', $sql);
		    		    $sql = str_replace('`t`.*,', '', $sql);
		    		    $sql = str_replace('`tto`.*', '', $sql);
		    		    
		    		    $result = $db->fetchAll($sql);
		    		
		    		    foreach ($result as $row){
		    		        $numer[] = array(
		    		            "location" => 'National',
		    		            "numer" => $row['numer'],
		    		            "color" => 'black',
		    		        );
		    		    }
		    		    
		    		    $select = $db->select()
		    		    ->from(array('c' => 'commodity'),
		    		        array(
		    		            "$useName as location",
		    		            'count(distinct(c.facility_id)) as denom' ))
		    		        ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
		    		        ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
		    		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		    		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		    		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		    		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		    		        ->where($sixMonthWhere); // s.b. cno.external_id in ('w92UxLIRNTl', 'H8A8xQ9gJ5b', 'ibHR9NQ0bKL', 'yJSLjbC9Gnr', 'vDnxlrIQWUo', 'krVqq8Vk5Kw') or cno.external_id = 'DiXDJRmPwfh', LARC
		    		        //->where($geoWhere);
		    		        //->group(array( $useName ));
		    		    
		    		    $sql = $select->__toString();
		    		    $sql = str_replace(' AS `denom`,',' AS `denom`', $sql);
		    		    $sql = str_replace('`cno`.*,', '', $sql);
		    		    $sql = str_replace('`f`.*,', '', $sql);
		    		    $sql = str_replace('`frr`.*,', '', $sql);
		    		    $sql = str_replace('`l1`.*,', '', $sql);
		    		    $sql = str_replace('`l2`.*,', '', $sql);
		    		    $sql = str_replace('`l3`.*', '', $sql);
		    		
		    		    $result = $db->fetchAll($sql);
		    		
		    		    foreach ($result as $row){
		    		        $denom[] = array(
		    		            "location" => 'National',
		    		            "denom" => $row['denom'],
		    		            "color" => 'black',
		    		        );
		    		    }
		    		    
		    
		    // geo
		    $stock_out_sql = $db->select()
		    ->from(array('c' => 'commodity'),
		        array("frr.facility_external_id as fei" ))
		        ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
		        ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->where($stockOutWhere)  // s.b.  cno.external_id in ('JyiR2cQ6DZT') and c.date = (select max(date) from commodity) ) 
 		        ->where($geoWhere);

		    $sql = $stock_out_sql->__toString();
		    $sql = str_replace('`frr`.`facility_external_id` AS `fei`,','`frr`.`facility_external_id` AS `fei`', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`cno`.*,', '', $sql);
		    $sql = str_replace('`cto`.*,', '', $sql);
		    $sql = str_replace('`f`.*,', '', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*', '', $sql);
		    
		    $training_sql = $db->select()
		    ->from(array('pt' => 'person_to_training'),
		        array(
		            "$useName as location",
		            'count(distinct(frr.facility_external_id)) as numer' ))
 		            
		        ->joinLeft(array('p' => "person"), 'pt.person_id = p.id')
		        ->joinLeft(array('f' => "facility"), 'p.facility_id = f.id')
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->joinLeft(array('t' => "training"), 'pt.training_id = t.id')
		        ->joinInner(array('tto' => "training_title_option"), 't.training_title_option_id = tto.id')
		        ->where($trainingWhere) // s.b. t.training_title_option_id = 1  
		        ->where($geoWhere)
		        ->where(" frr.facility_external_id in ( $sql ) " )
		        //->group(array( $useName ));
		    ->group( "$useName" );
		    
		    $sql = $training_sql->__toString();
		    $sql = str_replace(' AS `numer`,',' AS `numer`', $sql);
		    $sql = str_replace('`p`.*,', '', $sql);
		    $sql = str_replace('`f`.*,', '', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*,', '', $sql);
		    $sql = str_replace('`t`.*,', '', $sql);
		    $sql = str_replace('`tto`.*', '', $sql);
		    
		   // $final_count_select = 'select L1_location_name, L2_location_name, L3_location_name, count(distinct(fei)) as cnt from ( ' . $sql . ')t group by ' . $useName;
		    
		    $result = $db->fetchAll($sql);
		
		    foreach ($result as $row){
		        $numer[] = array(
		            "location" => $row['location'],
		            "numer" => $row['numer'],
		            "color" => 'blue',
		        );
		    }
		    
		    $select = $db->select()
		    ->from(array('c' => 'commodity'),
		        array(
		            "$useName as location",
		            'count(distinct(c.facility_id)) as denom' ))
		        ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
		        ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->where($sixMonthWhere) // s.b. cno.external_id in ('w92UxLIRNTl', 'H8A8xQ9gJ5b', 'ibHR9NQ0bKL', 'yJSLjbC9Gnr', 'vDnxlrIQWUo', 'krVqq8Vk5Kw') or cno.external_id = 'DiXDJRmPwfh', LARC
		        ->where($geoWhere)
		        ->group(array( $useName ));
		    
		    $sql = $select->__toString();
		    $sql = str_replace(' AS `denom`,',' AS `denom`', $sql);
		    $sql = str_replace('`cno`.*,', '', $sql);
		    $sql = str_replace('`f`.*,', '', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*', '', $sql);
		
		    $result = $db->fetchAll($sql);
		
		    foreach ($result as $row){
		        $denom[] = array(
		            "location" => $row['location'],
		            "denom" => $row['denom'],
		            "color" => 'blue',
		        );
		    }
		     
		    foreach($denom as $i => $drow){
		    
		        foreach($numer as $j => $nrow){ // if missing numer elements
		    
		            if ($drow['location'] == $nrow['location'])
		                $output[] = array('location' => $drow['location'], 'percent' => $numer[$j]['numer']/ $drow['denom'], 'color' => $drow['color']);
		        }
		    }
		    
		    //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI fetchPercentFacHWProvidingStockOutDetails  >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
		    //var_dump('$numer= ', $numer, 'END');
		    //var_dump('$denom= ', $denom, 'END');
		    //var_dump('$output= ', $output, 'END');
		    //var_dump('$ouput= ', $output, 'END');
		    //$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);
		
		    return $output;
				}
				

		public function fetchPercentFacHWTrainedProvidingDetails($trainingWhere = null, $cnoWhere = null, $geoWhere = null, $group = null, $useName = null ) {
		    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
		    $trained = array();
		  
		     
		    $select = $db->select()
		    ->from(array('pt' => 'person_to_training'),
		        array(
		            "count(*) as cnt" ))
		
		            ->joinLeft(array('p' => "person"), 'pt.person_id = p.id')
		            ->joinLeft(array('f' => "facility"), 'p.facility_id = f.id')
		            ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		            ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		            ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		             
		            ->joinLeft(array("t" => "training"), 'pt.training_id = t.id')
		            ->joinInner(array('tto' => training_title_option), 't.training_title_option_id = tto.id')
		            ->where($trainingWhere);
		     
		    $result = $db->fetchAll($select);
		     
		    foreach ($result as $row){
		        $trained[] = array(
		            "location" => 'National',
		            "cnt" => $row['cnt'],
		            "color" => 'black',
		        );
		    }
		     
		    $select = $db->select()
		    ->from(array('pt' => 'person_to_training'),
		        array(
		            'l1.location_name as L1_location_name',
		            'l2.location_name as L2_location_name',
		            'l3.location_name as L3_location_name',
		            "count(*) as cnt" ))
		             
		            ->joinLeft(array('p' => "person"), 'pt.person_id = p.id')
		            ->joinLeft(array('f' => "facility"), 'p.facility_id = f.id')
		            ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		            ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		            ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		
		            ->joinLeft(array("t" => "training"), 'pt.training_id = t.id')
		            ->joinInner(array('tto' => training_title_option), 't.training_title_option_id = tto.id')
		            ->where($geoWhere)
		            ->where($trainingWhere)
		            ->group(array($useName))
		            ->order(array($useName));
		
		    $result = $db->fetchAll($select);
		
		    foreach ($result as $row){
		        $color = 'blue' ;
		
		        $trained[] = array(
		            "location" => $row[$useName],
		            "cnt" => $row['cnt'],
		            "color" => $color,
		        );
		    }
		
		    //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI 243 >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
		    //var_dump($output,"END");
		    //var_dump('id=', $id);
		    //$result = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $result .PHP_EOL, FILE_APPEND | LOCK_EX);
		
		$providing = array();
        
        $select = $db->select()
        ->from(array('c' => 'commodity'),
            array("count(distinct(c.facility_id)) as cnt" ))
             
            ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
            ->joinLeft(array('cto' => "commodity_type_option"), 'c.type_id = cto.id')
            ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
            ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
            ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
            ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
            ->where($cnoWhere);
        
        $result = $db->fetchAll($select);
        
        foreach ($result as $row){
            $providing[] = array(
                "location" => 'National',
                "cnt" => $row['cnt'],
                "color" => 'black',
            );
        }
         
	        $select = $db->select()
	        ->from(array('c' => 'commodity'),
	            array(
	                'l1.location_name as L1_location_name',
	                'l2.location_name as L2_location_name',
	                'l3.location_name as L3_location_name',
	                "count(distinct(c.facility_id)) as cnt" ))
        
	                ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
	                ->joinLeft(array('cto' => "commodity_type_option"), 'c.type_id = cto.id')
	                ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
	                ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
	                ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
	                ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
	                ->where($geoWhere)
	                ->where($cnoWhere)
	                ->group(array( $useName ))
	                ->order(array( $useName ));
	    
	                    $result = $db->fetchAll($select);
	    
	                    foreach ($result as $row){
	                        $color = 'blue' ;
	    
	                        $providing[] = array(
	                           "location" => $row[$useName],
	                           "cnt" => $row['cnt'],
	                           "color" => $color,
	                            );
	                    }
	                    
	                    foreach($providing as $i => $row){
	                        
                            $output[] = array('location' => $row['location'], 'percent' => $trained[$i]['cnt']/ $row['cnt'], 'color' => $row['color']);                      

	                    }
	    
	    		//file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI fetchPercentFacHWTrainedProvidingDetails  >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
	    		//var_dump('$trained= ', $trained, 'END');
	    		//var_dump('$providing= ', $providing, 'END');
	    		//var_dump('$ouput= ', $output, 'END');
	    		//$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);	    
		    
		    return $output;
		}
		
		
    public function fetchPercentFacHWTrainedDetails1($trainingWhere = null, $geoWhere = null, $group = null, $useName = null ) {
	    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	    $output = array();
	    
	    //national
	    $create_view = $db->select()
	    ->from(array('f' => 'facility'),
	        array(
	             	"count(distinct(frr.facility_external_id)) as denom"))
	             	->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
	                ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
	                ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
	                ->joinInner(array('frr' => "facility_report_rate"), 'f.external_id = frr.facility_external_id');
	            
	            $sql = $create_view->__toString();
	            $sql = str_replace('AS `denom`,', 'AS `denom`', $sql);
	            $sql = str_replace('`l1`.*,', '', $sql);
	            $sql = str_replace('`l2`.*,', '', $sql);
	            $sql = str_replace('`l3`.*,', '', $sql);
	            $sql = str_replace('`frr`.*', '', $sql);
	            
	            try{
	                $sql = 'create or replace view pft_denom_view as ('.$sql.')';
	                $db->fetchOne($sql);
	            }
	            catch (Exception $e) { // normal operation throws "General Error"
	                //echo $e->getMessage();
	                //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
	                //var_dump('ERROR= ', $e->getMessage(), "END");
	                //$result = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $result .PHP_EOL, FILE_APPEND | LOCK_EX);
	            }
	            
	            $subSelect = new Zend_Db_Expr("( select denom from pft_denom_view )"); 
	    
	    $select = $db->select()
	    ->from(array('pt' => 'person_to_training'),
	        array(
	            "count(*) / $subSelect as percent" ))
	             
	            ->joinLeft(array('p' => "person"), 'pt.person_id = p.id')
	            ->joinLeft(array('f' => "facility"), 'p.facility_id = f.id')
	            ->joinInner(array('frr' => "facility_report_rate"), 'f.external_id = frr.facility_external_id')
	            ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
	            ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
	            ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
	            ->joinLeft(array("t" => "training"), 'pt.training_id = t.id')
	            ->joinInner(array('tto' => training_title_option), 't.training_title_option_id = tto.id')
	            ->where($trainingWhere);
	    
	    $sql = $select->__toString();
	    $sql = str_replace('AS `percent`,', 'AS `percent`', $sql);
	    $sql = str_replace('`p`.*,', '', $sql);
	    $sql = str_replace('`f`.*,', '', $sql);
	    $sql = str_replace('`frr`.*,', '', $sql);
	    $sql = str_replace('`l1`.*,', '', $sql);
	    $sql = str_replace('`l2`.*,', '', $sql);
	    $sql = str_replace('`l3`.*,', '', $sql);
	    $sql = str_replace('`t`.*,', '', $sql);
	    $sql = str_replace('`tto`.*', '', $sql);
	    
	    $result = $db->fetchAll($sql);
	    
	    foreach ($result as $row){
	        $output[] = array(
	            "Location" => 'National',
	            "percent" => $row['percent'],
	            "color" => 'black',
	        );
	    }
	    
    //geo	    
	    $create_view = $db->select()
	    ->from(array('f' => 'facility'),
	        array(
	            "$group as Location_id", // s.b. l1.id, l2.id, or l3.id
	            
	            "count(distinct(frr.facility_external_id)) as denom"))
	            ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
	            ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
	            ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
	            ->joinInner(array('frr' => "facility_report_rate"), 'f.external_id = frr.facility_external_id')
	            ->where($geoWhere)
	            ->group(array( $group ));
	        
	        $sql = $create_view->__toString();
	        $sql = str_replace('AS `denom`,', 'AS `denom`', $sql);
	        $sql = str_replace('`l1`.*,', '', $sql);
	        $sql = str_replace('`l2`.*,', '', $sql);
	        $sql = str_replace('`l3`.*,', '', $sql);
	        $sql = str_replace('`frr`.*', '', $sql);
	        
	        try{
	            $sql = 'create or replace view pft_denom_view as ('.$sql.')'; // could reuse identical pfp_denom_view but building pft... for concurrency
	            $db->fetchOne($sql);
	        }
	        catch (Exception $e) { // normal operation throws "General Error"
	            //echo $e->getMessage();
	            //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
	            //var_dump('ERROR= ', $e->getMessage(), "END");
	            //$result = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $result .PHP_EOL, FILE_APPEND | LOCK_EX);
	        }
	        
	        $subSelect = new Zend_Db_Expr("( select denom from pft_denom_view where $group = Location_id )"); //corraelated
		
		        $select = $db->select()
		        ->from(array('pt' => 'person_to_training'), 
		          array(
		          
		          'l1.location_name as L1_location_name', 
		          'l2.location_name as L2_location_name',
		          'l3.location_name as L3_location_name',
		          "count(*) / $subSelect as percent" ))
		         
		        ->joinLeft(array('p' => "person"), 'pt.person_id = p.id')
		        ->joinLeft(array('f' => "facility"), 'p.facility_id = f.id')
		        ->joinInner(array('frr' => "facility_report_rate"), 'f.external_id = frr.facility_external_id')
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        
		        ->joinLeft(array("t" => "training"), 'pt.training_id = t.id')
		        ->joinInner(array('tto' => training_title_option), 't.training_title_option_id = tto.id')
		        ->where($geoWhere)
		        ->where($trainingWhere)
		        ->group(array($useName))
                ->order(array('percent'));
		        
		        $sql = $select->__toString();
		        $sql = str_replace('AS `percent`,', 'AS `percent`', $sql);
		        $sql = str_replace('`p`.*,', '', $sql);
		        $sql = str_replace('`f`.*,', '', $sql);
		        $sql = str_replace('`frr`.*,', '', $sql);
		        $sql = str_replace('`l1`.*,', '', $sql);
		        $sql = str_replace('`l2`.*,', '', $sql);
		        $sql = str_replace('`l3`.*,', '', $sql);
		        $sql = str_replace('`t`.*,', '', $sql);
		        $sql = str_replace('`tto`.*', '', $sql);

		        $result = $db->fetchAll($sql);
		        
		        //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI 243 >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
		        //var_dump('$sql=', $sql,"END");
		        //var_dump('$result=', $result,"END");
		        //var_dump('id=', $id);
		        //$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);

		      $cnt = 0;
		      $length = sizeof($result);
		      
		      foreach ($result as $row){
		        $color = 'blue' ;

		        $output[] = array(
		 	    "location" => $row[$useName],
		 	    "percent" => $row['percent'],
		 	    "color" => $color,
		 	      );
		      }
		      

		
		//file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI 243 >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
		//var_dump($output,"END");
		//var_dump('id=', $id);
		//$result = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $result .PHP_EOL, FILE_APPEND | LOCK_EX);

		return $output;
	}
                
                
                
        public function fetchPercentFacHWTrainedDetails($training_type, &$locationNames, $where, $groupFieldName, $havingName, $geoList, $tierValue){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                require_once('Facility.php');
                
                $output = array (); 
                $helper = new Helper2();
                $tierText = $helper->getLocationTierText($tierValue);
                //var_dump($locationNames); exit;
                
                if($training_type == 'fp')
                    $tt_where = "fptrained > 0";
                else if($training_type == 'larc')
                    $tt_where = 'larctrained > 0';

                if($where == '')
                    $locationWhereClause = ' 1=1 ';
                else{
                    $locationFacsList = $helper->getLocationFacilityIDs($where);
                    //echo 'facs: ' . $locationFacsList; exit;
                    $locationWhereClause = 'flv.id IN (' . $locationFacsList . ')';
                }

                $facility = new Facility();
                $totalFacsCount = $facility->getAllFacilityCount();
                
                $coverageHelper = new CoverageHelper();
                $nationalNumerator = $coverageHelper->getAllFacsWithTrainedHWCount($tt_where);
                //echo $nationalNumerator; exit;
                $coverage = $coverageHelper->getCoverageCountFacWithTrainedHW($tt_where, $locationWhereClause, $locationNames, $groupFieldName, $havingName, $geoList, $tierText);
                if($training_type=='larc'){
                    //var_dump ($coverage); exit;
                }
                
                //set the national value first
                $output[] = array(
                    'location' => 'National',
                    'percent' => $nationalNumerator / $totalFacsCount
                    //'percent' => 0
                );
                
                //$percentSum = 0;
                foreach ($coverage as $location=>$numer){
                    $output[] = array(
                                'location' => $location,
                                'percent' => $numer / $totalFacsCount
                    );
                    
                    //$percentSum += $output[count($output)-1]['percent'];
                }
                
                //set national ave
                return $output;
            }
		
                
           
                
/*
	select 
l2.location_name as outer_state,

count(*) /
(select count(*) as cnt
  from facility f
  left join location l1 ON f.location_id = l1.id
  left join location l2 ON l1.parent_id = l2.id
  left join location l3 ON l2.parent_id = l3.id
  where l2.location_name = outer_state
  group by l2.location_name ) as percentage,

tto.training_title_phrase as title_phrase
-- pt.timestamp_created as date
from person_to_training pt
left join person p on pt.person_id = p.id
left join facility f on p.facility_id = f.id
left join location l1 ON f.location_id = l1.id
left join location l2 ON l1.parent_id = l2.id
left join location l3 ON l2.parent_id = l3.id
left join training t on pt.training_id = t.id
left join training_title_option tto on t.training_title_option_id = tto.id
where 1=1
and t.training_title_option_id in (5) 
and pt.award_id in (1,2)
group by outer_state, title_phrase
-- group by f.facility_name
order by percentage 
;

				    */
	
public function fetchPFTPDetails( $cnoWhere = null, $ttoWhere = null, $geoWhere = null, $dateWhere = null, $stockoutWhere = null, $group = null, $useName = null ) {
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    $output = array();
    
    /*
     * fetch numer from pfp_view, 
     *   select C_date, numer from pfp_view where CNO_external_id = 'DiXDJRmPwfh' order by C_date desc limit 12; -- implant
     *   
     * 0) fetch denom, 
     * 1) fetch total from begin-of-time to 1 year ago, 
     * 2) fetch previous year by month, 
     * 3) fill missing months with zero, 
     * 4) calc running total, 
     * 5) use as denom
      
     */
    
    
    //pfp_view with trained
    $select = $db->select()
    ->from(array('c' => 'commodity'),
        array(
            'c.date as C_date',
            'monthname(c.date) as C_monthName',
            'year(c.date) as C_year',
                'count(distinct(c.facility_id)) as numer'))
                ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
                ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
                ->joinInner(array('frr' => "facility_report_rate"), 'f.external_id = frr.facility_external_id')
                
                ->joinLeft(array('p' => "person"), 'f.id = p.facility_id')
                ->joinLeft(array('pt' => "person_to_training"), 'pt.person_id = p.id')
                ->joinLeft(array('t' => "training"), 'pt.training_id = t.id')
                
        	    ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
        	    ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
        	    ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
                ->where($cnoWhere)
                ->where($ttoWhere)
                ->where($geoWhere)
                ->group(array('C_date'))
                ->order(array('C_date desc'))
                ->limit(12);
        
        $sql = $select->__toString();
        $sql = str_replace('AS `numer`,', 'AS `numer`', $sql);
        $sql = str_replace('`c`.*,', '', $sql);
        $sql = str_replace('`cno`.*,', '', $sql);
        $sql = str_replace('`f`.*,', '', $sql);
        $sql = str_replace('`frr`.*,', '', $sql);
        
        $sql = str_replace('`p`.*,', '', $sql);
		$sql = str_replace('`pt`.*,', '', $sql);
		$sql = str_replace('`t`.*,', '', $sql);
        
        $sql = str_replace('`l1`.*,', '', $sql);
        $sql = str_replace('`l2`.*,', '', $sql);
        $sql = str_replace('`l3`.*', '', $sql);
 
    $numer = $db->fetchAll($sql);
    
    // fetch total from begin-of-time to 1 year ago
    $select = $db->select()
    ->from(array('pt' => 'person_to_training'),
        array(
            'sum(1) as start_denom_total' ))
            ->joinLeft(array('p' => "person"), 'pt.person_id = p.id')
            ->joinLeft(array('f' => "facility"), 'p.facility_id = f.id')
            ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
    	    ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
    	    ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
            ->joinLeft(array("t" => "training"), 'pt.training_id = t.id')
            ->joinInner(array('tto' => training_title_option), 't.training_title_option_id = tto.id')
            ->where($ttoWhere)
            ->where($geoWhere)
            ->where('t.training_end_date <= date_sub(now(), interval 365 day)')
            ->order(array('t.training_end_date desc'));
    
            $sql = $select->__toString();
            $sql = str_replace('AS `start_denom_total`,', 'AS `start_denom_total`', $sql);
            $sql = str_replace('`p`.*,', '', $sql);
            $sql = str_replace('`f`.*,', '', $sql);
            $sql = str_replace('`l1`.*,', '', $sql);
            $sql = str_replace('`l2`.*,', '', $sql);
            $sql = str_replace('`l3`.*,', '', $sql);
            $sql = str_replace('`t`.*,', '', $sql);
            $sql = str_replace('`tto`.*', '', $sql);
    
            $start_denom_total = $db->fetchOne( $sql );
            
    //fetch previous year            
    $select = $db->select()
    ->from(array('pt' => 'person_to_training'),
        array(
            't.training_end_date',
            'count(1) as added' ))
            ->joinLeft(array('p' => "person"), 'pt.person_id = p.id')
            ->joinLeft(array('f' => "facility"), 'p.facility_id = f.id')
            ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
            ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
            ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
            ->joinLeft(array("t" => "training"), 'pt.training_id = t.id')
            ->joinInner(array('tto' => training_title_option), 't.training_title_option_id = tto.id')
            ->where($ttoWhere)
            ->where($geoWhere)
            ->where('t.training_end_date between date_sub(now(), interval 365 day) and now() ')
            ->group('t.training_end_date')
            ->order(array('t.training_end_date asc'));
    
    $sql = $select->__toString();
    $sql = str_replace('AS `added`,', 'AS `added`', $sql);
    $sql = str_replace('`p`.*,', '', $sql);
    $sql = str_replace('`f`.*,', '', $sql);
    $sql = str_replace('`l1`.*,', '', $sql);
    $sql = str_replace('`l2`.*,', '', $sql);
    $sql = str_replace('`l3`.*,', '', $sql);
    $sql = str_replace('`t`.*,', '', $sql);
    $sql = str_replace('`tto`.*', '', $sql);
        
    $prev_year_raw = $db->fetchAll( $sql );
    
    // fill missing months with zero,
    foreach ($numer as $nrow){
        $found = false;
        foreach ($prev_year_raw as $prow){
            if($prow['training_end_date'] == $nrow['C_date']){
                $found = true;
            }
        }
        if($found == false){
            $new_rows[] = array($nrow['C_date'], '0');
        }
    }
    
    foreach ($prev_year_raw as $row){
        $prev_year[] = array('training_end_date' => $row['training_end_date'], 'added' => $row['added']);
    }
    $prev_year = array_merge($prev_year, $new_rows);
    
    // fetch total number of facilities reporting for use in tt calc
    
	    $select = $db->select()
	    ->from(array('f' => 'facility'),
	        array(
	            'frr.date',
	            'count(distinct(frr.facility_external_id)) as denom'))
	            ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
	            ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
	            ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
	            ->joinInner(array('frr' => "facility_report_rate"), 'f.external_id = frr.facility_external_id')
	            ->where($geoWhere)
	            ->group(array( 'frr.date' ));
	        
	        $sql = $select->__toString();
	        $sql = str_replace('AS `denom`,', 'AS `denom`', $sql);
	        $sql = str_replace('`l1`.*,', '', $sql);
	        $sql = str_replace('`l2`.*,', '', $sql);
	        $sql = str_replace('`l3`.*,', '', $sql);
	        $sql = str_replace('`frr`.*', '', $sql);
    
        $facilities_reporting = $db->fetchAll( $sql );
    
    // stocked out
		    $select = $db->select()
		    ->from(array('c' => 'commodity'),
		        array(
		        'c.date',
		        'count(distinct(frr.facility_external_id)) AS stocked_out_numer' ))
		        ->joinLeft(array('cno' => "commodity_name_option"), 'c.name_id = cno.id')
		        ->joinLeft(array('f' => "facility"), 'c.facility_id = f.id')
		        ->joinInner(array('frr' => "facility_report_rate"), 'frr.facility_external_id = f.external_id')
		        
		        ->joinLeft(array('p' => "person"), 'f.id = p.facility_id')
		        ->joinLeft(array('pt' => "person_to_training"), 'pt.person_id = p.id')
		        ->joinLeft(array('t' => "training"), 'pt.training_id = t.id')
		        
		        ->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
		        ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
		        ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
		        ->where($stockoutWhere)
		        ->where($ttoWhere)
 		        ->where($geoWhere)
 		        ->group(array( 'c.date' ))
 		        ->order(array('c.date desc'))
		        ->limit(12);
		    

		    $sql = $select->__toString();
		    $sql = str_replace('AS `stocked_out_numer`,','AS `stocked_out_numer`', $sql);
		    $sql = str_replace('`cno`.*,', '', $sql);
		    $sql = str_replace('`f`.*,', '', $sql);
		    $sql = str_replace('`frr`.*,', '', $sql);
		    
		    $sql = str_replace('`p`.*,', '', $sql);
		    $sql = str_replace('`pt`.*,', '', $sql);
		    $sql = str_replace('`t`.*,', '', $sql);
		    
		    $sql = str_replace('`l1`.*,', '', $sql);
		    $sql = str_replace('`l2`.*,', '', $sql);
		    $sql = str_replace('`l3`.*', '', $sql);
		    
		    $stocked_out = $db->fetchAll( $sql );
    
    // calc running total and use as denom
    for ($i = 0; $i < 12; $i++) {
        $denom = $start_denom_total;
        foreach ($prev_year as $prow){
            
            if($nrow['C_date'] >= $prow['training_end_date']){
                $denom = $denom + $prow['added'];
            }
        }
        
        $output[] = array('month' => $numer[$i]['C_monthName'], 'year' => $numer[$i]['C_year'], 
            'tt_percent' => $denom/$facilities_reporting[$i]['denom'],
            'tp_percent' => $numer[$i]['numer']/$denom, 
            'tso_percent' => $stocked_out[$i][stocked_out_numer]/$denom );
    
    //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI PFTP >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
    //var_dump('$month= ', $monthName,"END");
    //var_dump('$numer= ', $nrow['numer'],"END");
    //var_dump('$stocked_out[$i][stocked_out_numer]= ', $stocked_out[$i]['stocked_out_numer'],"END");
    //var_dump('$denom= ', $denom,"END");
    //var_dump('$tmp= ', $tmp,"END");
    //var_dump("$facilities_reporting[$i]['denom']= ", $facilities_reporting[$i]['denom'],"END");
    //var_dump('$output= ', $output,"END");
    //var_dump('$new_rows= ', $new_rows,"END");
    //var_dump('$prev_year= ', $prev_year,"END");
    //var_dump('$start_denom_total= ', $start_denom_total,"END");
    //$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    return $output;
}	


	
public function fetchPFPDetails( $where = null, $group = null, $useName = null ) {
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    $output = array();
    
    //$denominator = $this->getMonthlyFacilitiesReportingWithConsumption();
    
    $select = $db->select()
    ->from(array('ccfc' => 'commodity_consumption_facility_count_view'),
        array('*'))
        ->order(array('ccfc.date DESC'))
        ->limit(12);
    
    $monthlyConsumption = $db->fetchAll($select);

    foreach ($monthlyConsumption as $i => $row){
        $output[] = array(
            "month" => date('F', strtotime($row['date'])),
            "year" => date('Y', strtotime($row['date'])),
            "fp_percent" => $row['fp_facilities_reporting']/$row['total_facilities_reporting'],
            "larc_percent" => $row['larc_facilities_reporting']/$row['total_facilities_reporting'],
        );
    }
    
    return array_reverse($output, true);
    //return $output;
    
}	


/*TP: 
 * Query for the reporting dates and count of facilities reporting on the dates
 * This data is grouped by dates. It gets data from the last 12 months
 * Return: Resultset of dates and total facilities reporting and that have consumption per month in last 12 months
 */
public function getMonthlyFacilitiesReportingWithConsumption(){
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    
    $create_view = $db->select()
    ->from(array('ccfc' => 'commodity_consumption_facility_count_view'),
        array('*'))
        ->order(array('ccfc.date DESC'))
        ->limit(12);
            
            $sql = $create_view->__toString();
            //echo $sql; exit;
//            $sql = str_replace('AS `denom`,', 'AS `denom`', $sql);
//            $sql = str_replace('``.*', '', $sql);
//            $sql = str_replace('`frr`.*', '', $sql);
            
            return $db->fetchAll($select);   
}

public function fetchPFSODetails($where = null) {
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    $output = array();  
    $helper = new Helper2();
    
    
    /******************** BEGIN NEW QUERY ************************/
     $dashBoardHelper = new DashboardHelper();
     $numerators = $dashBoardHelper->getStockOutNumerators();
          
     $denominators = $dashBoardHelper->getStockOutDenominators();     
    /******************** END NEW QUERY ************************/
//     echo '---NUmer -----<br/>';
//     var_dump($numerators);
     
//     echo '<br/><br/>---Denom----<br/>';
//     var_dump($denominators);

     
    foreach ($numerators['fp'] as $key=>$row){
        $output[] = array(
            "month" => date('F', strtotime($key)),
            "year" => date('Y', strtotime($key)),
            "implant_percent" => $numerators['larc'][$key] / $denominators['larc'][$key], // implant
            "seven_days_percent" => $row / $denominators['fp'][$key]
        );
    }
    
//    echo '<br/><br/>---Output----<br/>';
//    var_dump($output); exit;
    
    //return $output;
    return array_reverse($output, true);
    
}	
	
/*
 * Percentage facilities providing FP, LARC and Injectables in the current month
 */
//public function fetchPercentProvidingDetails($cnoWhere = null, $geoWhere = null, $dateWhere = null, $group = null, $useName = null) {
  public function fetchPercentProvidingDetails($commodity_type, &$locationNames, $where, $groupFieldName, $havingName, $geoList, $tierValue){
    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
    require_once('Facility.php');

    $output = array (); 
    $helper = new Helper2();
    $tierText = $helper->getLocationTierText($tierValue);
    //var_dump($locationNames); exit;

    if($commodity_type == 'fp')
        $tt_where = "commodity_type = 'fp'";
    else if($commodity_type == 'larc')
        $tt_where = "commodity_type = 'larc'";
    else if ($commodity_type == 'injectables')
        $tt_where = "commodity_alias = 'injectables'";

    $dateWhere = '( (MONTH(c.date)) = (SELECT MONTH(MAX(frr.date)))  AND YEAR(c.date) = (SELECT YEAR(MAX(frr.date))) )';
        
    if($where == '')
        $locationWhereClause = ' 1=1 ';
    else{
        $locationFacsList = $helper->getLocationFacilityIDs($where);
        //echo 'facs: ' . $locationFacsList; exit;
        $locationWhereClause = 'flv.id IN (' . $locationFacsList . ')';
    }
    //echo '$locationWhereClause: ' . $locationWhereClause; exit;

    $facility = new Facility();
    $totalReportingFacsCount = $facility->getAllCurrentReportingFacilityCount();

    $coverageHelper = new CoverageHelper();
    $nationalNumerator = $coverageHelper->getAllFacsProvidingCount($tt_where);
    //echo $nationalNumerator; exit;
    $coverage = $coverageHelper->getCoverageCountFacProviding($tt_where, $dateWhere, $locationWhereClause, $locationNames, $groupFieldName, $havingName, $geoList, $tierText);

    //set the national value first
    $output[] = array(
        'location' => 'National',
        'percent' => $nationalNumerator / $totalReportingFacsCount
    );

    //$percentSum = 0;
    foreach ($coverage as $location=>$numer){
        $output[] = array(
                    'location' => $location,
                    'percent' => $numer / $totalReportingFacsCount
        );

        //$percentSum += $output[count($output)-1]['percent'];
    }

    //set national ave
    //var_dump($output); exit;
    return $output;
	
}	
		
		/*
		 * TA:17:17: 01/15/2015
		 * get trained persons details
		 DB query to take number of HW trained in �LARC� in 2014
		
		 select count(distinct person_to_training.person_id) from person_to_training
		 left join training on training.id = person_to_training.training_id
		 where training.training_title_option_id=1 and training.training_end_date like '2014%';
		 */
		public function fetchTPDetails($year_amount, $where = '') {
		    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		    $output = array ();
                    $helper = new Helper2();
                    
                    //get the last DHIS2 pull date from commodity table and use the year for year here
                    $latestDate = $helper->getPreviousMonthDates(1);
                    $year = date('Y', strtotime($latestDate[0]));
                    
                    if($where == '')
                        $locationWhereClause = ' 1=1 ';
                    else{
                        $locationFacsList = $helper->getLocationFacilityIDs($where);
                        $locationWhereClause = 'f.id IN (' . $locationFacsList . ')';
                    }
                    
                    
		    for($i = $year_amount; $i > 0; $i--) {
		        $data = array ();
                        
		        $select = $db->select ()
                                ->from ( array ('ptt' => 'person_to_training' ), array ('COUNT(DISTINCT(ptt.person_id)) as count'))
                                ->joinInner(array ('t' => "training" ), 't.id = ptt.training_id' )
                                ->joinInner(array('tto' => 'training_title_option' ), 'tto.id = t.training_title_option_id AND system_training_type = \'larc\'')
                                ->joinInner(array('p' => 'person'), 'ptt.person_id = p.id')
                                ->joinInner(array('f' => 'facility'), 'f.id = p.facility_id')
                                ->where($locationWhereClause)
                                ->where("t.training_end_date like '" . $year . "%'" );
                        
                        //echo $select->__toString(); exit;
                        
		        $result = $db->fetchAll ( $select );
		        $data['tp_larc'] = $result [0] ['count'];
		
		        $select = $db->select ()
                                ->from ( array ('ptt' => 'person_to_training' ), array ('COUNT(DISTINCT(ptt.person_id)) as count'))
                                ->joinInner(array ('t' => "training" ), 't.id = ptt.training_id' )
                                ->joinInner(array('tto' => 'training_title_option' ), 'tto.id = t.training_title_option_id AND system_training_type = \'fp\'')
                                ->joinInner(array('p' => 'person'), 'ptt.person_id = p.id')
                                ->joinInner(array('f' => 'facility'), 'f.id = p.facility_id')
                                ->where($locationWhereClause)
                                ->where("t.training_end_date like '" . $year . "%'" );
                        
                        //echo $select->__toString(); exit;
                        
		        $result = $db->fetchAll ( $select );
		        $data ['tp_fp'] = $result [0] ['count'];
		
		        $output[$year] = $data;
		        $year --;
		    }
                    
		    ksort($output); //
                    
                    //echo 'before accumulation<br/>'; 
                    $count = count($output);
                    
                    //echo '<br/><br/>before accumulation<br/>'; 
                    
		    //accamulate data: add previous years to the current year
                    $start = $year + 1; //set to lowest considered year after the loop above
                    
                    foreach($output as $i => $value){
                        if($i == $start) continue;
		    	$output[$i]['tp_larc'] = $output[$i]['tp_larc'] + $output[$i-1]['tp_larc'];
		    	$output[$i]['tp_fp'] = $output[$i]['tp_fp'] + $output[$i-1]['tp_fp'];
		    }
                    
                    //var_dump($output); exit;
		    return $output;
            }
            
            
        /* TP:
         * This method gets the count of coverage of trained workers in various 
         * geo-locations and tiers. Both FP and LARC
         */
        public function fetchTTCDetails($year_amount, $where, $groupFieldName, $havingName, $geoList, $tierValue){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $output = array (); 
                $helper = new Helper2();
                
                $tierText = $helper->getLocationTierText($tierValue);
                $locationNames = $helper->getLocationNames($geoList);
                //var_dump($locationNames); exit;
                
                //get the last DHIS2 pull date from commodity table and use the year for year here
                $latestDate = $helper->getPreviousMonthDates(1);
                $year = date('Y', strtotime($latestDate[0]));

                if($where == '')
                    $locationWhereClause = ' 1=1 ';
                else{
                    $locationFacsList = $helper->getLocationFacilityIDs($where);
                    $locationWhereClause = 'f.id IN (' . $locationFacsList . ')';
                }

                $coverageHelper = new CoverageHelper();
                $larcCoverage = $coverageHelper->trainedHWCoverage('larc', $year,$year_amount, $locationWhereClause, $locationNames, $groupFieldName, $havingName, $geoList, $tierText);
                $fpCoverage = $coverageHelper->trainedHWCoverage('fp', $year,$year_amount, $locationWhereClause, $locationNames, $groupFieldName, $havingName, $geoList, $tierText);
                
                return array(
                        'location_names' => $locationNames,
                        'fp_data' => $fpCoverage,
                        'larc_data' => $larcCoverage,
                    );
            }


            
		
                //Fetches data already prepared in table dashboard_refresh for 
                //making the dashboard load faster
              public function fetchDashboardData($chart = null) {
		    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
		    $output = array();
		    /*
		    select * from dashboard_refresh
		    where chart = 'percent_facilities_hw_trained_larc'
		    and datetime = (select max(datetime) from dashboard_refresh where chart = 'percent_facilities_hw_trained_larc')
		    ;
		    */

		    $bad_chars = array("'", ",");
		    $chart = str_replace($bad_chars, "", $chart);
		    
		    $where = "chart = '$chart' and datetime = (select max(datetime) from dashboard_refresh where chart = '$chart')";
		    $subSelect = new Zend_Db_Expr("(select max(datetime) from dashboard_refresh where chart = $chart)");
		
		    $select = $db->select()
		    ->from(array('dr' => 'dashboard_refresh'),
		        array(
		            'id',
		            'datetime',
		            'chart',
		            'data0','data1','data2','data3','data4','data5','data6','data7','data8','data9',
		        ))
		            ->where($where)
		            ->order(array('id'));
		
		    $result = $db->fetchAll($select);
		    
            switch ($chart) {
                case 'percent_facilities_hw_trained_larc':
                case 'percent_facilities_hw_trained_fp':
                case 'percent_facilities_providing_larc':
                case 'percent_facilities_providing_fp':
                    
                    foreach ($result as $row) {
                        $output[] = array(
                            "state" => $row['data0'],
                            "percentage" => $row['data1'],
                            "color" => $row['data2']
                        );
                    }
                    break;
                    
                  case 'national_consumptionw92UxLIRNTl':
                  case 'national_consumptionH8A8xQ9gJ5b':
                  case 'national_consumptionibHR9NQ0bKL':
                  case 'national_consumptionDiXDJRmPwfh':
                  case 'national_consumptionyJSLjbC9Gnr':
                  case 'national_consumptionvDnxlrIQWUo':
                  case 'national_consumptionkrVqq8Vk5Kw':
                
                    foreach($result as $row){
                        $output[] = array(
                            "location" => $row['data0'],
                            "consumption" => $row['data1'],
                        );
                    }
                    break;
                
                  case 'national_average_monthly_consumptionw92UxLIRNTl':
                  case 'national_average_monthly_consumptionH8A8xQ9gJ5b':
                  case 'national_average_monthly_consumptionibHR9NQ0bKL':
                  case 'national_average_monthly_consumptionDiXDJRmPwfh':
                  case 'national_average_monthly_consumptionyJSLjbC9Gnr':
                  case 'national_average_monthly_consumptionvDnxlrIQWUo':
                  case 'national_average_monthly_consumptionkrVqq8Vk5Kw':
                
                    foreach($result as $row){
                        $output[] = array(
                            "month" => $row['data0'],
                            "consumption" => $row['data1'],
                        );
                    }
                    break;
                
                  case 'national_total_consumptionw92UxLIRNTl':
                  case 'national_total_consumptionH8A8xQ9gJ5b':
                  case 'national_total_consumptionibHR9NQ0bKL':
                  case 'national_total_consumptionDiXDJRmPwfh':
                  case 'national_total_consumptionyJSLjbC9Gnr':
                  case 'national_total_consumptionvDnxlrIQWUo':
                  case 'national_total_consumptionkrVqq8Vk5Kw':
                
                    foreach($result as $row){
                        $output[] = array(
                            "location" => $row['data0'],
                            "consumption" => $row['data1'],
                        );
                    }
                    break;
                    
                case 'average_monthly_consumption':
                
                    foreach ($result as $row) {
                        $output[] = array(
                            "month" => $row['data0'],
                            "injectable_consumption" => $row['data1'],
                            "implant_consumption" => $row['data2']
                        );
                    }
                    break;
                    
                case 'national_consumption_by_method':
                    
                     foreach ($result as $row) {
                         $output[] = array(
                             "method" => $row['data0'],
                             "consumption" => $row['data1']
                         );
                     }
                     break;
                     
                case 'national_percent_facilities_providing':
                
                    foreach ($result as $row) {
                        $output[] = array(
                            "month" => $row['data0'],
                            "year" => $row['data1'],
                            "fp_percent" => $row['data2'],
                            "larc_percent" => $row['data3']
                        );
                    }
                    break;
                    
                case 'national_percent_facilities_stock_out':
                
                    foreach ($result as $row) {
                        $output[] = array(
                            "month" => $row['data0'],
                            "year" => $row['data1'],
                            "implant_percent" => $row['data2'],
                            "seven_days_percent" => $row['data3']
                        );
                    }
                    break;
                    
                case 'national_average_monthly_consumption_all':
                
                    foreach ($result as $row) {
                        $output[] = array(
                            "month" => $row['data0'],
                            "consumption1" => $row['data1'],
                            "consumption2" => $row['data2'],
                            "consumption3" => $row['data3'],
                            "consumption4" => $row['data4'],
                            "consumption5" => $row['data5'],
                            "consumption6" => $row['data6'],
                            "consumption7" => $row['data7'],
                            
                        );
                    }
                    break;
                case 'national_coverage_summary':
                   
                    if (count($result == 1)) {
                            $output["last_date"] =                       $result[0]['data0'];
                            $output["total_facility_count"] =            $result[0]['data1'];
                            $output["total_facility_count_month"] =      $result[0]['data2'];
                            $output["larc_facility_count"] =             $result[0]['data3'];
                            $output["fp_facility_count"] =               $result[0]['data4'];
                            $output["larc_consumption_facility_count"] = $result[0]['data5'];
                            $output["fp_consumption_facility_count"] =   $result[0]['data6'];
                            $output["larc_stock_out_facility_count"] =   $result[0]['data7'];
                            $output["fp_stock_out_facility_count"] =     $result[0]['data8'];
                   }
                   break;
                case 'PercentFacHWTrainedStockOutLarc':
                case 'PercentFacHWTrainedStockOutFP':
                     
                    foreach ($result as $row) {
                        $output[] = array(
                            "location" => $row['data0'],
                            "percent" => $row['data1'],
                            "color" => $row['data2'],
                            
                        );
                    }
                    break; 
                 case 'PercentFacHWProvidingStockOutLarc':
                 case 'PercentFacHWProvidingStockOutFP':
                      
                     foreach ($result as $row) {
                         $output[] = array(
                             "location" => $row['data0'],
                             "percent" => $row['data1'],
                             "color" => $row['data2'],
                 
                         );
                     }
                     break;
                  case 'PercentFacHWTrainedProvidingLarc':
                  case 'PercentFacHWTrainedProvidingFP':
                  
                      foreach ($result as $row) {
                          $output[] = array(
                              "location" => $row['data0'],
                              "percent" => $row['data1'],
                              "color" => $row['data2'],
                               
                          );
                      }
                      break;
                  case 'national_larc_coverage':
                  case 'national_fp_coverage':
                  
                      foreach ($result as $row) {
                          $output[] = array(
                              "month" => $row['data0'],
                              "year" => $row['data1'],
                              "tt_percent" => $row['data2'],
                              "tso_percent" => $row['data3'],
                              "tp_percent" => $row['data4'],
                               
                          );
                      }
                      break;
                  
                  case 'national_percent_facilities_providing_larc':
                  case 'national_percent_facilities_providing_fp':
                  case 'national_percent_facilities_providing_inject':
                  
                      foreach ($result as $row) {
                          $output[] = array(
                              "location" => $row['data0'],
                              "percent" => $row['data1'],
                              "color" => $row['data2'],
                          );
                      }
                      break;
                  
            }
            
            
		    //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI 243 >'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
		    //var_dump($output,"END");
		    //var_dump('id=', $id);
		    //$result = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $result .PHP_EOL, FILE_APPEND | LOCK_EX);
		
		    return $output;
		}
	
		public function insertDashboardData($details, $chart) {
		    //current_datetime = now()
		    //INSERT INTO `itechweb_chainigeria`.`dashboard_refresh`
		    //( `datetime`, `chart`, `data0`, `data1`, `data2`)
		    //VALUES (current_datetime, 'percent_facilities_hw_trained_larc', 'Plateau', '0.0025', 'red');
		    
		    /*save
		    $sfm = new ITechTable(array('name' => 'subpartner_to_funder_to_mechanism'));
		     
		    $data = array(
		        'subpartner_id'  => $params['subPartner'],
		        'partner_funder_option_id' => $params['partnerFunder'],
		        'mechanism_option_id' => $params['mechanism'],
		        'funding_end_date' => $params['funding_end_date'][0],
		    );
		    
		    $insert_result = $sfm->insert($data);
		    */
		    
		    $bad_chars = array("'", ",");
		    $chart = str_replace($bad_chars, "", $chart);
		    
		    $dateTime = date("Y-m-d H:i:s");
		    $dashboard_refresh = new ITechTable(array('name' => 'dashboard_refresh'));
		    
		    switch ($chart) {
		        case 'percent_facilities_hw_trained_larc':
		        case 'percent_facilities_hw_trained_fp':
		        case 'percent_facilities_providing_larc':
		        case 'percent_facilities_providing_fp':
		  		    
        		    foreach($details as $row){
        		        $data = array(
        		            'datetime'  => $dateTime,
        		            'chart'  => $chart,
        		            'data0'  => $row['state'],
        		            'data1'  => $row['percentage'],
        		            'data2'  => $row['color'],
        		        );
        		        
        		        $insert_result = $dashboard_refresh->insert($data);
        		    }
		          break;
		          
                  case 'national_consumptionw92UxLIRNTl':
                  case 'national_consumptionH8A8xQ9gJ5b':
                  case 'national_consumptionibHR9NQ0bKL':
                  case 'national_consumptionDiXDJRmPwfh':
                  case 'national_consumptionyJSLjbC9Gnr':
                  case 'national_consumptionvDnxlrIQWUo':
                  case 'national_consumptionkrVqq8Vk5Kw':
                      
		              foreach($details as $row){
		                  if (is_null($row['consumption'])) { $row['consumption'] = ''; }  // special case that can be null
		                  $data = array(
		                      'datetime'  => $dateTime,
		                      'chart'  => $chart,
		                      'data0'  => $row['location'],
		                      'data1'  => $row['consumption'],
		                  );
		          
		                  $insert_result = $dashboard_refresh->insert($data);
		              }
		              break;
		              
                  case 'national_average_monthly_consumptionw92UxLIRNTl':
                  case 'national_average_monthly_consumptionH8A8xQ9gJ5b':
                  case 'national_average_monthly_consumptionibHR9NQ0bKL':
                  case 'national_average_monthly_consumptionDiXDJRmPwfh':
                  case 'national_average_monthly_consumptionyJSLjbC9Gnr':
                  case 'national_average_monthly_consumptionvDnxlrIQWUo':
                  case 'national_average_monthly_consumptionkrVqq8Vk5Kw':
		          
		              foreach($details as $row){
		                  $data = array(
		                      'datetime'  => $dateTime,
		                      'chart'  => $chart,
		                      'data0'  => $row['month'],
		                      'data1'  => $row['consumption'],
		                  );
		          
		                  $insert_result = $dashboard_refresh->insert($data);
		              }
		              break;
		              
                  case 'national_total_consumptionw92UxLIRNTl':
                  case 'national_total_consumptionH8A8xQ9gJ5b':
                  case 'national_total_consumptionibHR9NQ0bKL':
                  case 'national_total_consumptionDiXDJRmPwfh':
                  case 'national_total_consumptionyJSLjbC9Gnr':
                  case 'national_total_consumptionvDnxlrIQWUo':
                  case 'national_total_consumptionkrVqq8Vk5Kw':
		          
		              foreach($details as $row){
		                  $data = array(
		                      'datetime'  => $dateTime,
		                      'chart'  => $chart,
		                      'data0'  => $row['location'],
		                      'data1'  => $row['consumption'],
		                  );
		          
		                  $insert_result = $dashboard_refresh->insert($data);
		              }
		              break;
		          
		        case 'average_monthly_consumption':
		            
		            foreach($details as $row){
		                $data = array(
		                    'datetime'  => $dateTime,
		                    'chart'  => $chart,
		                    'data0'  => $row['month'],
		                    'data1'  => $row['injectable_consumption'],
		                    'data2'  => $row['implant_consumption'],
		                );
		            
		                $insert_result = $dashboard_refresh->insert($data);
		            }
		            break;
		            
		        case 'national_consumption_by_method':
		            
		            foreach($details as $row){
		                if (is_null($row['consumption'])) { $row['consumption'] = ''; }  // special case that can be null
		                $data = array(
		                    'datetime'  => $dateTime,
		                    'chart'  => $chart,
		                    'data0'  => $row['method'],
		                    'data1'  => $row['consumption'],
		                );
		        
		                $insert_result = $dashboard_refresh->insert($data);
		            }
		            break;
		            
		        case 'national_percent_facilities_providing':
		        
		            foreach($details as $row){
		                $data = array(
		                    'datetime'  => $dateTime,
		                    'chart'  => $chart,
		                    'data0'  => $row['month'],
		                    'data1'  => $row['year'],
		                    'data2'  => $row['fp_percent'],
		                    'data3'  => $row['larc_percent'],
		                );
		        
		                $insert_result = $dashboard_refresh->insert($data);
		            }
		            break;
		        case 'national_percent_facilities_stock_out':
		        
		            foreach($details as $row){
		                $data = array(
		                    'datetime'  => $dateTime,
		                    'chart'  => $chart,
		                    'data0'  => $row['month'],
		                    'data1'  => $row['year'],
		                    'data2'  => $row['implant_percent'],
		                    'data3'  => $row['seven_days_percent'],
		                );
		            
		                $insert_result = $dashboard_refresh->insert($data);
		            }
		            break;
		            
		        case 'national_average_monthly_consumption_all':
		        
		            foreach($details as $row){
		                $data = array(
		                    'datetime'  => $dateTime,
		                    'chart'  => $chart,
		                    'data0'  => $row['month'],
		                    'data1'  => $row['consumption1'],
		                    'data2'  => $row['consumption2'],
		                    'data3'  => $row['consumption3'],
		                    'data4'  => $row['consumption4'],
		                    'data5'  => $row['consumption5'],
		                    'data6'  => $row['consumption6'],
		                    'data7'  => $row['consumption7'],
		                    
		                );
		        
		                $insert_result = $dashboard_refresh->insert($data);
		            }
		            break;
		            
		        case 'national_coverage_summary':
		        
		                $data = array(
		                    'datetime'  => $dateTime,
		                    'chart'  => $chart,
		                    'data0'  => $details['last_date'],
		                    'data1'  => $details['total_facility_count'],
		                    'data2'  => $details['total_facility_count_month'],
		                    'data3'  => $details['larc_facility_count'],
		                    'data4'  => $details['fp_facility_count'],
		                    'data5'  => $details['larc_consumption_facility_count'],
		                    'data6'  => $details['fp_consumption_facility_count'],
		                    'data7'  => $details['larc_stock_out_facility_count'],
		                    'data8'  => $details['fp_stock_out_facility_count'],
		                );
		        
		                $insert_result = $dashboard_refresh->insert($data);
		            break;
		            
		        case 'PercentFacHWTrainedStockOutLarc':
		        case 'PercentFacHWTrainedStockOutFP':
		        
		            foreach($details as $row){
		                $data = array(
		                    'datetime'  => $dateTime,
		                    'chart'  => $chart,
		                    'data0'  => $row['location'],
		                    'data1'  => $row['percent'],
		                    'data2'  => $row['color'],
		        
		                );
		        
		                $insert_result = $dashboard_refresh->insert($data);
		            }
		            break;
		            
		         case 'PercentFacHWProvidingStockOutLarc':
		         case 'PercentFacHWProvidingStockOutFP':
		         
		             foreach($details as $row){
		                 $data = array(
		                     'datetime'  => $dateTime,
		                     'chart'  => $chart,
		                     'data0'  => $row['location'],
		                     'data1'  => $row['percent'],
		                     'data2'  => $row['color'],
		         
		                 );
		         
		                 $insert_result = $dashboard_refresh->insert($data);
		             }
		             break;
		             
		          case 'PercentFacHWTrainedProvidingLarc':
		          case 'PercentFacHWTrainedProvidingFP':
		               
		              foreach($details as $row){
		                  $data = array(
		                      'datetime'  => $dateTime,
		                      'chart'  => $chart,
		                      'data0'  => $row['location'],
		                      'data1'  => $row['percent'],
		                      'data2'  => $row['color'],
		                       
		                  );
		                   
		                  $insert_result = $dashboard_refresh->insert($data);
		              }
		              break;
		              
		              case 'national_larc_coverage':
		              case 'national_fp_coverage':
		                   
		                  foreach($details as $row){
		                      if (is_null($row['tt_percent'])) { $row['tt_percent'] = ''; }  // special case that can be null 
		                      $data = array(
		                          'datetime'  => $dateTime,
		                          'chart'  => $chart,
		                          'data0'  => $row['month'],
		                          'data1'  => $row['year'],
		                          'data2'  => $row['tt_percent'],
		                          'data3'  => $row['tso_percent'],
		                          'data4'  => $row['tp_percent'],
		                           
		                      );
		                       
		                      $insert_result = $dashboard_refresh->insert($data);
		                  }
		                  break;
		              
		              case 'national_percent_facilities_providing_larc':
		              case 'national_percent_facilities_providing_fp':
		              case 'national_percent_facilities_providing_inject':
		                   
		                  foreach($details as $row){
		                      if (is_null($row['tt_percent'])) { $row['tt_percent'] = ''; }  // special case that can be null
		                      $data = array(
		                          'datetime'  => $dateTime,
		                          'chart'  => $chart,
		                          'data0'  => $row['location'],
		                          'data1'  => $row['percent'],
		                          'data2'  => $row['color'],
		                      );
		                       
		                      $insert_result = $dashboard_refresh->insert($data);
		                  }
		                  break;

		    }
		    
		    //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI 697>'.PHP_EOL, FILE_APPEND | LOCK_EX);	ob_start();
		    //var_dump('id=', $id);
		    //var_dump('dateTime=', $dateTime);
		    //$result = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $result .PHP_EOL, FILE_APPEND | LOCK_EX);
		    
		}



public function fetchCMDetails($where = null, $group = null, $useName = null) {

    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    $output = array();


    //file_put_contents('c:\wamp\logs\php_debug.log', 'Dashboard-CHAI 171>'.PHP_EOL, FILE_APPEND | LOCK_EX);ob_start();
    //var_dump('all=', $where, $group, $useName, "END");
    //$toss = ob_get_clean(); file_put_contents('c:\wamp\logs\php_debug.log', $toss .PHP_EOL, FILE_APPEND | LOCK_EX);

    $create_view = $db->select()
    ->from(array('f' => 'facility'),
        array(
            'f.id as F_id',
            'f.facility_name as F_facility_name',
            //"replace(f.facility_name, '\'', '\\\'') as F_facility_name',",
            'f.location_id as F_location_id',
            'l1.id as L1_id',
            'l1.location_name as L1_location_name',
            'l2.id as L2_id',
            'l2.location_name as L2_location_name',
            'l2.parent_id as L2_parent_id',
            'l3.location_name as L3_location_name',
            'cno.id as CNO_id',
            'cno.commodity_name as CNO_commodity_name',
            'c.date as C_date',
            'ifnull(sum(c.consumption),0) as C_consumption' ))
         	->joinLeft(array('l1' => "location"), 'f.location_id = l1.id')
    	    ->joinLeft(array('l2' => "location"), 'l1.parent_id = l2.id')
    	    ->joinLeft(array('l3' => "location"), 'l2.parent_id = l3.id')
    	    ->joinLeft(array("c" => "commodity"), 'f.id = c.facility_id')
    	    ->joinLeft(array("cno" => "commodity_name_option"), 'c.name_id = cno.id')
    	    ->joinInner(array('mc' => 'lc_view_subselect'), 'f.id = mc.facility_id and month(c.date) = C_monthDate')
    	    ->where($where)
    	    ->group(array($group))
    	    ->order(array('CNO_id'));
    
    $sql = $create_view->__toString();
    $sql = str_replace('`C_consumption`,', '`C_consumption`', $sql);
    $sql = str_replace('`l1`.*,', '', $sql);
    $sql = str_replace('`l2`.*,', '', $sql);
    $sql = str_replace('`l3`.*,', '', $sql);
    $sql = str_replace('`c`.*,', '', $sql);
    $sql = str_replace('`cno`.*,', '', $sql);
    $sql = str_replace('`mc`.*', '', $sql);
    
    try{
        $sql='create or replace view lc_view as ('.$sql.')';
        $db->fetchOne( $sql );
    }
    catch (Exception $e) {
        //echo $e->getMessage();
        //var_dump('error', $e->getMessage());
        
    }

    $select = $db->select()
    ->from(array('cv' => 'lc_view'),
        array(
            'CNO_commodity_name',
            'ifnull(C_consumption,0) as C_consumption' ))
    	->order(array('CNO_commodity_name'));
    
    $result = $db->fetchAll($select);
    
    foreach ($result as $row){
    
        $output[] = array(
            "CNO_commodity_name" => $row['CNO_commodity_name'],
            "C_consumption" => $row['C_consumption'],
        );
    }
    
    return $output;
}

}


?>
