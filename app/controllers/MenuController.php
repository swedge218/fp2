<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once ('ReportFilterHelpers.php');
require_once ('models/table/OptionList.php');
//require_once('models/table/Course.php');
require_once ('models/table/Facility.php');
require_once ('views/helpers/CheckBoxes.php');
require_once ('models/table/MultiAssignList.php');
require_once ('models/table/TrainingTitleOption.php');
require_once ('models/table/Helper.php');
require_once ('models/table/Helper2.php');

class MenuController extends ReportFilterHelpers {

	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array()) {
		parent::__construct ( $request, $response, $invokeArgs );
	}

	public function init() {
	}

	public function indexAction() {
$this->_forward ( 'info' );
	}

	public function preDispatch() {
		$rtn = parent::preDispatch ();
		$allowActions = array ('info' );

		if (! $this->isLoggedIn ())
		$this->doNoAccessError ();

		
		return $rtn;
	}

	public function dataAction() { 	}
        public function rrateAction(){
             $this->_countrySettings = array();
		$this->_countrySettings = System::getAll();
                $facility = new Facility();
                $helper = new Helper2();
		$this->view->assign ( 'mode', 'search' );
                require_once ('models/table/TrainingLocation.php');
		require_once('views/helpers/TrainingViewHelper.php');
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();

$current_month = "03";
$current_year = date('Y');
//$date_format = $current_year.'-'.$current_month.'-'.'01';
$date_format = $helper->getLatestPullDate();
$time = strtotime($date_format);
$month = date('F',$time);
$year = date('Y',$time);
$format = "for $month, $year";
$allFacilitiesNational = $this->get_all_facilities_with_location("","");
$facility_idsNational = implode(",",$allFacilitiesNational);
$reportRatesNational = $this->get_all_facilities_reporte_rates($facility_idsNational,$date_format);
$totalFacilities = sizeof($allFacilitiesNational);
$reportRateNationalPercent = round(($reportRatesNational/$totalFacilities) * 100,2);
  echo '<h1 align="left" style="color:green;margin-left:60px;width:50%;"><span style="width:70%;float:left;">National Reporting Rate:</span><span style="float:right;width:29%;">'.$reportRateNationalPercent.'% </span></h1>';

echo '<div id="accordion">';

$zones = $this->get_location_category_unique("zone");
//print_r($zones);exit;
foreach($zones as $zone){
    $zone_name = $zone['geo_zone'];
    $zone_id = $zone['geo_parent_id'];
   
    $facilities = $this->get_all_facilities_with_location("zone",$zone_id);
    $facility_ids = implode(",",$facilities);
    
   $report_rates = $this->get_all_facilities_reporte_rates($facility_ids,$date_format);
   $totalFacilities = sizeof($facilities);
    $reportRateZonePercent = round(($report_rates/$totalFacilities) * 100,2);
  echo ' <h3 align=""><span class="tableft">'.$zone_name.':</span><span class="tabright">'.$reportRateZonePercent.'%</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h3>
<div class="accordion1">';
  //  echo '<li><a href=#"><span></span>'.$zone_name.'::'.$report_rates.'</a>';
    $states = $this->get_location_category_unique("state",$zone_id);
   // echo '<ul>';
    foreach($states as $state){
        $state_name = $state['state'];
        $state_id = $state['state_id'];
        $facilities = $this->get_all_facilities_with_location("state",$state_id);
    $facility_ids = implode(",",$facilities);
    $report_rates_state = $this->get_all_facilities_reporte_rates($facility_ids,$date_format);
    $totalFacilities = sizeof($facilities);
    $reportRateStatePercent = round(($report_rates_state/$totalFacilities) * 100,2);
    echo '<h3 align=""><span class="tabstateleft" >'.$state_name.':</span></span><span class="tabright">'.$reportRateStatePercent.'%</span></h3>
   
';
        //echo '<li><a href="#"><span></span>'.$state_name.'::'.$report_rates_state.'</a>';
       // echo '<ul>';
    
   echo '<div>';
    $lgas = $this->get_location_category_unique("lgs",$state_id);
    foreach($lgas as $lga){
        $lga_name = $lga['lga'];
        $lga_id = $lga['lga_id'];
       $facilities = $this->get_all_facilities_with_location("lga",$lga_id);
       $totalFacilities = sizeof($facilities);
    $facility_ids = implode(",",$facilities);
    $report_rates_lga = $this->get_all_facilities_reporte_rates($facility_ids,$date_format);
    $reportRateLgaPercent = round(($report_rates_lga/$totalFacilities) * 100,2);
        echo '<p><span class="tableft">'.$lga_name.':</span><span class="tabright">'.$reportRateLgaPercent.'%</span></p>'; 
        
    }
  echo '</div>';
      
    }
    echo '</div>';
    
}


echo '</div>';
           
$this->view->assign('date_format',$format);
        }
        public function importAction(){
            $this->_countrySettings = array();
		$this->_countrySettings = System::getAll();
if ( $this->getSanParam('download') )
			return $this->download();
		
		$this->view->assign ( 'mode', 'search' );
                require_once ('models/table/TrainingLocation.php');
		require_once('views/helpers/TrainingViewHelper.php');
                
                 $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                 $status = ValidationContainer::instance();
                 $filename = "";
                 $stat = "";
                 if(isset($_FILES['upload']['tmp_name'])){
            $filename = ($_FILES['upload']['tmp_name']);
                 }
		if ( $filename!="" ){
			$rows = $this->_excel_parser($filename,1);
                       
                        $json = json_encode($rows);
                        
                       // $result = true;
                       // print_r($json);
   
  //$result = true;
                        
                       $result =  $this->update_json_file($json);
                       if($result){
                         $stat = t ('Your changes have been saved. The new DHS Data has been uploaded to the database');
                       }else{
                          $stat =  t("Unable to update DHS database. Make sure file content is not the same as the one already in the database"); 
                       }
                }else{
                $errs[] = "Select the DHS Static Excel File";
                }
                
                foreach($errs as $errmsg){
								$stat .= '<br>'.''.htmlspecialchars($errmsg, ENT_QUOTES);
        }                                                          
								$status->setStatusMessage($stat);
								$this->view->assign('status',$status);
                
                
        }
public function infoAction(){
    $this->_countrySettings = array();
		$this->_countrySettings = System::getAll();

		$this->view->assign ( 'mode', 'search' );
                require_once ('models/table/TrainingLocation.php');
		require_once('views/helpers/TrainingViewHelper.php');

                $file = $this->get_json_file_from_db();
                $json_array = json_decode($file,true);
            //starting from index 5
                $this->view->assign('json_array',$json_array);
}

public function update_json_file($json){
    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
   $data = array(
    'json'      => $json
  
);
 
   
$result = $db->update('dhs_static_data', $data, 'id = 1');
return $result;
}
public function get_json_file_from_db(){
    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
 $sql = "SELECT * FROM `dhs_static_data` WHERE id='1'";
 $result = $db->fetchAll($sql);
 $file = $result[0]['json'];
return $file;
}
public function get_location_category_unique($category,$condition=""){
      if($category=="zone"){
        $needle = "geo_parent_id,geo_zone";
        $condi = "";
        $name = "geo_zone";
    }else if($category=="state"){
        $needle = "state_id,state";
        $name = "state";
        $condi = "WHERE geo_parent_id='$condition'";
    }else{
        $needle = "lga_id,lga";
        $name = "lga";
        $condi = "WHERE state_id='$condition'";
    }
    
    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
    $sql = "SELECT DISTINCT  ".$needle." FROM facility_location_view ".$condi."  ORDER BY `$name` ASC";
  // echo $sql;exit;
    $result = $db->fetchAll($sql);
    return $result;
    
}

public function get_all_facilities_reporte_rates($facility_ids,$date_format){
    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
    $sql = "SELECT COUNT(*) as counter FROM facility_report_rate WHERE facility_id IN (".$facility_ids.") AND date='$date_format'";
    
    $result = $db->fetchAll($sql);
    //print_r($result);exit;
    return $result[0]['counter'];
    
}
public function get_all_facilities_with_location($category,$id){
    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
    if($category=="zone"){
        $needle = "geo_parent_id";
        $whereClause = "WHERE `$needle`='$id'";
    }else if($category=="state"){
        $needle = "state_id";
        $whereClause = "WHERE `$needle`='$id'";
    }else if($category=="lga"){
        $needle = "lga_id";
        $whereClause = "WHERE `$needle`='$id'";
    }
    else{
       $whereClause = "";
    }
    
    $sql  = "SELECT id FROM facility_location_view ".$whereClause."";
    $result = $db->fetchAll($sql);
    $facilities = array();
    foreach($result as $facility){
        $facility_id = $facility['id'];
        $facilities[] = $facility_id;
    }
    return $facilities;
}
public function download(){
    header('Content-Type: application/application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 			header('Content-Disposition: attachment; filename="Dashboard_static_page_indicators.xlsx"');
 			header("Content-Type: application/force-download");
 			readfile(Globals::$BASE_PATH . '/html/templates/Dashboard_static_page_indicators.xlsx');
  			$this->view->layout()->disableLayout();
         	$this->_helper->viewRenderer->setNoRender(true);
}

public function definitionsPageAction(){
    
}
}
?>
