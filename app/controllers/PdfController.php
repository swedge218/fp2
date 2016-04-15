<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PDFController
 *
 * @author Swedge
 */
require_once ('Zend/Validate/EmailAddress.php');
require_once ('Zend/Mail.php');
require_once ('ReportFilterHelpers.php');
require_once 'dompdf/dompdf_config.inc.php';
require_once('models/table/PDF.php');
require_once('models/table/Helper2.php');
require_once('models/table/Coverage.php');
require_once('models/table/Stockout.php');
require_once('models/table/Consumption.php');
require_once('models/table/Dashboard.php');

require_once ('models/table/OptionList.php');

require_once ('views/helpers/CheckBoxes.php');
require_once ('models/table/MultiAssignList.php');
require_once ('models/table/TrainingTitleOption.php');
require_once ('models/table/Helper.php');
require_once ('models/table/Report.php');
require_once ('models/table/User.php');

class PdfController extends ReportFilterHelpers {
    //put your code here
    public function preDispatch() {
            parent::preDispatch ();

            if (!$this->isLoggedIn ())
                    $this->doNoAccessError ();
               
            //if (! $this->setting('module_employee_enabled')){
                    //$_SESSION['status'] = t('The employee module is not enabled on this site.');
                    //$this->_redirect('select/select');
            //}

            //if (! $this->hasACL ( 'employees_module' )) {
                    //$this->doNoAccessError ();
            //}
    }

   public  function lgachartsAction(){
        $pdf  = new PDF();
         if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->isPost()) {

           }
        }
        else {
            //echo 'before'; exit;
               //first ensure that all the locations have been registered
            
                $pdf->insertLocationIds();
/*
          $pdf->update_database("2015-05-01", "2015-05-31","0");
          exit;*/
          
    
               //get the next unflagged (with 0 for the file_generated value) location
                $reportLocation = array();
                //$reportLocation = $pdf->getNextLocationDetails();
                //print_r($reportLocation);exit;
               
                 /*TP
                * the getNextlocationDetailssWithTiers method has two optional argument which are
                *  $tierValue and $location_id respectively.
                 1978-abj municipal, 1995-bwari*/
                $reportLocation = $pdf->getNextLocationDetailsWithTiers(3,"","0");
               
                //
                //$reportLocation['folder_name'] = $folder_mode;
                //var_dump($reportLocation); 
               if($reportLocation['tier']!=3){
                   $message = "All Charts for this month state category has been generated earlier";
               }else{
                   //echo 'helloo';exit;
                   $this->createLgaCharts($reportLocation);
               }
                //call the handler for national or state or lga
               
                
        }
    }
    
    public function createLgaCharts($reportLocation){
        $pdf = new PDF();
        $coverage = new Coverage();
        $cons = new Consumption();
        $helper = new Helper2();
        $stockout = new Stockout();
        $lastPullDate = $helper->getLatestPullDate();
        //echo $lastPullDate;exit;
        $date = substr($lastPullDate, 0,-3);
        $folder_mode = $date." LGA";
        $folder_name = str_replace(" ", "_", $folder_mode);
        mkdir("pdfrepo/lga/".$folder_name."");
         //TP: passing the report id and the location id of the current location into variables
        $location_id = $reportLocation['location_id'];
        $report_id = $reportLocation['report_id'];
        
        $parent_id = $helper->getParentID($location_id);  //TP: getting the parent id of the location id to be used further in the code
        $state_names = $helper->getLocationNames($parent_id); //getting the name (state name) of the parent id gotten
      // echo $folder_name;exit;
        $lga_names = $helper->getLocationNames($location_id);
        
        $state_name = $state_names[$parent_id]." State";
        $lga_name = $lga_names[$location_id];
        //print_r($lga_names);exit;
        
       
        
        //TP: Facilities with FP trained HW that are not providing
        $fp_trained_not_providing = $pdf->fetchfacsWithHWNotProviding("fp", "fp", $parent_id, 2);
        
        $percent_fp_trained_not_providing_for_this_state_value = round($fp_trained_not_providing[0] * 100,1);
      
        
        $facilities_fptrained_not_providing = $pdf->fetchFacilitiesWithHWNotProviding("fp","fp",$location_id,3);
        //print_r($percent_fp_trained_not_providing_for_this_state_value);
        
        
        //TP: Facilities with LARC trained HW that are not providing
        $larc_trained_not_providing = $pdf->fetchfacsWithHWNotProviding("larc", "larc", $parent_id, 2);
        $percent_larc_trained_not_providing_for_this_state_value = round($larc_trained_not_providing[0] * 100,1);
      
        
        $facilities_larctrained_not_providing = $pdf->fetchFacilitiesWithHWNotProviding("larc","larc",$location_id,3);
        //print_r($percent_larc_trained_not_providing_for_this_state_value);exit;
        
        /*TP:
         * FACILITY PROVIDING FP
         * ------------------------------------------------------------------------------------------------------------
         */
        
      
        
        //TP:Facilities Stocked Out of Fp
        $state_stocked_out_fp = $stockout->fetchPercentFacsProvidingButStockedOut("fp",$parent_id,2,false,true);
       // print_r($state_stocked_out_fp);exit;
        $state_stock_out_fp_seven_days = round($state_stocked_out_fp[1]['percent']*100,1);
        $lga_stock_out_seven_days = $stockout->fetchPercentFacsProvidingButStockedOut("fp",$location_id,3,false,true);
        $lga_stocked_out_fp_seven_days = round($lga_stock_out_seven_days[1]['percent']*100,1);
        $fp_stock_out_for_seven_days = array($state_stock_out_fp_seven_days,$lga_stocked_out_fp_seven_days);
        //print_r($fp_stock_out_for_seven_days);exit;
       
        
       
        $facilities_stocked_out_fp_seven_days = $stockout->fetchFacilitiesProvidingButStockedOut("fp",$location_id,3);
        //echo 'this is where we are stocked';
        
        //TP: Facilities stocked of implants
        $state_stocked_out_larc = $stockout->fetchPercentFacsProvidingButStockedOut("larc",$parent_id,2,false,true);
        $state_stock_out_larc = round($state_stocked_out_larc[1]['percent']*100,1);
        $facilities_stocked_out_larc = $stockout->fetchFacilitiesProvidingButStockedOut("larc",$location_id,3);
         
         
        
        //TP: fetching the percent of facility providing fp for the state (parent id)
      $state_fp_facility_prov = $pdf->fetchPercentFacsProvidingPerLocation("fp","2", $parent_id);
        $fp_array = array();
         foreach($state_fp_facility_prov as $data){
               if($parent_id==$data['location_id']){
                   $fp_array[0] = $data;               
               }
           }
          
        //TP: fetching the percent of facility providing fp for the local government (location_id)   
        $lga_fp_facility_prov = $pdf->fetchPercentFacsProvidingPerLocation("fp","3", $location_id);
       // $fp_array = array();
         foreach($lga_fp_facility_prov as $data){
               if($location_id==$data['location_id']){
                   array_push($fp_array,$data);              
               }
           }   
           
           
           //print_r($fp_array);echo '<br/><br/>';
           
           
          /*TP:
         * FP TRAINED HW
         * ------------------------------------------------------------------------------------------------------------
         */
           //TP for percentage of fp trained HW for single state and national
       $state_fp_trained_in = $pdf->fetchPercentFacHWTrainedPerState("fp",2,$parent_id);
       $fp_trained_array = array();
        
        foreach($state_fp_trained_in as $data){
           
           if($parent_id==$data['location_id']){
            $fp_trained_array[0] = $data;
              continue;
               
           }
       } 
           
           //TP: fetching the percent of fp trained HW for the local government (location_id)  
       $lga_fp_trained_in = $pdf->fetchPercentFacHWTrainedPerState("fp",3,$location_id);
      foreach($lga_fp_trained_in as $data){
           
           if($location_id==$data['location_id']){
            array_push($fp_trained_array,$data);
            
              continue;
               
           }
       } 
           
          // print_r($fp_trained_array);echo '<br/><br/>';
        
           
           
     
       //print_r($stock_out_fp_seven_days);//exit;
       
       
        //TP: consumption overtime chart gotten from the database  
        $lastPullDate = $helper->getLatestPullDate();
        $time = strtotime($lastPullDate);
        $injectables_id = 15;
        $implants_id = 18;
       
        //TP:: getting the consumption overtime data into two variables using list
        list($methodName,$consBySingleCommodityAndLocation) = $cons->fetchAllConsumptionBySingleLocationOverTime($location_id, "3");
        //$consumption_monthly_overtime_implants = $cons->fetchConsumptionByCommodityAndLocationOverTime($injectables_id, $location_id, "2");
  
        
          $this->view->assign('report_id',$report_id);
        $this->view->assign('consumption_by_method',$methodName);
        $this->view->assign('consumption_overtime',$consBySingleCommodityAndLocation); 
        $this->view->assign('consumption_overtime_lga',$consBySingleCommodityAndLocation);
        $this->view->assign('facility_providing_lga_state',$fp_array);
        $this->view->assign('facility_with_hw_trained_in_fp',$fp_trained_array);
        $this->view->assign('fp_stock_out_for_seven_days',$fp_stock_out_for_seven_days);
        $this->view->assign('state_name',$state_name);
        $this->view->assign('lga_name',$lga_name);
        $this->view->assign('folder_name',$folder_name);
        $this->view->assign('percent_facilities_with_fphw_not_prov_fp',$percent_fp_trained_not_providing_for_this_state_value);
        $this->view->assign('facilities_with_fp_trained_hw_not_provfp',$facilities_fptrained_not_providing);
        $this->view->assign('percent_larc_trained_not_providing_for_this_lga_value',$percent_larc_trained_not_providing_for_this_state_value);
        $this->view->assign('facilities_larctrained_not_providing',$facilities_larctrained_not_providing);
        $this->view->assign('percent_state_stock_out_fp_seven_days',$state_stock_out_fp_seven_days);
        $this->view->assign('facilities_stocked_out_fp_seven_days',$facilities_stocked_out_fp_seven_days);
        $this->view->assign('percent_state_stock_out_larc',$state_stock_out_larc);
        $this->view->assign('facilities_stocked_out_larc',$facilities_stocked_out_larc);
        
        
    }
    public function statechartsAction(){
        $pdf = new PDF();
        //echo 'trying';
        //$this->_helper->layout()->disableLayout();
        //$this->_helper->viewRenderer->setNoRender(true);
        //exit;
        /*
          $pdf->update_database("2015-09-01", "2015-09-31","2");
          exit;
        */
         
        if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->isPost()) {

           }
        }
        else {
               //echo 'before'; exit;
               //first ensure that all the locations have been registered
                $pdf->insertLocationIds();

               //get the next unflagged (with 0 for the file_generated value) location
                
                /*TP
                * the getNextlocationDetailssWithTiers method has two optional argument which are
                *  $tierValue and $location_id respectively.
                 970 -abj, 969-lagos  956-oyo state, 954-ondo state,963-kaduna state*/
                $reportLocation = $pdf->getNextLocationDetailsWithTiers(2,"","0");
                //print_r($reportLocation);
                //exit;
                                

               if($reportLocation['tier']!=2){
                   $message = "All Charts for this month state category has been generated earlier";
               }else{
                   //echo 'helloo';exit;
                   $this->createstatecharts($reportLocation);
               }
                //call the handler for national or state or lga
               
                
                
           }
    }
    
    public function createstatecharts($reportLocation){
        //TP: initiating an objects for the classes we are going to need
        //sleep(5);
       // $html = file_get_contents("statepdftemp.php");
//echo $Html;        
//echo "http://" . $_SERVER['HTTP_HOST'] . "/statepdftemp.php";
       // exit;
      ///  error_reporting(-1);
        //ini_set('display_errors', 'On');
        //echo 'inside state charts'; exit;
        $pdf = new PDF();
        $coverage = new Coverage();
        $cons = new Consumption();
        $helper = new Helper2();
        $stockout = new Stockout();
         
        $lastPullDate = $helper->getLatestPullDate();
        $date = substr($lastPullDate, 0,-3);
$folder_mode = $date." State";
$folder_name = str_replace(" ", "_", $folder_mode);

$createDirectory = mkdir("pdfrepo/state/".$folder_name."");


        //TP: passing the report id and the location id of the current location into variables
        $location_id = $reportLocation['location_id'];
        $report_id = $reportLocation['report_id'];
        $local_list = implode(",",$helper->fetchlocwithparentid($location_id));
        
        
        
       
        //TP: consumption overtime chart gotten from the database  
        $lastPullDate = $helper->getLatestPullDate();
        $time = strtotime($lastPullDate);
        $injectables_id = 15;
        $implants_id = 18;
       
        //TP:: getting the consumption overtime data into two variables using list
        list($methodName,$consBySingleCommodityAndLocation) = $cons->fetchAllConsumptionBySingleLocationOverTime($location_id, "2");
        //$consumption_monthly_overtime_implants = $cons->fetchConsumptionByCommodityAndLocationOverTime($injectables_id, $location_id, "2");
  
                
        //TP: date pdf was generated     
        $date_viewshow = date('F Y',$time);
       
        //TP: for percentage fp providing for single state and national
        $fp_facility_prov = $pdf->fetchPercentFacsProvidingPerLocation("fp","2", $location_id);
        //print_r($fp_facility_prov);exit;
        $fp_array = array();
        array_push($fp_array,$fp_facility_prov[0]);
        foreach($fp_facility_prov as $data){
               if($location_id==$data['location_id']){
                   array_push($fp_array,$data);  
                   continue;
               }
           }
      
       
       //TP for percentage larc providing for single state and national
        $larc_facility_prov = $pdf->fetchPercentFacsProvidingPerLocation("larc","2", $location_id);
        $larc_array = array();
        array_push($larc_array,$larc_facility_prov[0]);
        
        foreach($larc_facility_prov as $data){
               if($location_id==$data['location_id']){
                   array_push($larc_array,$data);
                   continue;
               }
           }
       
       
       
        //TP for percentage hw trained in larc for single state and national
        $larc_trained_in = $pdf->fetchPercentFacHWTrainedPerState("larc",2,$location_id);
        $larc_trained_array = array();
        array_push($larc_trained_array,$larc_trained_in[0]);
        foreach($larc_trained_in as $data){
           
           if($location_id==$data['location_id']){
               array_push($larc_trained_array,$data);
              continue;
               
           }
       }
       
     
       //TP:  for all local governments
      $fp_trained_lgas =  $pdf->fetchPercentFacHWTrainedPerState("fp",3,$local_list);
      
      $larc_trained_lgas = $pdf->fetchPercentFacHWTrainedPerState("larc",3,$local_list);
     //echo 'hello';exit;
        $fp_trained_lgass[] = $fp_trained_lgas[0];
        $fp_trained_lgass = array_merge($fp_trained_lgass, array_slice($fp_trained_lgas,1,5));
        $fp_trained_lgas = array_merge($fp_trained_lgass, array_slice($fp_trained_lgas,count($fp_trained_lgas)-1,1));
       // print_r($fp_trained_lgas);echo '<br/><br/>';
         $larc_trained_lgass[] = $larc_trained_lgas[0];
        $larc_trained_lgass = array_merge($larc_trained_lgass, array_slice($larc_trained_lgas,1,5));
        $larc_trained_lgas = array_merge($larc_trained_lgass, array_slice($larc_trained_lgas,count($larc_trained_lgas)-1,1));
        
        //print_r($larc_trained_lgas);exit;
        
        //print_r($fp_trained_lgas);exit;
        
        
        
        
      //echo '<br/><br/>';
     // print_r($larc_trained_lgas);exit;
      $geoList =  implode(",",$helper->fetchlocwithparentid($location_id));
     
      $fp_prov_lgas =  $pdf->fetchPercentFacsProvidingPerState("fp",3,$local_list);
      $larc_prov_lgas = $pdf->fetchPercentFacsProvidingPerState("larc",3,$local_list);
        
      
   
 //  echo $local_list;
    // echo 'working';exit;
      
     
      
       //TP for percentage hw trained in fp for single state and national
       $fp_trained_in = $pdf->fetchPercentFacHWTrainedPerState("fp",2,$location_id);
       $fp_trained_array = array();
        array_push($fp_trained_array,$fp_trained_in[0]);
      
        foreach($fp_trained_in as $data){
           
           if($location_id==$data['location_id']){
               array_push($fp_trained_array,$data);
              continue;
               
           }
       } 
       
       $state_code_larc = $pdf->fetchFacsWithHWProviding("larc", "larc", $location_id, 2);
      
      // print_r($state_code);
       $lister = $pdf->fetchFacsWithHWProviding("larc", "larc", $local_list, 3);
       //print_r($lister);
       //print_r($larc_percent_multiple);
      // exit;
       //TP: getting percentage of facilities providing larc for the whole lgas in a given state.
       $larc_percent_multiple = $pdf->fetchFacsWithHWProviding("larc", "larc", $local_list, 3);  //$coverage->fetchPercentFacsProvidingPerLocation("larc",3,$location_id);
         
       $newLarcArray = array_reverse($larc_percent_multiple,true);
         array_pop($newLarcArray);
       $sizeof = sizeof($larc_percent_multiple);
       
      for($i=0;$i<$sizeof;$i++){
          if($larc_percent_multiple[$i]['percent']==""){
              if($state_code_larc[1]['percent']!=1 || $state_code_larc[1]['percent']!="1"){
              $larc_percent_multiple[$i]['percent'] = 0;
              }
              }
          }
      if($state_code_larc[1]['percent']==""){
              $state_code_larc[1]['percent'] = 0;
          }
      
     // fetchFacsWithHWProviding
         /* $larc_percent_multiple = array();
       $larc_percent_multiple[0]['location'] = $larc_array[1]['location'];
       $larc_percent_multiple[0]['percent'] = $state_code_larc[1]['percent'];
       $larc_percent_multiple = array_merge($larc_percent_multiple,$newLarcArray);
      */
       $larc_percent_multipleData  = array();
        $larc_percent_multipleData[] = $state_code_larc[1];
        $larc_percent_multipleData = array_merge($larc_percent_multipleData, array_slice($larc_percent_multiple,1,5));
        $larc_percent_multipleData = array_merge($larc_percent_multipleData, array_slice($larc_percent_multiple,count($larc_percent_multiple)-1,1));
        //print_r($larc_percent_multipleData);exit;
      // $lister[0]['location'] = $larc_array[1]['location'];
       //$lister[0]['percent'] 
       
        //print_r($larc_percent_multiple);exit;
       //TP: getting percentage of facilities providing larc for the whole lgas in a given state.
       $state_code_fp = $pdf->fetchFacsWithHWProviding("fp", "fp", $location_id, 2);
      
       $fp_percent_multiple =  $pdf->fetchFacsWithHWProviding("fp", "fp", $local_list, 3);  //$coverage->fetchPercentFacsProvidingPerLocation("fp",3,$location_id);
      //print_r($state_code_fp);exit;
      //echo '<br/><br/>';
     // print_r($fp_percent_multiple);exit;
       $sizeof = sizeof($fp_percent_multiple);
      for($i=0;$i<$sizeof;$i++){
          if($fp_percent_multiple[$i]['percent']==""){
              if($location_id!="969"){
              $fp_percent_multiple[$i]['percent'] = 0;
              }
              }
          }
        ;
      
          if($state_code_fp[1]['percent']==""){
              $state_code_fp[1]['percent'] = 0;
          }
      /*
       $fp_percent_multiple[0]['location'] = $fp_array[1]['location'];
       $fp_percent_multiple[0]['percent'] = $state_code_fp[1]['percent'];
         */
      $fp_percent_multipleData  = array();
        $fp_percent_multipleData[] = $state_code_fp[1];
        $fp_percent_multipleData = array_merge($fp_percent_multipleData, array_slice($fp_percent_multiple,1,5));
        $fp_percent_multipleData = array_merge($fp_percent_multipleData, array_slice($fp_percent_multiple,count($fp_percent_multiple)-1,1));
      
        
       
       //TP: fp stock out of implants
       $stock_out_fp_seven_days = $stockout->fetchPercentFacsProvidingButStockedOut("fp", $local_list,3,false,true);
       $state_stocked_out = $stockout->fetchPercentFacsProvidingButStockedOut("fp",$location_id,2,false,true);
       
       $stock_out_fp_seven_days[0]['location'] = $fp_array[1]['location'];
       $stock_out_fp_seven_days[0]['percent'] = $state_stocked_out[1]['percent'];
       
       
     
 
       
       
       
       
       
       //TP: larc stock of implants
       $stock_out_larc_implants = $stockout->fetchPercentFacsProvidingButStockedOut("larc", $local_list,3,false,true);
       $state_stocked_out_implants = $stockout->fetchPercentFacsProvidingButStockedOut("larc",$location_id,2,false,true);
       
       $stock_out_larc_implants[0]['location'] = $larc_array[1]['location'];
       $stock_out_larc_implants[0]['percent'] = $state_stocked_out_implants[1]['percent'];
       
       
       
         
           $length = sizeof($larc_trained_lgas);
           
           $table_array = array();
           for($i=1;$i<6;$i++){
              
               if($fp_trained_lgas[$i]['percent']==0)$fp_trained_lgas[$i]['percent']=0; 
               if($larc_trained_lgas[$i]['percent']==0)$larc_trained_lgas[$i]['percent']=0; 
               //echo 'hello';
               $percent_fp_trained = round($fp_trained_lgas[$i]['percent']*100,1);
               $percent_larc_trained = round($larc_trained_lgas[$i]['percent']*100,1);
               
               $fptrained = $fp_trained_lgas[$i]['location']." (".$percent_fp_trained."%)";
               $larctrained = $larc_trained_lgas[$i]['location']." (".$percent_larc_trained."%)";
              
               if($fp_prov_lgas[$i]['percent']==0)$fp_prov_lgas[$i]['percent']=0;                   
               if($larc_prov_lgas[$i]['percent']==0)$larc_prov_lgas[$i]['percent']=0;  
               
               $percent_fp_prov = round($fp_prov_lgas[$i]['percent']*100,1);
               $percent_larc_prov = round($larc_prov_lgas[$i]['percent']*100,1);
               
               $fpprov = $fp_prov_lgas[$i]['location']." (".$percent_fp_prov."%)";
               $larcprov = $larc_prov_lgas[$i]['location']." (".$percent_larc_prov."%)";
             
               array_push($table_array,$fptrained);
               array_push($table_array,$larctrained);
               array_push($table_array,$fpprov);
               array_push($table_array,$larcprov);
              
               }
              // print_r($table_array);exit;
      
       
       
       //print_r($stock_out_fp_seven_days);exit;
       
       //print_r($larc_trained_in);echo 'number iya';
   //print_r($fp_array);exit;
       
        //print_r($fp_trained_in);exit;
       //print_r($fp_percent_multiple);exit;
             
       //$keys = array_search("",$fp_percent_multiple);
       //print_r($keys);//exit;
       //
              
        //$fp_percent_multiple;
               
               if($location_id=="969"){
                   $fp_percent_multiple = $pdf->SortFacilityDataPercent($fp_percent_multiple);
        $larc_percent_multiple = $pdf->SortFacilityDataPercent($larc_percent_multiple);
               }
               
        $larc_percent_multiples[] = $larc_percent_multiple[0];
        $larc_percent_multiples = array_merge($larc_percent_multiples, array_reverse(array_slice($larc_percent_multiple,1,5), true));
        $larc_percent_multiple = array_merge($larc_percent_multiples, array_slice($larc_percent_multiple,count($larc_percent_multiple)-1,1));
        
        
        
        $fp_percent_multiples[] = $fp_percent_multiple[0];
        $fp_percent_multiples = array_merge($fp_percent_multiples, array_reverse(array_slice($fp_percent_multiple,1,5), true));
        $fp_percent_multiple = array_merge($fp_percent_multiples, array_slice($fp_percent_multiple,count($fp_percent_multiple)-1,1));
        
        
        
        
              // print_r($larc_percent_multiple);
               //echo '<br/><br/>';
                //print_r($fp_percent_multiple);exit;
               //         exit;
                        ////print_r($fp_providing_percent_values);
                       //exit;
       //TP: assigning the datas to the placeholder to be used in the view
        $this->view->assign('larc_trained_in',$larc_trained_array);
        $this->view->assign('fp_trained_in',$fp_trained_array);
        $this->view->assign('fp_percent_facprov',$fp_array);
        //print_r($fp_array);exit;
        $this->view->assign('larc_percent_facprov',$larc_array);
        $this->view->assign('report_id',$report_id);
        $this->view->assign('consumption_by_method',$methodName);
        $this->view->assign('consumption_overtime',$consBySingleCommodityAndLocation); 
        $this->view->assign('percent_prov_date',$date_viewshow);
        $this->view->assign('larc_percent_providing',$larc_percent_multipleData);
        $this->view->assign('fp_percent_providing',$fp_percent_multipleData);
        $this->view->assign('stock_out_fp_com_seven_days',$stock_out_fp_seven_days);
        $this->view->assign('stock_out_larc_implants',$stock_out_larc_implants);
        $this->view->assign('fp_trained_lgas',$fp_trained_lgas);
        $this->view->assign('larc_trained_lgas',$larc_trained_lgas);
        $this->view->assign('fp_prov_lgas',$fp_prov_lgas);
        $this->view->assign('table__data_sender',$table__data_sender);
        $this->view->assign('larc_prov_lgas',$larc_prov_lgas);
        $this->view->assign('table_array',$table_array);
        $this->view->assign('folder_name',$folder_name);
      //print_r($fp_trained_lgas);
     // print_r($larc_trained_lgas);
      
    }
    public function showreportsAction(){     
        //echo $_SERVER['HTTP_HOST'] . "/pdftemplate.php"; exit;
        $pdf = new PDF();
        //echo 'trying';
        //$this->_helper->layout()->disableLayout();
        //$this->_helper->viewRenderer->setNoRender(true);
        //exit;
           
        if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->isPost()) {

           }
        }
        else {
               //echo 'before'; exit;
               //first ensure that all the locations have been registered
                $pdf->insertLocationIds();
/*
          $pdf->update_database("2015-08-01", "2015-08-31","0");
          exit;
          */
               //get the next unflagged (with 0 for the file_generated value) location
                //$reportLocation = $pdf->getNextLocationDetails();
                 /*TP
                * the getNextlocationDetailssWithTiers method has two optional argument which are
                *  $tierValue and $location_id respectively.
                 */
                $reportLocation = $pdf->getNextLocationDetailsWithTiers("","0","0");
                
              
                
                //var_dump($reportLocation); exit;
                //call the handler for national or state or lga
                $this->createNationalCharts($reportLocation);
                
                
           }
           
       
    }
    

    public function  createNationalCharts($reportLocation){
        $helper = new Helper2();
        $larc_target = 5500;
        $fp_target = 5500;
        $pdf = new PDF();
        $lastPullDate = $helper->getLatestPullDate();
        //$pdf = new PDF();
        
        
       
        //$pdf->updatePdfReportsTable($lastPullDate);
        $date = substr($lastPullDate, 0,-3);
$folder_mode = $date." National";
$folder_name = str_replace(" ", "_", $folder_mode);
$folderName = "national/".$folder_name."/";
mkdir("pdfrepo/national/".$folder_name."");
        $reportId = $reportLocation['report_id'];
        if($reportId=="" || empty($reportId)){
            $this->view->assign('info',"The National Report for the Last Pulled Date has been Generated Earlier");
            return;
        }
       
      
//echo $reportId;exit;
        list($geoList, $tierValue) = $this->buildParameters();
     
        //trained workers
        $coverage = new Coverage();
        $cumm_data = $coverage->fetchCummulativeTrainedWorkers(1, $geoList, $tierValue);
        $key = key($cumm_data); 
        $this->view->assign('cumm_data', $cumm_data);
        $this->view->assign('fp_diff', ($fp_target - $cumm_data[$key]['fp']));
        $this->view->assign('larc_diff', ($larc_target - $cumm_data[$key]['larc']));
        
        
        //method mix
        $dashboard = new Dashboard();
        $consumptionbyMethod = $dashboard->fetchConsumptionByMethod();
        $this->view->assign('consumption_by_method', $consumptionbyMethod);
        
        
        //consumption over time - implants and injectables
        $cons = new Consumption();
        //$upperDate = "2015-08-31";
        //$lowerDate = "2014-09-01";
        $consOverTime = $cons->fetchConsumptionByCommodityOverTime();
       // print_r($consOverTime);exit;
        $this->view->assign('consumption_overtime',$consOverTime);
        
       
        //percentfacstrainedperstate
        $fp_percent_per_state = $pdf->fetchPercentFacHWTrainedPerLocationCoverage('fp');
        
        $percentData = array();
        $percentData[] = $fp_percent_per_state[0];
        $percentData = array_merge($percentData, array_slice($fp_percent_per_state,1,5));
        $percentData = array_merge($percentData, array_slice($fp_percent_per_state,count($fp_percent_per_state)-1,1));
        $this->view->assign('fp_percent_per_state',$percentData);
        
        $larc_percent_per_state = $pdf->fetchPercentFacHWTrainedPerLocationCoverage('larc');
        $percentData = array();
        $percentData[] = $larc_percent_per_state[0];
        $percentData = array_merge($percentData, array_slice($larc_percent_per_state,1,5));
        $percentData = array_merge($percentData, array_slice($larc_percent_per_state,count($larc_percent_per_state)-1,1));
        $this->view->assign('larc_percent_per_state',$percentData);
         //echo 'It is working now';
        
        //fetchPercentFacsProvidingPerState
        $fp_providing_per_state = $coverage->fetchPercentFacsProvidingPerState('fp');
        $percentData = array();
        $percentData[] = $fp_providing_per_state[0];
        $percentData = array_merge($percentData,array_slice($fp_providing_per_state,1,5));
        $percentData = array_merge($percentData, array_slice($fp_providing_per_state,count($fp_providing_per_state)-1,1));
        $this->view->assign('fp_providing_per_state',$percentData);
        
        $larc_providing_per_state = $coverage->fetchPercentFacsProvidingPerState('larc');
        $percentData = array();
        $percentData[] = $larc_providing_per_state[0];
        $percentData = array_merge($percentData, array_slice($larc_providing_per_state,1,5));
        $percentData = array_merge($percentData, array_slice($larc_providing_per_state,count($larc_providing_per_state)-1,1));
        $this->view->assign('larc_providing_per_state',$percentData);
        
        
        //facs with fp/larc trained and stocked out of fp(so7days)/larc commodities
        $stockout = new Stockout();
        
        $larc_stockout_per_state = $pdf->fetchFacsWithHWProvidingCoverage("larc","larc",'',2);  //$stockout->fetchPercentStockOutFacsWithTrainedHWPerStates('larc');
       //print_r($larc_stockout_per_state);exit;
        $percentData = array();
        $percentData[] = $larc_stockout_per_state[0];
        $percentData = array_merge($percentData, array_slice($larc_stockout_per_state,1,5));
        $percentData = array_merge($percentData, array_slice($larc_stockout_per_state,count($larc_stockout_per_state)-1,1));
       //print_r($percentData);exit;
        $this->view->assign('larc_stockout_per_state',$percentData);
       
        $tierValues = "2";
        $geoLists = array();
        $geoLists = $helper->getLocationTierIDs($tierValues);
        $geoListStock = implode(',',$geoLists);
        //echo $geoListStock;exit;
        //var_dump($geoLists);exit;
        //$fp_stockout_per_state = $stockout->fetchPercentStockOutFacsWithTrainedHWPerStates('fp');
        $fp_stockout_per_state = $stockout->fetchPercentFacsProvidingButStockedOut('fp', $geoListStock, $tierValues,false,true);
        //fetchFacilitiesProvidingButStockedOut
        
        $percentData = array();
        $percentData[] = $fp_stockout_per_state[0];
        $percentData = array_merge($percentData, array_slice($fp_stockout_per_state,1,5));
        $percentData = array_merge($percentData, array_slice($fp_stockout_per_state,count($fp_stockout_per_state)-1,1));
        $this->view->assign('fp_stockout_per_state',$percentData);
        $this->view->assign('folder_name',$folder_name);
        $this->view->assign('report_id',$reportId);
    }
    

    public function testAction (){
                $html = '';
                echo 'inside test';
                try{
                    
//                    require_once 'Zend/Loader/Autoloader.php';
//                    //require_once('Zend/dompdf/dompdf_config.inc.php');
//                    $load = Zend_Loader_Autoloader::getInstance();
//                    $load->pushAutoloader('DOMPDF_autoload','');
                    
                    
                    $html = 'This is from inside zend';
                
                    $dompdf = new DOMPDF();
                    $dompdf->load_html(trim($html));
                    $dompdf->render();
                    //$dompdf->stream("sample" . date('His') . ".pdf");
                    $pdf = $dompdf->output();
                    file_put_contents("sample" . date('His') . ".pdf", $pdf);
                    
                } catch(Exception $e){
                    $e->getMessage();
                    print '<br><br>';
                    $e->getTrace();
                }
                
                $this->view->assign('html', $html);
    }
    public function createpdfStateAction(){
        $pdfs = new PDF();
         if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->isPost()) {
                //echo json_encode(array('result'=>'OK')); exit;
                $helper = new Helper2();
                $lastPullDate = $helper->getLatestPullDate();
                $month = date('F', strtotime($lastPullDate));
                
               $overTimeDates = $helper->getPreviousMonthDates(12);
                $startMonth = date('F', strtotime($overTimeDates[11])). ' '. date('Y', strtotime($overTimeDates[11])); 
                $endMonth = date('F', strtotime($overTimeDates[0])). ' '. date('Y', strtotime($overTimeDates[0])); 
                
                $year = (int)date('Y', strtotime($lastPullDate));
                $date_generated = date('Y-m-d');
                //$date_generated = "2016-02-28";
                
                $firstdate = date('M Y',strtotime($lastPullDate));
                try{
                    //$html = file_get_contents("pdftemplate.php");
                   $html = file_get_contents("statepdftemp.php");
              // $html = file_get_contents("http://" . $_SERVER['HTTP_HOST'] . "/statepdftemp.php");
                   //echo "http://" . $_SERVER['HTTP_HOST'] . "/statepdftemp.php";
                $data_array =   explode(",",$_POST['table_hidden']);
                    $html = sprintf($html,
                                    $month, //1
                                    $year,  //2
                                    $startMonth,   //3
                                    $endMonth,     //4
                                    $firstdate, //5
                                    $_POST['state_hidden'],//6
                                    $_POST['fcs_hidden'], //7
                                    $_POST['fac_hw_hidden'], //8
                                    $_POST['chart13_hidden'], //9
                                    $_POST['fpprov_hidden'], //10
                                    $_POST['larcprov_hidden'], //11
                                    $_POST['fpstock_hidden'], //12
                                    $_POST['larc_stock_out_hidden'], //13
                                    $data_array[0], //14
                                    $data_array[1],  //15
                                    $data_array[2],  //16
                                    $data_array[3],  //17
                                    $data_array[4],  //18
                                    $data_array[5],  //19
                                    $data_array[6],  //20
                                    $data_array[7],  //21
                                    $data_array[8],   //22
                                    $data_array[9],  //23
                                    $data_array[10],  //24
                                    $data_array[11],  //25
                                    $data_array[12],   //26
                                    $data_array[13],  //27
                                    $data_array[14],  //28
                                    $data_array[15],  //29
                                    $data_array[16],  //30
                                    $data_array[17],  //31
                                    $data_array[18],  //32
                                    $data_array[19],   //33
                                    "%" //34
                                    
                                    
                    
                            );
                //14
                $folder_name = $_POST['folder_name'];
                 //$data_array = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20);
              //   "pdfrepo/lga/".$folder_name."/".$join."_Report_$month"
             file_put_contents('pdfrepo/state/'.$folder_name.'/template_modifieds.txt', $html);
             $html = file_get_contents('pdfrepo/state/'.$folder_name.'/template_modifieds.txt');
                //$html =  vprintf($html,$data_array);
                   $report_id = $_POST['report_hidden'];
                   $join = str_replace(" ", "_", $_POST['state_hidden']);
                   $join = str_replace("/","_",$join);
                   //echo json_encode('State hidden '.$_POST['state_hidden']).'<br/>';
                   //echo json_encode('FCS hidden '.$_POST['fcs_hidden']).'<br/>';
                  // echo json_encode('This is the report id '.$report_id);
                    file_put_contents('pdfrepo/state/'.$folder_name.'/template_modified.txt', $html);
//file_put_contents('pdfrepo/state/'.$folder_name.'/template_modified.txt');
                   $dompdf = new DOMPDF();
                   $dompdf->load_html(trim($html));
                   $dompdf->render();
                   //$dompdf->stream("sample" . date('His') . ".pdf");

                   $pdf = $dompdf->output();
                   $filename = $join."_Report_$month" . "_$year.pdf";
                   $folderName = "state/".$folder_name."/";
                   //echo json_encode($filename);
                   file_put_contents("pdfrepo/state/".$folder_name."/".$join."_Report_$month" . "_$year.pdf", $pdf);
                  // echo ("pdfrepo/state/".$folder_name."/".$join."_Report_$month" . "_$year.pdf");exit;
                   echo json_encode(array('result'=>'OK','message'=>'This is successful'));
                 $pdfs->updatereportid($report_id,$filename,$date_generated,$folderName);
                }
                catch(Exception $e){
                    echo json_encode(array('result'=>'Error','message'=>$e->getMessage()));
                    exit;
                }
            }
            
            }
     
    }
    public function createpdfLgaAction(){
        $pdfs = new PDF();
         if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->isPost()) {
                //echo json_encode(array('result'=>'OK')); exit;
                $helper = new Helper2();
                $lastPullDate = $helper->getLatestPullDate();
                $month = date('F', strtotime($lastPullDate));
                
               $overTimeDates = $helper->getPreviousMonthDates(12);
                $startMonth = date('F', strtotime($overTimeDates[11])). ' '. date('Y', strtotime($overTimeDates[11])); 
                $endMonth = date('F', strtotime($overTimeDates[0])). ' '. date('Y', strtotime($overTimeDates[0])); 
               
                $year = (int)date('Y', strtotime($lastPullDate));
                $date_generated = date('Y-m-d');
                //$date_generated = "2016-02-28";
                $firstdate = date('M Y',strtotime($lastPullDate));
                try{
                   //$html = file_get_contents("http://localhost/trainsmart/html/pdftemplate.php");
                 $html = file_get_contents("lgapdftemp.php");
                // $html = file_get_contents("http://". $_SERVER['HTTP_HOST'] . "/lgapdftemp.php");
                //echo "http://" . $_SERVER['HTTP_HOST'] . "/statepdftemp.php";
               // $data_array =   explode(",",$_POST['table_hidden']);
                  $new_contents =  explode('firsttablebreak',$html);
                  $FpFacilitiesNotProv = $_POST['facilities_list_fp_prov_hidden'];
                  $FacilitiesFpListNotProviding = explode(',',$FpFacilitiesNotProv);
                  $PercentFacilitiesListFpProvHidden = $_POST['percent_facilities_list_fp_prov_hidden'];
                  //print_r($FacilitiesFpListNotProviding);exit;
                  
                  $sizeof = sizeof($FacilitiesFpListNotProviding);
                  if($sizeof>30){
                      $sizeof = 30;
                  }
                  $colspan = ceil($sizeof/5);
                
$counter = 0;
$r = 0;
$modules = ($sizeof%5);
$number_size = floor($sizeof/5);
//echo 'This is the number size'.$number_size;
if($colspan>1){
    $inner_limit = 5;
}else{
    $inner_limit = $sizeof;
}
$long_array = array();
$chunked_array = array_chunk($FacilitiesFpListNotProviding,5);
$br = "<br/>";
for($i=0;$i<$colspan;$i++){
    $br .="<br/>"; 
$new_array = $chunked_array[$i];
for($r=0;$r<$inner_limit;$r++){
$long_array[$r][] = $new_array[$r];
}
}     

//TP: Generating the table for the FP trained hw and not providing FP

   $table_structure =  '<div class="row" style="top:45;">
                    <fieldset class="fontsize10" >
                         <legend class="fieldsetlegend">Facilities with FP trained health workers that are <u>not</u> providing FP
</legend><br/>
This indicator identifies the facilities that should be providing FP (because they have a trained HW) but are not. There may be several reasons why they are not providing FP. For example, they may be stocked out. Call the facilities listed here to find out whether they are stocked out, require additional mentoring, or are facing another barrier to service provision. When conducting supportive supervision, target these facilities. 
<br/><br/>
                        <table height="auto">
                            <thead>
<th colspan="'.$colspan.'">Facilities</th>
    </thead>
    <tbody>
';
$table_size = sizeof($long_array);

for($i=0;$i<$table_size;$i++){
$TableTr = $long_array[$i];
$table_structure .= '<tr>';
foreach($TableTr as $TableTd){
$table_structure .='<td>'.$TableTd.'</td>';
}
$table_structure .= '</tr>';

}
$table_structure .= '</tbody></table><br/>';
$footer_div =  '<span class="footer">Percent of facilities in the state with FP trained HWs that are not providing FP:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$PercentFacilitiesListFpProvHidden.'%12$s</span></fieldset></div>';
$table_structure .= $footer_div;



               $LarcFacilitiesNotProv = $_POST['facilities_list_larc_prov_hidden'];
                  $FacilitiesLarcListNotProviding = explode(',',$LarcFacilitiesNotProv);
                  $PercentFacilitiesListLarcProvHidden = $_POST['percent_facilities_larc_prov'];
                 // print_r($FacilitiesLarcListNotProviding);exit;
                  $sizeof = sizeof($FacilitiesLarcListNotProviding);
                  if($sizeof>30){
                      $sizeof = 30;
                  }
                  $colspan = ceil($sizeof/5);
                
$counter = 0;
$r = 0;
$modules = ($sizeof%5);
$number_size = floor($sizeof/5);
//echo 'This is the number size'.$number_size;
$long_array = array();
$chunked_array = array();
if($colspan>1){
    $inner_limit = 5;
}else{
    $inner_limit = $sizeof;
}
$chunked_array = array_chunk($FacilitiesLarcListNotProviding,5);
   $br ="<br/>";
for($i=0;$i<$colspan;$i++){
       $br .="<br/>";
$new_array = $chunked_array[$i];
for($r=0;$r<$inner_limit;$r++){
$long_array[$r][] = $new_array[$r];
}
}     
//TP: Generating the table for the LARC trained hw and not providing LARC

   $table_structure .=  ''. $br.'<br/><br/><div class="row" style="top:50;">
                    <fieldset class="fontsize10">
                         <legend class="fieldsetlegend">Facilities with LARC trained health workers that are <u>not</u> providing LARC
</legend><br/>
This indicator identifies the facilities that should be providing LARC (because they have a trained HW) but are not. There may be several reasons why they are not providing LARC. For example, they may be stocked out. Call the facilities listed here to find out whether they are stocked out, require additional mentoring, or are facing another barrier to service provision. When conducting supportive supervision, target these facilities. 

<br/><br/>
                        <table>
                            <thead>
<th colspan="'.$colspan.'">Facilities</th>
    </thead>
    <tbody>
';
   //print_r($long_array);exit;
$table_size = sizeof($long_array);
for($i=0;$i<$table_size;$i++){
$TableTr = $long_array[$i];
$table_structure .= '<tr>';
foreach($TableTr as $TableTd){
$table_structure .='<td>'.$TableTd.'</td>';
}
$table_structure .= '</tr>';

}
$table_structure .= '</tbody></table><br/>';
$footer_div =  '<span class="footer">Percent of facilities in the state with LARC trained HWs that are not providing LARC:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$PercentFacilitiesListLarcProvHidden.'%12$s</span></fieldset></div>';
$table_structure .= $footer_div;




$StockOutFP = $_POST['facilities_stocked_out_fp_seven_days_hidden'];
                  $StockOutFPDetails = explode(',',$StockOutFP);
                  $PercentStateStockOutFP = $_POST['percent_state_stock_out_fp_seven_days_hidden'];
                 // print_r($FacilitiesLarcListNotProviding);exit;
                  $sizeof = sizeof($StockOutFPDetails);
                  if($sizeof>30){
                      $sizeof = 30;
                  }
                  $colspan = ceil($sizeof/5);
                
$counter = 0;
$r = 0;
$modules = ($sizeof%5);
$number_size = floor($sizeof/5);
//echo 'This is the number size'.$number_size;
$long_array = array();
$chunked_array = array();
if($colspan>1){
    $inner_limit = 5;
}else{
    $inner_limit = $sizeof;
}
 $br ="<br/>";
$chunked_array = array_chunk($StockOutFPDetails,5);
for($i=0;$i<$colspan;$i++){
     $br .="<br/>";
$new_array = $chunked_array[$i];
for($r=0;$r<$inner_limit;$r++){
$long_array[$r][] = $new_array[$r];
}
}     
//TP: Generating the table for the FP Stocked Out in seven days

   $table_structure .=  ''. $br.'<br/><br/><br/><div class="row" style="top:20;">
                    <fieldset class="fontsize10">
                         <legend class="fieldsetlegend">FP facilities stocked out for 7 days
</legend><br/>
This indicator identifies facilities that have provided FP in the last six months but reported a stock out of any FP commodity for seven consecutive days during the current month. Call these facilities to find out what commodity is stocked out and arrange an emergency shipment of stock from the LGA store. 

<br/><br/>
                        <table>
                            <thead>
<th colspan="'.$colspan.'">Facilities</th>
    </thead>
    <tbody>
';
   //print_r($long_array);exit;
$table_size = sizeof($long_array);
for($i=0;$i<$table_size;$i++){
$TableTr = $long_array[$i];
$table_structure .= '<tr>';
foreach($TableTr as $TableTd){
$table_structure .='<td>'.$TableTd.'</td>';
}
$table_structure .= '</tr>';

}
$table_structure .= '</tbody></table><br/>';
$footer_div =  '<span class="footer">Percent of FP facilities in the state stocked out for 7 days:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$PercentStateStockOutFP.'%12$s</span></fieldset></div>';
$table_structure .= $footer_div;



$StockOutLarc = $_POST['facilities_stocked_out_larc_hidden'];
                  $StockOutLARCDetails = explode(',',$StockOutLarc);
                  $PercentStateStockOutLARC = $_POST['percent_state_stock_out_larc_hidden'];
                 // print_r($FacilitiesLarcListNotProviding);exit;
                  $sizeof = sizeof($StockOutLARCDetails);
                  if($sizeof>30){
                      $sizeof = 30;
                  }
                  $colspan = ceil($sizeof/5);
                
$counter = 0;
$r = 0;
$modules = ($sizeof%5);
$number_size = floor($sizeof/5);
//echo 'This is the number size'.$number_size;
$long_array = array();
$chunked_array = array();
if($colspan>1){
    $inner_limit = 5;
}else{
    $inner_limit = $sizeof;
}
 $br ="<br/>";
$chunked_array = array_chunk($StockOutLARCDetails,5);
for($i=0;$i<$colspan;$i++){
     $br .="<br/>";
$new_array = $chunked_array[$i];
for($r=0;$r<$inner_limit;$r++){
$long_array[$r][] = $new_array[$r];
}
}     
//TP: Generating the table for the LARC implant stock out

   $table_structure .=  ''. $br.'<br/><br/><div class="row" style="top:20;">
                    <fieldset class="fontsize10">
                         <legend class="fieldsetlegend">LARC facilities stocked out of implants
</legend><br/>
This indicator identifies the facilities that have provided at least one implant over the last six months but are stocked out of implants during the current month. Call these facilities to arrange an emergency shipment of stock from the LGA store. Ensure the facilities are connected to the FP commodity distribution system and are using CLMS forms correctly.

<br/><br/>
                        <table>
                            <thead>
<th colspan="'.$colspan.'">Facilities</th>
    </thead>
    <tbody>
';
   //print_r($long_array);exit;
$table_size = sizeof($long_array);
for($i=0;$i<$table_size;$i++){
$TableTr = $long_array[$i];
$table_structure .= '<tr>';
foreach($TableTr as $TableTd){
$table_structure .='<td>'.$TableTd.'</td>';
}
$table_structure .= '</tr>';

}
$table_structure .= '</tbody></table><br/>';
$footer_div =  '<span class="footer">Percent of LARC facilities in the state stocked out of implants:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$PercentStateStockOutLARC.'%12$s</span></fieldset></div>';
$table_structure .= $footer_div;


$new_contents[0].=$table_structure;
$html = implode("<br/><br/>",$new_contents);
                  
        
                    $html = sprintf($html,
                                    $month, //1
                                    $year,  //2
                                    $startMonth,   //3
                                    $endMonth,     //4
                                    $firstdate, //5
                                    $_POST['state_name'],//6
                                    $_POST['lga_name_hidden'], //7
                                    $_POST['facilitysum_hidden'], //8
                                    $_POST['chart13_hidden'], //9
                                    $_POST['percent_facilities_list_fp_prov_hidden'], //10
                                    $_POST['facilities_list_fp_prov_hidden'], //11
                                     "%"//12
                               
                                    
                                    
                    
                            );
                //14
                 //$data_array = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20);
                 $folder_name = $_POST['folder_name'];
             file_put_contents('pdfrepo/lga/'.$folder_name.'/template_modifieds.txt', $html);
             $html = file_get_contents('pdfrepo/lga/'.$folder_name.'/template_modifieds.txt');
                //$html =  vprintf($html,$data_array);
                   $report_id = $_POST['report_hidden'];
             
                   $join = str_replace(" ", "_", $_POST['lga_name_hidden']);
                   $join = str_replace("/","_",$join);
                   //echo json_encode('State hidden '.$_POST['state_hidden']).'<br/>';
                   //echo json_encode('FCS hidden '.$_POST['fcs_hidden']).'<br/>';
                  // echo json_encode('This is the report id '.$report_id);
                    file_put_contents('pdfrepo/lga/'.$folder_name.'/template_modified.txt', $html);
file_put_contents('pdfrepo/lga/'.$folder_name.'/template_modified.txt');
                   $dompdf = new DOMPDF();
                   $dompdf->load_html(trim($html));
                   $dompdf->render();
                   //$dompdf->stream("sample" . date('His') . ".pdf");

                   $pdf = $dompdf->output();
                   $filename = $join."_Report_$month" . "_$year.pdf";
                   $folderName = "lga/".$folder_name."/";
                   //echo json_encode($filename);
                   file_put_contents("pdfrepo/lga/".$folder_name."/".$join."_Report_$month" . "_$year.pdf", $pdf);
                   echo json_encode(array('result'=>'OK','message'=>'This is successful'));
                   $pdfs->updatereportid($report_id,$filename,$date_generated,$folderName);
                }
                catch(Exception $e){
                    echo json_encode(array('result'=>'Error','message'=>$e->getMessage()));
                    exit;
                }
            }
            
            }
        
        
    }
    public function createpdfAction(){
       
       if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->isPost()) {
                //echo json_encode(array('result'=>'OK')); exit;
                $helper = new Helper2();
                $lastPullDate = $helper->getLatestPullDate();
                $month = date('F', strtotime($lastPullDate));
                $year = (int)date('Y', strtotime($lastPullDate));
                $pdfs = new PDF();
                $overTimeDates = $helper->getPreviousMonthDates(12);
                $startMonth = date('F', strtotime($overTimeDates[11])). ' '. date('Y', strtotime($overTimeDates[11])); 
                $endMonth = date('F', strtotime($overTimeDates[0])). ' '. date('Y', strtotime($overTimeDates[0])); 
                  $date_generated = date('Y-m-d');
                  //$date_generated = "2016-02-28";
                  //$$methodmixValues = explode(",",$methodmixValue);
               try{
                   
                   $methodmixValues = array();
                  $methodmixValues = $_POST['methodmixValuesId'];
                  //echo $method
                  $methodMixExplode = explode(",",$methodmixValues);
                 //print_r($methodMixExplode); echo '<br/><br/>';
                  //echo $methodMixExplode[0];
                  //var_dump($methodmixValues);
                  //exit;
                  
                    $html = file_get_contents("pdftemplate.php");
                 // $html = file_get_contents("http://" . $_SERVER['HTTP_HOST'] . "/pdftemplate.php");
                    
                    $html = sprintf($html,
                                    $month, //1
                                    $year,  //2
                                    $startMonth,   //3
                                    $endMonth,     //4
                                    $_POST['tw_hidden'], //5
                                    $_POST['mm_hidden'],  //6
                                    $_POST['mc_hidden'],  //7
                                    $_POST['fptps_hidden'],  //8
                                    $_POST['larctps_hidden'],  //9
                                    $_POST['fpprov_hidden'],  //10
                                    $_POST['larcprov_hidden'],  //11
                                    $_POST['larcso_hidden'],  //12
                                    $_POST['fpso_hidden'],  //13
                                    $methodMixExplode[0],   //14
                                    $methodMixExplode[1],     //15
                                    $methodMixExplode[2],      //16
                                    $methodMixExplode[3],         //17
                                    $methodMixExplode[4],            //18
                                    $methodMixExplode[5],       //19
                                    $methodMixExplode[6],     //20
                                   "%",     //21 
                                   $_POST['larc_difference'] //22
                            );
                    $folder_name = $_POST['folder_name'];
                    $path = "pdfrepo/national/".$folder_name;
                    
                    file_put_contents("$path/template_modified.txt", $html);
                    $report_id = $_POST['report_id'];
                    $filename = "National_Report_$month" . "_$year.pdf";
                    $folderName = "national/".$folder_name."/";
                    $dompdf = new DOMPDF();
                    $dompdf->load_html(trim($html));
                    $dompdf->render();
                   //$dompdf->stream("sample" . date('His') . ".pdf");

                
                   $pdf = $dompdf->output();
                   //$filename = $join."_Report_$month" . "_$year.pdf";
                   //echo json_encode($filename);
                   file_put_contents("$path/National_Report_$month" . "_$year.pdf", $pdf);
                 //  echo "pdfrepo/national/".$folder_name."/National_Report_$month" . "_$year.pdf";
                   
                   $pdfs->updatereportid($report_id,$filename,$date_generated,$folderName);
                   
                   //file_put_contents("pdfrepo/national/'.$folder_name.'/National_Report_$month" . "_$year.pdf", $pdf);
                   echo json_encode(array('result'=>'OK','message'=>'National Pdf Report Successfully Generated..'));
                } catch(Exception $e){
                    echo json_encode(array('result'=>'OK','message'=>$e->getMessage()));
                    exit;
                }
            }
       }
    }
    
    public function downloadPdfFile($fileId){
        $pdf = new PDF();
        $uniqueData  = $pdf->fetchUniqueReportData($fileId);
        //print_r($uniqueData);exit;
        $folderName = $uniqueData['folder_name'];
        $fileName = $uniqueData['filename'];
        echo $fileName;echo '<br/>';echo $folderName;
        $this->download($folderName,$fileName);
    }
    public function download($folderName,$fileName){
        $filePath = $folderName.'/'.$fileName;
        
    header('Content-Type: application/application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 			header('Content-Disposition: attachment; filename="'.$fileName.'"');
 			header("Content-Type: application/force-download");
 			readfile(Globals::$BASE_PATH . '/html/pdfrepo/'.$filePath.'');
  			$this->view->layout()->disableLayout();
         	$this->_helper->viewRenderer->setNoRender(true);
}
    public function pdfMailLinkAction(){
        $this->_forward ( 'pdfMailingList' );
    }
    public function responseMessage($fullName,$username,$email,$roleName,$status,$reason){
        if($status){
            $message = "PDF report sent to <b>$fullName</b> with username <b>$username</b>, email <b>$email</b>, role <b>$roleName</b><br/><br/>";
        }else{
            $message = "PDF report not sent to <b>$fullName</b> with username <b>$username</b>, email <b>$email</b>, role <b>$roleName</b>. <b>Error: $reason</b><br/><br/>";
        }
        return $message;
    }
    public function pdfMailingListAction(){
        $this->_countrySettings = array();
		$this->_countrySettings = System::getAll();
                $report = new Report();
                $pdf = new PDF();
                
		
                $user  = new User();
                $helper2 = new Helper2();
                $lastPulledDate = $helper2->getLatestPullDate();
                
                $time = strtotime($lastPulledDate);
                $month = date('F',$time);
                $year = date('Y',$time);
                $allUserDetails = $user->fetchAllUsers();
                //print_r($allUserDetails);exit;
               //echo 'hallo';exit;
               foreach($allUserDetails as $userDetails){
                   
                   $role = trim($userDetails['role']);
                    settype($role,"integer");            //echo $role;echo '<br/><br/>';
                   $region_c_id  = $userDetails['region_c_id'];
                   // echo '<br/>'.$region_c_id;echo ': '.$role.'';
                   $province_id = $userDetails['province_id'];
                   $district_id = $userDetails['district_id'];
                   $firstName = $userDetails['first_name'];
                   $lastName  = $userDetails['last_name'];
                   $fullName = $firstName." ".$lastName;
                   $username = $userDetails['username'];
                   $email = trim($userDetails['email']);
                   settype($email,"string");
                   $partnerStates = array();
                   if($role=="3"){
                       if(!empty($userDetails['multiple_locations_id']) && $userDetails['multiple_locations_id']!=""){
                   $multiple_locationsArray = json_decode($userDetails['multiple_locations_id'],true);
                  
                   $partnerStates = $report->explodeGeogArray($multiple_locationsArray, "2");
                       }
                   }
                   
                   
                 
                   //$emailArray = array("john.ojetunde@techieplanetltd.com");
                   
                   
                   if($role=="1" || $role==1){
                       $roleName = "Administrator";
                       $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"User is an administrator");
                       echo $message;
                       
                   }
                   else if($role=="2" || $role==2){
                       //fmoh 
                       //$allStates = $helper2->getLocationTierIDs(2);
                       $parentIds = array();
                       $province_id = $userDetails['province_id'];
                       $parentIds[] = $province_id;
                       $parentId = implode(",",$parentIds);
                       $allStates = array();
                      // $allStates = $helper2->fetchlocwithparentid($parentId);
                      $allStates[] = 0;
                           $roleName = "FMOH";
                       
                      
                       $stateIds = implode(",",$allStates);
                       if(!empty($allStates)){
                           
                           $fileAttachmentPaths = $pdf->fetchFileToBeAttached($stateIds,$lastPulledDate);
                    //echo $fileAttachmentPaths;
                     $result =  $this->sendMail($fileAttachmentPaths,$email,$month,$year,$firstName,$lastName,"fmoh");
                     //$result = false; 
                     if($result){
                       $message = $this->responseMessage($fullName,$username,$email,$roleName,true,"");
                      
                      } else{
                      $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"Server Mailing Function Error");
                      }   
                      

                       }else{
                            $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"Coverage area unavailable");
                       }
                       echo $message;
                   }
                   
                   else if($role=="3" || $role==3 ){
                       
                       //partner mails
                       $allStates = $partnerStates;
                       $roleName = "Partner";
                       
                    
                       if(!empty($partnerStates) ){
                       $stateIds = implode(",",$partnerStates);
                       
                       if(!empty($allStates)){
                           $fileAttachmentPaths = $pdf->fetchFileToBeAttached($stateIds,$lastPulledDate);
                    
                       $result = $this->sendMail($fileAttachmentPaths,$email,$month,$year,$firstName,$lastName,"Partner");
                       //$result = false; 
                           if($result){
                         $message = $this->responseMessage($fullName,$username,$email,$roleName,true,"");
                        } else{
                        $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"Server Mailing Function Error");
                        }                    
                       }
                       
                   }else{
                       
                        $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"Coverage area unavailable");
                   }
                   echo $message;
                   }
                   else if($role=="4" || $role==4){
                       //state mails
                       $district_ids = array();
                       $district_id = $userDetails['district_id'];
                        array_push($district_ids,$district_id);
                       //print_r($district_ids);exit;
                        $roleName = "State";
                       if(!empty($district_id) || $district_id!=0){
                       $stateIds = implode(",",$district_ids);
                       
                      $fileAttachmentPaths = $pdf->fetchFileToBeAttached($stateIds,$lastPulledDate);
                      //print_r($fileAttachmentPaths);
                    $result =   $this->sendMail($fileAttachmentPaths,$email,$month,$year,$firstName,$lastName,"state");
                    //$result = false; 
                      if($result){
                   $message = $this->responseMessage($fullName,$username,$email,$roleName,true,"");
                        }                  
                        else{
                   $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"Server Mailing Function Error");
                        }   
                       }else{
                        $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"Coverage area unavailable");   
                       }
                      echo $message;
                   }
                   else if($role==5 || $role=="5"){
                       //lga mails
                    
                       $region_c_ids = array();
                       $region_c_id = $userDetails['region_c_id'];
                       array_push($region_c_ids,$region_c_id);
                       $roleName = "LGA";
                       
                        if(!empty($region_c_ids) || $region_c_id!=0){
                           
                       $lgaIds = implode(",",$region_c_ids);
                        
                      $fileAttachmentPaths = $pdf->fetchFileToBeAttached($lgaIds,$lastPulledDate);
                         $result = $this->sendMail($fileAttachmentPaths,$email,$month,$year,$firstName,$lastName,"lga");
                         //$result = false; 
                        
                      if($result){
                      
                         $message = $this->responseMessage($fullName,$username,$email,$roleName,true,"");
}                        else{
    
                        $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"Server Mailing Function Error");

                        
}   
                       }else{
                            
                          $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"Coverage area unavailable");
                    
                       } 
                       echo $message;
                   }else{
                    $message = $this->responseMessage($fullName,$username,$email,$roleName,false,"no role is attached to this user");
                      
                   
               }
               
               }  
      
                
        
    }
    public function sendMail($files,$email,$month,$year,$firstName,$lastName,$category){
        try {
						$mail = new Zend_Mail ( );
                                                
                                                //$file = file_get_contents($file);
                                              foreach($files as $file){
                                               $at = new Zend_Mime_Part(file_get_contents($file));
                                               $at->filename = basename($file);
                                               $at->type = 'application/pdf';
                                               $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                                               $at->encoding = Zend_Mime::ENCODING_BASE64;
                                               $mail->addAttachment($at);
                                              }
                                               $sizeof = sizeof($files); 
                                                list($text,$html) = $this->textHtml($firstName,$lastName,$month,$year,$sizeof,$category);
						
                                                $mail->setBodyText ( $text );
						$mail->setBodyHtml ( $html );
						$mail->setFrom ( "no-reply@fpdashboard.ng", "Family Planning Dashboard" );
						$mail->addTo ( $email, $firstName.' '.$lastName  );
						$mail->setSubject ( 'PDF Report for '.$month.','.$year.'' );
						$mail->send ();
                                                return true;
					} catch (Exception $e) {
                                           return false;
					}

    }
   
    public function textHtml($firstName,$lastName,$month,$year,$sizeof,$category){
        if($sizeof>1){
            $word = "are Pdf Reports for $month,$year";
        }else{
            $word = "is Pdf Report for $month,$year";
        }
        $text = "";
        $html = "<html><head></head><body>";
        $html .= 'Dear '.$firstName.' '.$lastName.',<br/><br/> 
        Your monthly family planning report is attached. Please review it and complete any necessary action items. For more information, log on to the Family Planning Dashboard at <a href="http://fpdashboard.ng/">http://fpdashboard.ng/</a>.<br/><br/>
        
';
        if($category=="state"){
        $html.= "<p>Don't forget to:</p>
            <ul>
<li>	Input any new training information into the FP Dashboard this month.</li>
<li>	Update any changes to the location of trained health workers.</li>
</ul>
<br/><br/>";
        }
        $html.="Forgot your password?&nbsp;You can retrieve it by navigating to the log-in page of the Dashboard, clicking the <a href='http://fpdashboard.ng/user/forgotPassword'>Forgot your password?</a> link, and following the instructions. For further assistance with the FP Dashboard, please contact: <a href='mailto:support@fpdashboard.ng'>support@fpdashboard.ng</a>.";
        $html.="<br/><br/>Sincerely,<br/> 
Director of Family Health Division, FMOH";

        $html .= "</body></html>";
        $text = $html;
        return array($text,$html);
    }
    public function archivedreportsAction () {
        $this->_countrySettings = array();
        
         $request = $this->getRequest()->getMethod();
if($this->getSanParam('go')){
  if($request!="POST"){
      $this->_redirect('pdf/archivedreports');
      //$this->_forward ( 'facilitysummary' );
      
  }  
}
		$this->_countrySettings = System::getAll();
                $report = new Report();
                $pdf = new PDF();
		$this->view->assign ( 'mode', 'search' );
                //$reports = new Report();
		require_once ('models/table/TrainingLocation.php');
		require_once('views/helpers/TrainingViewHelper.php');
                $helper2 = new Helper2();
               if ( $this->getSanParam('download') ){
                 
                    $fileId = $this->getSanParam('fileId');
         
			return $this->downloadPdfFile($fileId);
               }
                $lastpulled_date = $helper2->getLatestPullDate();
		$criteria = array ();
		$where = array ();
		
		
               $output = array();
               
                $locations = Location::getAll("2");
                $minimumDate = "";
               //print_r($locations); exit;
		$this->viewAssignEscaped('locations', $locations);
		//course
                //print_r($locations);exit;
                //exit;
               // $date = "2015-02-01";
		//$pdf->deletePdfReportsData($date);
              $leastMonth = $pdf->leastMonth();
             $minDate = $leastMonth[0]['mindate'];
             $minDateArray = explode("-",$minDate);
             $minYear = $minDateArray[0];
             $minMonth = $minDateArray[1];
             $minDay = $minDateArray[2];
             $error = array();
                 //-----------------------------------------the query is here now.----------------------------------------------//
                $zone = $this->getSanParam('province_id');
                $state  = $this->getSanParam('district_id');
                $localgovernment  = $this->getSanParam('region_c_id');
                
                $startDateValue = $this->getSanParam('StartDate');
                $endDateValue = $this->getSanParam('EndDate');
                $headers = "";
                $outputs = "";
                
                $genDatesStart = array();
                $genDatesEnd = array();
                
                $genDatesStart = explode("/", $startDateValue);
                $genDatesEnd = explode("/",$endDateValue);
                
                $startDay = "";
                $startMonth = "";
                $startYear = "";
                
                if(isset($genDatesStart[1])){
                    $startDay = $report->check_length_add_one($genDatesStart[1]);
                }
                
                if(isset($genDatesStart[0])){
                    $startMonth = $report->check_length_add_one($genDatesStart[0]);
                }
                
                if(isset($genDatesEnd[2])){
                    $startYear = $genDatesStart[2];
                }
                
//                $startMonth = $report->check_length_add_one($genDatesStart[0]);
//                $startYear = $genDatesStart[2];
               
                $endDay = "";
                $endMonth = "";
                $endYear = "";
                
                 if(isset($genDatesEnd[1])){
                    $endDay = $report->check_length_add_one($genDatesEnd[1]);
                }
                
                if(isset($genDatesEnd[0])){
                    $endMonth = $report->check_length_add_one($genDatesEnd[0]);
                }
                
                if(isset($genDatesEnd[2])){
                    $endYear = $genDatesEnd[2];
                }
                
//                $endDay = $report->check_length_add_one($genDatesEnd[1]);
//                $endMonth = $report->check_length_add_one($genDatesEnd[0]);
//                $endYear = $genDatesEnd[2];
                
                /*
                $startDay = $report->check_length_add_one($this->getSanParam('start-day'));
                $startMonth = $report->check_length_add_one($this->getSanParam('start-month'));
                $startYear = $this->getSanParam('start-year');
                
                $endDay = $report->check_length_add_one($this->getSanParam('end-day'));
                $endMonth = $report->check_length_add_one($this->getSanParam('end-month'));
                $endYear = $this->getSanParam('end-year');
                */
                $nationalReport = $this->getSanParam('national_report');
                
                $current_month = date('m');
                $currentDay = date('d');
                $currentYear = date('Y');
                 //echo $agrregate_method;exit;
                 if($startYear==""){
                        $startYear = $minYear;
                    }
                    if($startDay==""){
                       $startDay = $minDay;
                    }
                  if($startMonth==""){
                      $startMonth = $minMonth;
                    }
                   if($endYear==""){
                        $endYear = $currentYear;
                    }
                   if($endDay==""){
                       $endDay = $currentDay;
                    }
                  if($endMonth==""){
                      $endMonth = $current_month;
                   }
     
      
       $startDate = $startYear.'-'.$startMonth.'-'.$startDay;
      $endDate = $endYear.'-'.$endMonth.'-'.$endDay;
      
                $locationsArray = array();
              
                
                if(!empty($localgovernment)){
                foreach($localgovernment as $state){
                  $state_gen =   explode("_",$state);
                  $parent_id = $state_gen[0];
                $district = $state_gen[1];
                $location_id = $state_gen[2];
                if($location_id!=""){
                array_push($locationsArray,$location_id);
                }
                }
                $filter = "location_id";
                //echo 'na here i dey';
}else if(!empty($state)){
 // echo 'hi';
    foreach($state as $states){
                  $state_gen =   explode("_",$states);
                  $parent_id = $state_gen[0];
                $location_id = $state_gen[1];
                if($location_id!=""){
                array_push($locationsArray,$location_id);
                }
                }
                 
                $filter = "parent_id";
}


else{
   unset($locationsArray);
}


if( $nationalReport=="0"){
                   
                    $location_id = "0";
                  $locationsArray[] = $location_id; 
                }
if(!isset($locationsArray) || empty($locationsArray)){
    $error[] = "Please select National Report or at least one State or LGA";
}else {

$locationsImplode = implode(',',$locationsArray);
$achivedReportsArray = array();
$achivedReportsArray = $pdf->getReportsWithLocationDate($locationsImplode,$startDate,$endDate);
$achivedReports = $pdf->reformatArchivedSearchData($achivedReportsArray);
//print_r($achivedReports);
$headers = array("Location Name","Date","Date Generated","Download");
$outputs = array();
foreach($achivedReports as $archived){
    $output = array();
    $output[] = $archived['location_name'];
   
    $timestamp = strtotime($archived['date']);
    $output[] =  date("F Y",$timestamp);
    $output[] = $archived['date_generated'];
    $fileId = $archived['id'];
    //echo $this->base_url;exit;
    $link = '<a href="archivedreports/download/template/fileId/'.$fileId.'">Download</a>';
     $output[] = $link;
    
   array_push($outputs,$output);
    
}
    }


 $criteria['error'] = $error;
 $criteria['national_report'] = $this->getSanParam('national_report');
 $defaultStartDate = $startMonth."/".$startDay."/".$startYear;
 $defaultEndDate = $endMonth."/".$endDay."/".$endYear;
 $criteria['province_id'] = $this->getSanParam('province_id');
 $criteria['district_id'] = $this->getSanParam('district_id');
 $criteria['region_c_id'] = $this->getSanParam('region_c_id');
 $criteria['StartDate'] = ($this->getSanParam('StartDate')!="")?$startDateValue:$defaultStartDate;
 $criteria['EndDate'] = ($this->getSanParam('EndDate')!="")?$endDateValue:$defaultEndDate;
 
$this->view->assign('criteria',$criteria);
$this->view->assign('headers',$headers);
$this->view->assign('outputs',$outputs);
    }
}