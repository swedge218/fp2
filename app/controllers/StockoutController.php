<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of StockoutController
 *
 * @author Swedge
 */
require_once ('ReportFilterHelpers.php');
require_once ('models/table/Helper2.php');
require_once('models/table/Stockout.php');

require_once('models/table/Dashboard.php');

class StockoutController extends ReportFilterHelpers {
    //put your code here
    public function init(){
        $burl = Settings::$COUNTRY_BASE_URL;
        if (substr($burl, -1) != '/' && substr($burl, -1) != '\\')
            $this->baseUrl = $burl . '/';
    }
    
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
    
    public function percentStockoutWithTrainedHWAction() {
	    $stockout = new Stockout();
	    $helper = new Helper2();
            
            $this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));
	     
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
            
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $fp_stockout = $stockout->fetchPercentStockOutFacsWithTrainedHW('fp', $geoList, $tierValue, true);
                $larc_stockout = $stockout->fetchPercentStockOutFacsWithTrainedHW('larc', $geoList, $tierValue, true);
            }
            else{
                $fp_stockout = $stockout->fetchPercentStockOutFacsWithTrainedHW('fp', $geoList, $tierValue, false);
                $larc_stockout = $stockout->fetchPercentStockOutFacsWithTrainedHW('larc', $geoList, $tierValue, false);
            }
            
            $this->view->assign('larc_data', $larc_stockout);
	    $this->view->assign('fp_data', $fp_stockout);
	    
	    //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
	    //GNR:use max commodity date	    
	    $sDate = $helper->fetchTitleDate();
	    $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 
	    
            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
	    $this->viewAssignEscaped ('locations', Location::getAll(1));
	
            $this->view->assign('base_url', $this->baseUrl);$this->view->assign('base_url', $this->baseUrl);
	
	} //dashAction15
        
        
        public function percentFacsProvidingButStockedoutAction(){
            $stockout = new Stockout();
	    $helper = new Helper2();
            
            $this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));
	     
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
            
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $fp_stockout = $stockout->fetchPercentFacsProvidingButStockedOut('fp', $geoList, $tierValue, true);
                $larc_stockout = $stockout->fetchPercentFacsProvidingButStockedOut('larc', $geoList, $tierValue, true);
            }
            else{
                $fp_stockout = $stockout->fetchPercentFacsProvidingButStockedOut('fp', $geoList, $tierValue, false);
                $larc_stockout = $stockout->fetchPercentFacsProvidingButStockedOut('larc', $geoList, $tierValue, false);
            }
            
            $this->view->assign('larc_data', $larc_stockout);
	    $this->view->assign('fp_data', $fp_stockout);
	    
	    //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
	    //GNR:use max commodity date	    
	    $sDate = $helper->fetchTitleDate();
	    $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 
	    
            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
	    $this->viewAssignEscaped ('locations', Location::getAll(1));
            
            $this->view->assign('base_url', $this->baseUrl);$this->view->assign('base_url', $this->baseUrl);
        }
        
        public function stockoutsAction(){
            $dashboard = new Dashboard();
            $helper = new Helper2();
            
            //get the parameters
            list($geoList, $tierValue) = $this->buildParameters();
            
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                //$facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedout($geoList, $tierValue, true);
                $fp_facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedoutOvertime('fp', $geoList, $tierValue, true);
                $larc_facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedoutOvertime('larc', $geoList, $tierValue, true);
                $freshVisit = true;
            }
            else{
                //$facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedout($geoList, $tierValue, false);
                $fp_facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedoutOvertime('fp', $geoList, $tierValue, false);
                $larc_facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedoutOvertime('larc', $geoList, $tierValue, false);
                $freshVisit = false;
            }
                
            //$this->view->assign('facs_providing_stockedout', $facsProvidingStockedout);
            $this->view->assign('fp_facs_providing_stockedout', $fp_facsProvidingStockedout);
            $this->view->assign('larc_facs_providing_stockedout', $larc_facsProvidingStockedout);
            
            $title_date = $helper->fetchTitleDate();
            $this->view->assign('title_date', $title_date['month_name'] . ' ' . $title_date['year']);
            
            $overTimeDates = $helper->getPreviousMonthDates(12);
            $this->view->assign('end_date', date('F', strtotime($overTimeDates[0])). ' ' . date('Y', strtotime($overTimeDates[0]))); 
            $this->view->assign('start_date', date('F', strtotime($overTimeDates[11])) . ' ' . date('Y', strtotime($overTimeDates[11])));
            
            $locationName = $helper->getLocationNames($geoList);            
            reset($locationName); $key = key($locationName);  $location_name = $locationName[$key];
            $this->view->assign('location_name', $location_name); 
            $this->view->assign('freshvisit', $freshVisit); 
            
            $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
            $this->viewAssignEscaped ('locations', Location::getAll(1));
            
            $this->view->assign('base_url', $this->baseUrl);$this->view->assign('base_url', $this->baseUrl);
        }
        
}

?>
