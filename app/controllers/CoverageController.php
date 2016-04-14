<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CoverageController
 *
 * @author Swedge
 */
require_once ('ReportFilterHelpers.php');
require_once ('models/table/Helper2.php');
require_once('models/table/Coverage.php');
class CoverageController extends ReportFilterHelpers {
    
    public function init(){
        $burl = Settings::$COUNTRY_BASE_URL;
        if (substr($burl, -1) != '/' && substr($burl, -1) != '\\')
            $this->baseUrl = $burl . '/';
    }
    
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

    public function cummhwtraineddemoAction(){
         $coverage = new Coverage();
            $helper = new Helper2();
            
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
                
            //If no GEO selection made 
	    if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $cumm_data = $coverage->fetchCummulativeTrainedWorkers(3, $geoList, $tierValue);
                $this->view->assign('cumm_data', $cumm_data);
	    }
            else{
                $cumm_data = $coverage->fetchCummulativeTrainedWorkers(3, $geoList, $tierValue);
                $this->view->assign('cumm_data', $cumm_data);
                //var_dump($cumm_data); exit;
                
                $locationNames = $helper->getLocationNames($geoList);
                $larc_cumm_location = $coverage->fetchCummulativeTrainedWorkersByLocation('larc',3, $geoList, $tierValue);
                $fp_cumm_location= $coverage->fetchCummulativeTrainedWorkersByLocation('fp',3, $geoList, $tierValue);
                
                $this->view->assign('fp_cumm_location', $fp_cumm_location);
                $this->view->assign('larc_cumm_location', $larc_cumm_location);
                $this->view->assign('cumm_locations', $helper->getLocationNames($geoList));
            }
                
            //GNR:use max commodity date
            $sDate = $helper->fetchTitleDate();
            $this->view->assign('tp_date', $sDate['month_name'] . ', '.$sDate['year']);
            
            $this->view->assign('base_url', $this->baseUrl);
            
            //locations
            $this->view->assign('cumm_data', $cumm_data);
            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
            $this->viewAssignEscaped ('locations', Location::getAll(1));
    }
    //TP: rewrote most part of this method 2/4/2015
	public function cummhwtrainedAction() {
            $coverage = new Coverage();
            $helper = new Helper2();
            
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
                
            //If no GEO selection made 
	    if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $cumm_data = $coverage->fetchCummulativeTrainedWorkers(3, $geoList, $tierValue);
                $this->view->assign('cumm_data', $cumm_data);
	    }
            else{
                $cumm_data = $coverage->fetchCummulativeTrainedWorkers(3, $geoList, $tierValue);
                $this->view->assign('cumm_data', $cumm_data);
                //var_dump($cumm_data); exit;
                
                $locationNames = $helper->getLocationNames($geoList);
                $larc_cumm_location = $coverage->fetchCummulativeTrainedWorkersByLocation('larc',3, $geoList, $tierValue);
                $fp_cumm_location= $coverage->fetchCummulativeTrainedWorkersByLocation('fp',3, $geoList, $tierValue);
                
                $this->view->assign('fp_cumm_location', $fp_cumm_location);
                $this->view->assign('larc_cumm_location', $larc_cumm_location);
                $this->view->assign('cumm_locations', $helper->getLocationNames($geoList));
            }
                
            //GNR:use max commodity date
            $sDate = $helper->fetchTitleDate();
            $this->view->assign('tp_date', $sDate['month_name'] . ' '.$sDate['year']);
            
            $this->view->assign('base_url', $this->baseUrl);            

            //locations
            $this->view->assign('cumm_data', $cumm_data);
            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
            $this->viewAssignEscaped ('locations', Location::getAll(1));


              
    }
    
    
    public function facswithhwprovidingAction() {
	    $coverage = new Coverage();
	    $helper = new Helper2();
            
            //$this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));
	     
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
            
            //set date limit
            //$dateWhere = 'c.date = \'' . $helper->getLatestPullDate() . '\'';
            //$dateWhere = $helper->getLatestPullDate();
            
            //get the location names
            //$locationNames = $helper->getLocationNames($geoList);
            
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $fp_coverage = $coverage->fetchFacsWithHWProviding('fp', 'fp', $geoList, $tierValue, true);
                $larc_coverage = $coverage->fetchFacsWithHWProviding('larc', 'larc', $geoList, $tierValue, true);
            }
            else{
                $fp_coverage = $coverage->fetchFacsWithHWProviding('fp', 'fp', $geoList, $tierValue, false);
                $larc_coverage = $coverage->fetchFacsWithHWProviding('larc', 'larc', $geoList, $tierValue, false);
            }
            
            $this->view->assign('fp_data',$fp_coverage);
            $this->view->assign('larc_data',$larc_coverage);
            	
	    //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
	    //GNR:use max commodity date
	    $sDate = $helper->fetchTitleDate();
	    $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 
	    
            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
	    $this->viewAssignEscaped ('locations', Location::getAll(1) );
	      
            $this->view->assign('base_url', $this->baseUrl);
            
	} //dashAction13
        
        
        public function percentfacsprovidingAction() {
            $coverage = new Coverage();
            $helper = new Helper2();
	    
	    //$this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));
            
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();

            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $fp_coverage = $coverage->fetchPercentFacsProviding('fp', $geoList, $tierValue, true);
                $larc_coverage = $coverage->fetchPercentFacsProviding('larc', $geoList, $tierValue, true);
                $inj_coverage = $coverage->fetchPercentFacsProviding('injectables', $geoList, $tierValue, true);
            }
            else{
                $fp_coverage = $coverage->fetchPercentFacsProviding('fp', $geoList, $tierValue, false);
                $larc_coverage = $coverage->fetchPercentFacsProviding('larc', $geoList, $tierValue, false);
                $inj_coverage = $coverage->fetchPercentFacsProviding('injectables', $geoList, $tierValue, false);
            }           


            $this->view->assign('fp_data',$fp_coverage);
            $this->view->assign('larc_data',$larc_coverage);
            $this->view->assign('inj_data',$inj_coverage);

            //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
            //GNR:use max commodity date
            $sDate = $helper->fetchTitleDate();
            $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 

            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
	    $this->viewAssignEscaped('locations', Location::getAll(1));

            $this->view->assign('base_url', $this->baseUrl);
       }
   
   
   
        
        public function percentfacswithtrainedhwAction() {
            $coverage = new Coverage();
            $helper = new Helper2();
	    
            //$ids = $helper->getTierLocationsIds(1); var_dump($ids); exit;
            
	    //$this->view->assign('title', $this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));
            
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();                
	    
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $fp_coverage = $coverage->fetchPercentFacHWTrained('fp', $geoList, $tierValue, true);
                $larc_coverage = $coverage->fetchPercentFacHWTrained('larc', $geoList, $tierValue, true);
            }
            else{
                $fp_coverage = $coverage->fetchPercentFacHWTrained('fp', $geoList, $tierValue, false);
                $larc_coverage = $coverage->fetchPercentFacHWTrained('larc', $geoList, $tierValue, false);
            }
            
//            var_dump($fp_coverage);
//            echo '<br/><br/>';
//            var_dump($larc_coverage); exit;
            
            $this->view->assign('fp_data',$fp_coverage);
            $this->view->assign('larc_data',$larc_coverage);
            
            //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
	    //GNR:use max commodity date
	    //$tDate = new DashboardCHAI();
	    $sDate = $helper->fetchTitleDate();
	    $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']);
	   
//            $zone = $this->getSanParam('province_id');
//            $state  = $this->getSanParam('district_id');
//            $localgovernment  = $this->getSanParam('region_c_id');
            
//            $criteria = array();
//            $criteria['province_id'] = $zone;
//            $criteria['district_id'] = $state;
//            $criteria['region_c_id'] = $localgovernment;
//            $criteria['error'] = $error;
//            
//            $this->viewAssignEscaped('criteria',$criteria);            
            
            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
	    $this->viewAssignEscaped('locations', Location::getAll(1));

            $this->view->assign('base_url', $this->baseUrl);$this->view->assign('base_url', $this->baseUrl);
	}
        
        
        function coverageovertimeAction(){
            $helper = new Helper2();
            $coverage = new Coverage();
            $freshVisit = true;
            //$this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));
            
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
            
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $fp_overtime = $coverage->fetchHWCoverageOvertime('fp', $geoList, $tierValue, true);
                $larc_overtime = $coverage->fetchHWCoverageOvertime('larc', $geoList, $tierValue, true);
            }
            else { 
                $fp_overtime = $coverage->fetchHWCoverageOvertime('fp', $geoList, $tierValue, false);
                $larc_overtime = $coverage->fetchHWCoverageOvertime('larc', $geoList, $tierValue, false);
                $freshVisit = false;
            }
            
            $locationName = $helper->getLocationNames($geoList);
            
            $this->view->assign('fp_overtime', $fp_overtime); 
            $this->view->assign('larc_overtime', $larc_overtime); 

            //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
            //GNR:use max commodity date
            $sDate = $helper->fetchTitleDate();
            $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 

            $overTimeDates = $helper->getPreviousMonthDates(12);
            $this->view->assign('start_date', date('F', strtotime($overTimeDates[11])). ' '. date('Y', strtotime($overTimeDates[11]))); 
            $this->view->assign('end_date', date('F', strtotime($overTimeDates[0])). ' '. date('Y', strtotime($overTimeDates[0]))); 
            $this->view->assign('freshvisit', $freshVisit); 
            
            reset($locationName); $key = key($locationName);  $location_name = $locationName[$key];
            $this->view->assign('location_name', $location_name);
            
            $this->view->assign('base_url', $this->baseUrl);
            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
            $this->viewAssignEscaped('locations', Location::getAll(1) );
        }
        
        
        function providingovertimeAction(){
            $helper = new Helper2();
            $coverage = new Coverage();
            //$this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));

            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
            
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $fp_overtime = $coverage->fetchProvidingOvertime('fp', $geoList, $tierValue, true);
                $larc_overtime = $coverage->fetchProvidingOvertime('larc', $geoList, $tierValue, true);
            }
            else {
                $fp_overtime = $coverage->fetchProvidingOvertime('fp', $geoList, $tierValue, false);
                $larc_overtime = $coverage->fetchProvidingOvertime('larc', $geoList, $tierValue, false);
            }
            
            $this->view->assign('fp_overtime', $fp_overtime); 
            $this->view->assign('larc_overtime', $larc_overtime); 
            
            //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
            //GNR:use max commodity date
            $sDate = $helper->fetchTitleDate();
            $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 

            $overTimeDates = $helper->getPreviousMonthDates(12);
            $this->view->assign('start_date', date('F', strtotime($overTimeDates[0])). ' '. date('Y', strtotime($overTimeDates[0]))); 
            $this->view->assign('end_date', date('F', strtotime($overTimeDates[11])). ' '. date('Y', strtotime($overTimeDates[11]))); 

            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
            $this->viewAssignEscaped ('locations', Location::getAll(1) );
            
            $this->view->assign('base_url', $this->baseUrl);
        }
        
         
        function providingovertimedemoAction(){
            $helper = new Helper2();
            $coverage = new Coverage();
            //$this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));

            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
            
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $fp_overtime = $coverage->fetchProvidingOvertime('fp', $geoList, $tierValue, true);
                $larc_overtime = $coverage->fetchProvidingOvertime('larc', $geoList, $tierValue, true);
            }
            else {
                $fp_overtime = $coverage->fetchProvidingOvertime('fp', $geoList, $tierValue, false);
                $larc_overtime = $coverage->fetchProvidingOvertime('larc', $geoList, $tierValue, false);
            }
            
            $this->view->assign('fp_overtime', $fp_overtime); 
            $this->view->assign('larc_overtime', $larc_overtime); 
            
            //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
            //GNR:use max commodity date
            $sDate = $helper->fetchTitleDate();
            $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 

            $overTimeDates = $helper->getPreviousMonthDates(12);
            $this->view->assign('start_date', date('F', strtotime($overTimeDates[0])). ' '. date('Y', strtotime($overTimeDates[0]))); 
            $this->view->assign('end_date', date('F', strtotime($overTimeDates[11])). ' '. date('Y', strtotime($overTimeDates[11]))); 

            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
            $this->viewAssignEscaped ('locations', Location::getAll(1) );
            
            $this->view->assign('base_url', $this->baseUrl);
        }
        
        public function updatecacheAction(){
            echo 'starting updates';
            $coverage = new Coverage();
            $stockout = new Stockout();
            
            $coverage->updateCache();
            $stockout->updateCache();
            
            echo 'done updating';
        }
}

?>