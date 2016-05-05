<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CacheController
 *
 * @author Swedge
 */
require_once ('ReportFilterHelpers.php');
require_once ('models/table/Helper2.php');
require_once('models/table/Coverage.php');
require_once('models/table/Dashboard.php');

class CacheController  extends ReportFilterHelpers {
    //put your code here
    
    public function setcacheAction(){
        $cacheManager = new CacheManager();
        $helper = new Helper2();
         
        $tierValue = 1;
        $geoIDsArray = $helper->getLocationTierIDs($tierValue);
        foreach($geoIDsArray as $key=>$geoid)
           $geoIDsArray[$key] = "'$geoid'";

        $geoList = implode(',', $geoIDsArray);
        
        $dashboard = new Dashboard();
        $dashboard->fetchConsumptionByMethod();  //landing page 1
        $dashboard->fetchCoverageSummary($geoList, $tierValue);     //landing page 2
        
        //landiing page 3
        $dashboard->fetchFacsProviding('fp');
        $dashboard->fetchFacsProviding('larc');
        
        //landing page 4
        $dashboard->fetchFacsProvidingStockedoutOvertime('fp', $geoList, $tierValue, true);
        $dashboard->fetchFacsProvidingStockedoutOvertime('larc', $geoList, $tierValue, true);
        
        
        /*************************  COVERAGE ***********************/
        $coverage = new Coverage();
        
        //Trained HWs - no need
        
        //Facilities with Trained HWs Providing FP
        $coverage->fetchFacsWithHWProviding('fp', 'fp', $geoList, $tierValue, true, false);
        $coverage->fetchFacsWithHWProviding('larc', 'larc', $geoList, $tierValue, true, false);
        
        //Facilities Providing FP
        $coverage->fetchPercentFacsProviding('fp', $geoList, $tierValue, true, false);
        $coverage->fetchPercentFacsProviding('larc', $geoList, $tierValue, true, false);
        $coverage->fetchPercentFacsProviding('injectables', $geoList, $tierValue, true, false);
        
        //Facilities with Trained HWs
        $coverage->fetchPercentFacHWTrained('fp', $geoList, $tierValue, true, false);
        $coverage->fetchPercentFacHWTrained('larc', $geoList, $tierValue, true, false);
        
        //Facilities with Trained HWs Providing FP Over Time
        $coverage->fetchHWCoverageOvertime('fp', $geoList, $tierValue, true, false);
        $coverage->fetchHWCoverageOvertime('larc', $geoList, $tierValue, true, false);
        
        //Facilities Providing FP Over Time
        $coverage->fetchProvidingOvertime('fp', $geoList, $tierValue, true);
        $coverage->fetchProvidingOvertime('larc', $geoList, $tierValue, true);        
         
         
        //facswithhwproviding
        $this->fetchFacsWithHWProviding('fp', 'fp', $geoList, $tierValue, false, true );
        $this->fetchFacsWithHWProviding('larc', 'larc', $geoList, $tierValue, false, true );
            
        //coverageovertime
        $this->fetchHWCoverageOvertime('fp', $geoList, $tierValue, false, true);
        $this->fetchHWCoverageOvertime('larc', $geoList, $tierValue, false, true); 
        
        
        
        /*************************  CONSUMPTION ***********************/
        
        
        /*************************  STOCKOUTS ***********************/
        $stockout = new Stockout();
        
        //Stock Outs at Facilities with Trained HWs
        $stockout->fetchPercentStockOutFacsWithTrainedHW('fp', $geoList, $tierValue, true, false);
        $stockout->fetchPercentStockOutFacsWithTrainedHW('larc', $geoList, $tierValue, true, false);
        
        //Stock Outs at Facilities Providing FP
        $stockout->fetchPercentFacsProvidingButStockedOut('fp', $geoList, $tierValue, true, false);
        $stockout->fetchPercentFacsProvidingButStockedOut('larc', $geoList, $tierValue, true, false);
        
        //Stock Outs at Facilities Providing FP Over Time
        $dashboard->fetchFacsProvidingStockedoutOvertime('fp', $geoList, $tierValue, true);
        $dashboard->fetchFacsProvidingStockedoutOvertime('larc', $geoList, $tierValue, true);
    }
}
