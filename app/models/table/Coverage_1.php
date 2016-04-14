<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Coverage
 *
 * @author Swedge
 */
require_once('Facility.php');
require_once('Helper2.php');
require_once('CoverageHelper.php');
require_once('CoverageNationalHelper.php');
require_once('Stockout.php');
require_once 'CacheManager.php';

class Coverage {
    //put your code here
    
    
    /*
     * TA:17:17: 01/15/2015
     * get trained persons details
     DB query to take number of HW trained in �LARC� in 2014

     select count(distinct person_to_training.person_id) from person_to_training
     left join training on training.id = person_to_training.training_id
     where training.training_title_option_id=1 and training.training_end_date like '2014%';
     */
    public function fetchCummulativeTrainedWorkers($year_amount, $geoList, $tierValue ) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        $output = array ();
        $helper = new Helper2();
        $trainingTypesArray = array('fp', 'larc');
        
        //get the last DHIS2 pull date from commodity table and use the year for year here
        //$latestDate = $helper->getPreviousMonthDates(1);
        $latestDate = $helper->getLatestPullDate();
        $year = date('Y', strtotime($latestDate));
        
        $tierText = $helper->getLocationTierText($tierValue);
        $tierFieldName = $helper->getTierFieldName($tierText);
                

        for($i = $year_amount; $i > 0; $i--) {
            $data = array ();
            //$endDateWhere = "t.training_end_date like '" . $year . "%'";
            $endDateWhere = "YEAR(t.training_end_date) <= '" . $year . "'";
            
            foreach ($trainingTypesArray as $training_type){
                $trainingTypeWhere = "tto.system_training_type = '" . $training_type . "'";
                $longWhereClause = $endDateWhere . ' AND ' . $trainingTypeWhere . ' AND ' . 
                                   $tierFieldName . ' IN (' . $geoList . ')';
                
                $select = $db->select ()
                        ->from ( array ('p' => 'person' ), array ('COUNT(DISTINCT(p.id)) as count'))
                        ->joinInner(array('ptt'=>'person_to_training'), 'ptt.person_id=p.id', array())
                        ->joinInner(array ('t' => "training" ), "t.id = ptt.training_id", array())
                        ->joinInner(array('tto' => 'training_title_option' ), 'tto.id = t.training_title_option_id', array())
                        ->joinInner(array ('flv' => "facility_location_view" ), 'flv.id = p.facility_id', array('flv.lga', 'flv.state', 'flv.geo_zone') )
                        ->where($longWhereClause)
                        ->order(array($tierText));
                
                //echo $select->__toString(); exit;

                $result = $db->fetchAll ( $select );
                $data = $result [0] ['count'];

                $output[$year][$training_type] = $data;
            }//end inner loop
            
            $year--;
        }//outer loop

        ksort($output); 

        //accamulate data: add previous years to the current year
        $startYear = $year + 1; //set to lowest considered year after the loop above

//        foreach($output as $year => $value){
//            if($year == $startYear) continue;
//            $output[$year]['larc'] = $output[$year]['larc'] + $output[$year-1]['larc'];
//            $output[$year]['fp'] = $output[$year]['fp'] + $output[$year-1]['fp'];
//        }

        //var_dump($output); exit;
        return $output;
}


        /* TP:
         * This method gets the count of coverage of trained workers in various 
         * geo-locations and tiers. Both FP and LARC
         */
        public function fetchCummulativeTrainedWorkersByLocation($training_type, $year_amount, $geoList, $tierValue){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
            $output = array (); 
            $helper = new Helper2();
            
            $tierText = $helper->getLocationTierText($tierValue);
            $tierFieldName = $helper->getTierFieldName($tierText);
            $locationNames = $helper->getLocationNames($geoList);
            //var_dump($locationNames); exit;

            //get the last DHIS2 pull date from commodity table and use the year for year here
            $latestDate = $helper->getLatestPullDate();
            $year = date('Y', strtotime($latestDate));

            $coverageHelper = new CoverageHelper();
            //$larcCoverage = $coverageHelper->getTrainedHWCoverageCount('larc', $year,$year_amount, $locationWhereClause, $locationNames, $groupFieldName, $havingName, $geoList, $tierText);
            //$larcCoverage = $coverageHelper->getTrainedHWByLocationCount('larc', $year, $year_amount, $locationNames, $geoList, $tierText, $tierFieldName);
            //$fpCoverage = $coverageHelper->getTrainedHWByLocationCount('fp', $year, $year_amount, $locationNames, $geoList, $tierText, $tierFieldName);
            
            $coverageByLocation = $coverageHelper->getTrainedHWByLocationCount($training_type, $year, $year_amount, $locationNames, $geoList, $tierText, $tierFieldName);
            return $coverageByLocation;
        }
        
        
        
        public function fetchPercentFacHWTrained($training_type, $geoList, $tierValue, $freshVisit){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $output = array(array('location'=>'National', 'percent'=>0));
                $helper = new Helper2();
                
                $cacheManager = new CacheManager();
            
                $latestDate = $helper->getLatestPullDate();
                if($training_type == 'fp')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_TRAINED_FP, $latestDate);
                else if($training_type == 'larc')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_TRAINED_LARC, $latestDate);
                
                //check if page is just being loaded
                //fresh session, month data already registered
                //just retrieve registered data
                if($cacheValue && $freshVisit){ 
                    $output = json_decode($cacheValue, true);
                }
                else {
                    //needed variables
                    $tierText = $helper->getLocationTierText($tierValue);
                    $tierFieldName = $helper->getTierFieldName($tierText);
                    $latestDate = $helper->getLatestPullDate();

                    //where clauses
                    if($training_type == 'fp')
                        $tt_where = "fptrained > 0";
                    else if($training_type == 'larc')
                        $tt_where = 'larctrained > 0';

                    $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';
                    $longWhereClause = $tt_where . ' AND ' . $locationWhere;

                    $coverageHelper = new CoverageHelper();                
                    $facility = new Facility();
                    
                    $numerators = $coverageHelper->getFacWithTrainedHWCountByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);
                    //$denominators = $coverageHelper->getFacWithTrainedHWCountByLocation($locationWhere, $geoList, $tierText, $tierFieldName);
                    $denominators = $facility->getFacilityCountByLocation($locationWhere, $geoList, $tierText, $tierFieldName);
                    
                    
                    $sumsArray = $helper->sumNumersAndDenoms($numerators, $denominators);
                    $output = array_merge($output, $sumsArray['output']);
                    $output[0]['percent'] = $sumsArray['nationalAvg'];
                    
                    //check if to save month national data
                    if(!$cacheValue && $freshVisit){ //fresh in month
                        //do cache insert
                        if($training_type == 'fp')
                            $alias = CacheManager::PERCENT_FACS_TRAINED_FP;
                        else if($training_type == 'larc')
                            $alias = CacheManager::PERCENT_FACS_TRAINED_LARC;
                        
                        $dataArray = array(
                            'date_cached'=> $latestDate,
                            'indicator' => 'Percent of facilities with a trained HW',
                            'indicator_alias' => $alias,
                            'value' => json_encode($output)
                        );
                        $cacheManager->setIndicator($dataArray);
                    }
                    else{
                        //get month national data and put in first array element
                        $cacheValue = json_decode($cacheValue, true);
                        if($cacheValue)
                            $output[0]['percent'] = $cacheValue[0]['percent'];
                    }
                }
                
                //var_dump($output); exit;
                return $output;
        }
        
        
        
        
        public function fetchPercentFacHWTrainedPerLocation($training_type,$tierValue,$geoList){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $output = array(array('location'=>'National', 'percent'=>0));
                $helper = new Helper2();
                
               
                $freshVisit = false;
                $locations = $geoList;
            $latestDate = $helper->getLatestPullDate();
            if(!isset($tierValue) || $tierValue==""){
            $tierValue = 2;
            }
           
            if(!isset($geoList) || $geoList==""){
                $geoList = array();
            $geoList = $helper->getLocationTierIDs($tierValue);
           $geoList = implode(',',$geoList);
            }else{
                
                $geoList = $locations;
            }
                $cacheManager = new CacheManager();
            
            
                if($training_type == 'fp')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_TRAINED_FP, $latestDate);
                else if($training_type == 'larc')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_TRAINED_LARC, $latestDate);
                
                //needed variables
                $tierText = $helper->getLocationTierText($tierValue);
                $tierFieldName = $helper->getTierFieldName($tierText);
                $latestDate = $helper->getLatestPullDate();

                //where clauses
                if($training_type == 'fp')
                    $tt_where = "fptrained > 0";
                else if($training_type == 'larc')
                    $tt_where = 'larctrained > 0';

                $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';
                $longWhereClause = $tt_where . ' AND ' . $locationWhere;

                $coverageHelper = new CoverageHelper();                
                $facility = new Facility();

                $numerators = $coverageHelper->getFacWithTrainedHWCountByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);
                //$denominators = $coverageHelper->getFacWithTrainedHWCountByLocation($locationWhere, $geoList, $tierText, $tierFieldName);
                $denominators = $facility->getFacilityCountByLocation($locationWhere, $geoList, $tierText, $tierFieldName);
                    
                    
                $sumsArray = $helper->sumNumersAndDenoms($numerators, $denominators);
                
                //$arrayToSort = array_slice($sumsArray['output'], 1);
                $sortedArray = $helper->msort($sumsArray['output']);

                //get month national data and put in first array element
                $cacheValue = json_decode($cacheValue, true);
                if($cacheValue)
                    $output[0]['percent'] = $cacheValue[0]['percent'];
                
                //$location_id = $this->fetchlocaid($locname);
              //$sortedArray[$i]['location_id'] = $location_id;
                
                $sortedArray = $helper->addlocationnames($sortedArray);
                $output = array_merge($output, $sortedArray);
                
                //var_dump($output); exit;
                return $output;
        }

    
        
     /*
     * Percentage facilities providing FP, LARC and Injectables in the current month
     */
      public function fetchPercentFacsProviding($commodity_type, $geoList, $tierValue, $freshVisit){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter ();

            $output = array(array('location'=>'National', 'percent'=>0)); 
            $helper = new Helper2();
            $latestDate = $helper->getLatestPullDate();
            
            $cacheManager = new CacheManager();
            
            if($commodity_type == 'fp')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_PROVIDING_FP, $latestDate);
            else if($commodity_type == 'larc')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_PROVIDING_LARC, $latestDate);
            else if($commodity_type == 'injectables')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_PROVIDING_INJECTABLES, $latestDate);
            
            
            //check if page is just being loaded
            //fresh session, month data already registered
            //just retrieve registered data
            if($cacheValue && $freshVisit){ 
                $output = json_decode($cacheValue, true);
            }
            else{
                    $tierText = $helper->getLocationTierText($tierValue);
                    $tierFieldName = $helper->getTierFieldName($tierText);

                    //where clauses
                    if($commodity_type == 'fp')
                        $ct_where = "(commodity_type = 'fp' OR commodity_type = 'larc')";
                    else if($commodity_type == 'larc')
                        $ct_where = "commodity_type = 'larc'";
                    else if ($commodity_type == 'injectables')
                        $ct_where = "commodity_alias = 'injectables'";

                    $dateWhere = "c.date = '$latestDate'";
                    $reportingWhere = 'facility_reporting_status = 1';
                    $consumptionWhere = 'consumption > 0';
                    $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';

                    $coverageHelper = new CoverageHelper();
                    $longWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                       $consumptionWhere . ' AND ' . $ct_where . ' AND ' . $locationWhere;
                    $numerators = $coverageHelper->getFacProvidingCount($longWhereClause, $geoList, $tierText, $tierFieldName);

                    $dateWhere = "frr.date = '$latestDate'";
                    $longWhereClause = $dateWhere . ' AND ' . $locationWhere;
                    
                    //send only one month date range. 
                    $denominators = $helper->getReportingFacsOvertimeByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);

                    //set output                    
                    $sumsArray = $helper->sumNumersAndDenoms($numerators, $denominators);
                    $output = array_merge($output, $sumsArray['output']);
                    $output[0]['percent'] = $sumsArray['nationalAvg'];

                    //check if to save month national data
                    if(!$cacheValue && $freshVisit){ //fresh in month
                        //do cache insert
                        if($commodity_type == 'fp')
                            $alias = CacheManager::PERCENT_FACS_PROVIDING_FP;
                        else if($commodity_type == 'larc')
                            $alias = CacheManager::PERCENT_FACS_PROVIDING_LARC;
                        else if($commodity_type == 'injectables')
                            $alias = CacheManager::PERCENT_FACS_PROVIDING_INJECTABLES;
                        
                        $dataArray = array(
                            'date_cached'=> $latestDate,
                            'indicator' => 'Percent of facilities providing LARC, FP or injectables',
                            'indicator_alias' => $alias,
                            'value' => json_encode($output)
                            //'timestamp_created' => date('');
                        );
                        $cacheManager->setIndicator($dataArray);
                    }
                    else{ //inner if
                        //get month national data and put in first array element
                        $cacheValue = json_decode($cacheValue, true);
                        if($cacheValue)
                            $output[0]['percent'] = $cacheValue[0]['percent'];
                    }
            }

            //set national ave
            //var_dump($output); exit;
            return $output;

        }
        
        
     /*
     * Percentage facilities providing FP, LARC nationally per state
     */
      
      public function fetchPercentFacsProvidingPerLocation($commodity_type,$tierValue,$geoList){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter ();

            $output = array(array('location'=>'National', 'percent'=>0)); 
            $helper = new Helper2();
            
            $locations = $geoList;
            $latestDate = $helper->getLatestPullDate();
            if(!isset($tierValue) || $tierValue==""){
            $tierValue = 2;
            }
           
            if(!isset($geoList) || $geoList==""){
                $geoList = array();
            $geoList = $helper->getLocationTierIDs($tierValue);
           $geoList = implode(',',$geoList);
            }else{
                
                $geoList = $locations;
            }
            // print_r($locations);exit;
              //print_r($geoList);exit;
            $freshVisit = false;
            
            $cacheManager = new CacheManager();
            
            if($commodity_type == 'fp')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_PROVIDING_FP, $latestDate);
            else if($commodity_type == 'larc')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_PROVIDING_LARC, $latestDate);            
           
            $tierText = $helper->getLocationTierText($tierValue);
            $tierFieldName = $helper->getTierFieldName($tierText);
         
            
            $tierTexts = $helper->getLocationTierText(2);
            $tierFieldNames = $helper->getTierFieldName($tierTexts);
            //where clauses
            if($commodity_type == 'fp')
                $ct_where = "(commodity_type = 'fp' OR commodity_type = 'larc')";
            else if($commodity_type == 'larc')
                $ct_where = "commodity_type = 'larc'";

            $dateWhere = "c.date = '$latestDate'";
            $reportingWhere = 'facility_reporting_status = 1';
            $consumptionWhere = 'consumption > 0';
            $locationWhere = $tierFieldNames . ' IN (' . $geoList . ')';

            $coverageHelper = new CoverageHelper();
            $longWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                               $consumptionWhere . ' AND ' . $ct_where . ' AND ' . $locationWhere;
            $numerators = $coverageHelper->getFacProvidingCount($longWhereClause, $geoList, $tierText, $tierFieldName);
//print_r($numerators); echo '<br/><br/><br/>';
            $dateWhere = "frr.date = '$latestDate'";
            $longWhereClause = $dateWhere . ' AND ' . $locationWhere;

            //send only one month date range. 
            $denominators = $helper->getReportingFacsOvertimeByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);
//print_r($denominators);echo '<br/><br/><br/>';
            //set output                    
            $sumsArray = $helper->sumNumersAndDenoms($numerators, $denominators);
           
          //  $arrayToSort = array_slice($sumsArray['output'], 1);
            $sortedArray = $helper->msort($sumsArray['output']);

            //get month national data and put in first array element
            $cacheValue = json_decode($cacheValue, true);
            if($cacheValue)
                $output[0]['percent'] = $cacheValue[0]['percent'];
            
            $sortedArray = $helper->addlocationnames($sortedArray);
            $output = array_merge($output, $sortedArray);

            //set national ave
            //var_dump($output); 
            return $output;

        }


      //public function fetchPercentFacHWTrainedProvidingDetails($commodity_type, $training_type, &$locationNames, $where, $groupFieldName, $havingName, $geoList, $tierValue){
      public function fetchFacsWithHWProviding($commodity_type, $training_type, $geoList, $tierValue, $freshVisit){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                
                $output = array(array('location'=>'National', 'percent'=>0));
                $helper = new Helper2();
                $latestDate = $helper->getLatestPullDate();
                
                $cacheManager = new CacheManager();
            
                if($training_type == 'fp')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_HW_PROVIDING_FP, $latestDate);
                else if($training_type == 'larc')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_HW_PROVIDING_LARC, $latestDate);


                //check if page is just being loaded
                //fresh session, month data already registered
                //just retrieve registered data
                if($cacheValue && $freshVisit){ 
                    $output = json_decode($cacheValue, true);
                }
                else{
                    $tierText = $helper->getLocationTierText($tierValue);
                    $tierFieldName = $helper->getTierFieldName($tierText);
                    $locationNames = $helper->getLocationNames($geoList);
                    $consumptionWhere = 'consumption > 0';
                    $reportingWhere = 'facility_reporting_status = 1';

                    $dateWhere = "c.date = '$latestDate'";

                    //commodity type where
                    if($commodity_type == 'fp')
                        $ct_where = "(commodity_type = 'fp' OR commodity_type = 'larc')";
                    else if($commodity_type == 'larc')
                        $ct_where = "commodity_type = 'larc'";

                    //training type where
                    if($training_type == 'fp')
                        $tt_where = "fptrained > 0";
                    else if($commodity_type == 'larc')
                        $tt_where = "larctrained > 0";

                    $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';

                    $coverageHelper = new CoverageHelper();

                    //concatenate conditions for numerators
                    $longWhereClause = $consumptionWhere . ' AND ' . $reportingWhere . ' AND ' . 
                                       $ct_where . ' AND ' . $tt_where . ' AND ' . $locationWhere . ' AND ' .
                                       $dateWhere;
                    $numerators = $coverageHelper->getCoverageCountFacWithHWProviding($longWhereClause, $locationNames, $geoList, $tierText, $tierFieldName);

                    //concatenate conditions for denominators
                    $dateWhere = "frr.date = '$latestDate'";
                    $longWhereClause = $tt_where . ' AND ' . $dateWhere . ' AND ' . $locationWhere;

                    //send only one month date range. 
                    $denominators = $helper->getReportingFacsWithTrainedHWOvertimeByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);

                    //set output                    
                    $sumsArray = $helper->sumNumersAndDenoms($numerators, $denominators);
                    $output = array_merge($output, $sumsArray['output']);
                    
                    $output[0]['percent'] = $sumsArray['nationalAvg'];

                    //check if to save month national data
                    if(!$cacheValue && $freshVisit){ //fresh in month
                        //do cache insert
                        if($training_type == 'fp')
                            $alias = CacheManager::PERCENT_FACS_HW_PROVIDING_FP;
                        else if($training_type == 'larc')
                            $alias = CacheManager::PERCENT_FACS_HW_PROVIDING_LARC;

                        $dataArray = array(
                            'date_cached'=> $latestDate,
                            'indicator' => 'Percent of Facilities with a trained HW providing FP/LARC',
                            'indicator_alias' => $alias,
                            'value' => json_encode($output)
                        );
                        $cacheManager->setIndicator($dataArray);
                    }
                    else{ //inner if
                        //get month national data and put in first array element
                        $cacheValue = json_decode($cacheValue, true);
                        if($cacheValue)
                            $output[0]['percent'] = $cacheValue[0]['percent'];
                    }
                }
                    
                //echo '<br/><br/>';
                //var_dump($output); exit;
                return $output;
     }
     
     
     
 function fetchHWCoverageOvertime($training_type){
         $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        
        $ouput = array();
        $helper = new Helper2();
        $latestDate = $helper->getLatestPullDate();
                
        $cacheManager = new CacheManager();

        if($training_type == 'fp')
            $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_COVERAGE_OVERTIME_FP, $latestDate);
        else if($training_type == 'larc')
            $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_COVERAGE_OVERTIME_LARC, $latestDate);

        //check if page is just being loaded
        //fresh session, month data already registered
        //just retrieve registered data
        if($cacheValue){ 
            $output = json_decode($cacheValue, true);
        }
        else{
            //where clauses
            if($training_type == 'fp'){
                $tt_where = "fptrained > 0";
                $ct_where = "(commodity_type = 'fp' OR commodity_type = 'larc')";
            }
            else if($training_type == 'larc'){
                $tt_where = 'larctrained > 0';
                $ct_where = "commodity_type = 'larc'";
            }

            $coverageHelper = new CoverageHelper();                

            $dateWhere = '(date <= (SELECT MAX(date) FROM facility_report_rate) AND date >= DATE_SUB((SELECT MAX(date) FROM facility_report_rate), INTERVAL 11 MONTH))';
            $consmptionWhere = 'consumption > 0';
            $reportingWhere = 'facility_reporting_status = 1';
            $longWhereClause = $tt_where . ' AND ' . $dateWhere;

            //hw                                        getCoverageCountFacWithHWProviding  
            $facsWithTrainedHWNumers = $coverageHelper->getReportingFacsWithTrainedHWOvertime($longWhereClause);
            $facsReporting = $helper->getReportingFacsOvertime($dateWhere);        

            //providing
            $longWhereClause = $reportingWhere . ' AND ' . $tt_where . ' AND ' . $ct_where . ' AND ' .
                               $consmptionWhere . ' AND ' . $dateWhere;
            $facsWithHWAndConsumptionNumers = $coverageHelper->getFacWithHWProvidingOverTime($longWhereClause);
            $facsReportingWithHW = $facsWithTrainedHWNumers;

            //stockout 
            $stockout = new Stockout();
            $facsWithHWStockOutNumers = $stockout->fetchStockOutFacsWithTrainedHWOverTime($training_type);
            //$facsWithTrainedHWNumers is also denominator for this

            $hwOverTime = $helper->doOverTimePercents($facsWithTrainedHWNumers, $facsReporting);
            $providingOverTime = $helper->doOverTimePercents($facsWithHWAndConsumptionNumers, $facsWithTrainedHWNumers);
            $stockoutOverTime = $helper->doOverTimePercents($facsWithHWStockOutNumers, $facsWithTrainedHWNumers);
            
            $output = array($hwOverTime, $providingOverTime, $stockoutOverTime);
            
            //check if to save month national data
            if(!$cacheValue){ //fresh in month...this will be always true if execution gets here
                //do cache insert
                if($training_type == 'fp')
                    $alias = CacheManager::PERCENT_COVERAGE_OVERTIME_FP;
                else if($training_type == 'larc')
                    $alias = CacheManager::PERCENT_COVERAGE_OVERTIME_LARC;

                $dataArray = array(
                    'date_cached'=> $latestDate,
                    'indicator' => 'FP/LARC HR coverage over time',
                    'indicator_alias' => $alias,
                    'value' => json_encode($output)
                );
                $cacheManager->setIndicator($dataArray);
            }
        }
        
        return $output;
  }
          
     
  public function fetchProvidingOvertime($commodity_type, $geoList, $tierValue, $freshVisit){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            
            //$output = array(array('location'=>'National', 'percent'=>0)); 
            $output = array();
            $helper = new Helper2();
            $latestDate = $helper->getLatestPullDate();
                
            $cacheManager = new CacheManager();

            if($commodity_type == 'fp')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_PROVIDING_OVERTIME_FP, $latestDate);
            else if($commodity_type == 'larc')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_PROVIDING_OVERTIME_LARC, $latestDate);


            //check if page is just being loaded
            //fresh session, month data already registered
            //just retrieve registered data
            if($cacheValue && $freshVisit){ 
                $output = json_decode($cacheValue, true);
            }
            else{
                //echo 'second'; exit;
                $tierText = $helper->getLocationTierText($tierValue);
                $tierFieldName = $helper->getTierFieldName($tierText);

                //where clauses
                if($commodity_type == 'fp')
                    $ct_where = "(commodity_type = 'fp' OR commodity_type = 'larc')";
                else if($commodity_type == 'larc')
                    $ct_where = "commodity_type = 'larc'";

                $dateWhere = '(date <= (SELECT MAX(date) FROM facility_report_rate) AND date >= DATE_SUB((SELECT MAX(date) FROM facility_report_rate), INTERVAL 11 MONTH))';
                $reportingWhere = 'facility_reporting_status = 1';
                $consumptionWhere = 'consumption > 0';
                $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';

                //use coverage helper for this functions even though they have variants in the 
                //helper2 class but these do not filter and return more rows
                //appropriate for what we are doing here
                $coverageHelper = new CoverageHelper();
                $longWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                   $consumptionWhere . ' AND ' . $ct_where . ' AND ' . $locationWhere;
                $numerators = $coverageHelper->getFacProvidingOverTime($longWhereClause, $geoList, $tierText, $tierFieldName);
                //var_dump($numerators); exit;

                $longWhereClause = $dateWhere . ' AND ' . $locationWhere;
                $denominators = $coverageHelper->getReportingFacsOvertimeByLocationNoFilter($longWhereClause, $geoList, $tierText, $tierFieldName);                    
                //echo 'denom<br/>';
                //var_dump($denominators); exit;

                  //get the month names
                  $monthNames = array();  $i =0;
                  $monthNames = $helper->getPreviousMonthDates(12);
                  sort($monthNames);
                  //convert to strings 
                  foreach ($monthNames as $key=>$date){
                      $monthNames[$key] = date('F', strtotime($date));
                  }              

                  $locationNames = $helper->getLocationNames($geoList);

                  //add all missing months for each location in the numerator list
                  $numerators = $this->addMissingMonths($numerators, $monthNames, $locationNames, $tierText);             
                  $denominators = $this->addMissingMonths($denominators, $monthNames, $locationNames, $tierText);
                  //echo 'numerator count: ' . count($numerators) . '<br/>'; 
                  //echo 'denominators count: ' . count($denominators) . '<br/>'; 

//                  var_dump($numerators); echo '<br><br>';
//                  var_dump($denominators); echo '<br><br>';
//                  exit;


                  /*TP:
                   * This routine will arrange location values into month arrays
                   * Format:
                   * $output['April']['North Central'] = 1234;
                   * $output['April']['North East'] = 5678;
                   * ...
                   * $output['March']['North Central'] = 1234;
                   * $output['March']['North East'] = 5678;
                   */
                  for($i=0; $i<count($monthNames); $i++){                
                        $monthName = $monthNames[$i];
                        $output[$monthName] = array();
                        $j = $i;

                        //$output = array();
                        //$output[$monthName]['National'] = $nationalNumerator[$i]['fid_count'] / $nationalDenominator[$i]['fid_count'] * 100;
                        $output[$monthName]['National'] = 0;
                        foreach($locationNames as $location){                        
                            $output[$monthName][$location] = $numerators[$j]['fid_count'] / $denominators[$j]['fid_count'] * 100;
                            $j += 12;
                        }
                  }
                    
                    //check if to save month national data
                    if(!$cacheValue && $freshVisit){ //fresh in month
                        //do cache insert
                        if($commodity_type == 'fp')
                            $alias = CacheManager::PERCENT_PROVIDING_OVERTIME_FP;
                        else if($commodity_type == 'larc')
                            $alias = CacheManager::PERCENT_PROVIDING_OVERTIME_LARC;

                        //get national figures
                        $nationalHelper = new CoverageNationalHelper();
                        $longWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                           $consumptionWhere . ' AND ' . $ct_where;
                        $nationalNumerator = $nationalHelper->getNationalFacProvidingOverTime($longWhereClause);
                        $nationalDenominator = $nationalHelper->getNationalReportingFacsOvertime($dateWhere);

                        for($i=0; $i<count($monthNames); $i++){
                            $monthName = $monthNames[$i];
                            $output[$monthName]['National'] = $nationalNumerator[$i]['fid_count'] / $nationalDenominator[$i]['fid_count'] * 100;
                        }
                        
                        $dataArray = array(
                            'date_cached'=> $latestDate,
                            'indicator' => 'Percent of facilities providing FP/LARC over time',
                            'indicator_alias' => $alias,
                            'value' => json_encode($output)
                        );
                        $cacheManager->setIndicator($dataArray);
                    }
                    else{ //else for inner if
                        //get national data for each month and put in national key for each month
                        $cacheValue = json_decode($cacheValue, true);
                        for($i=0; $i<count($monthNames); $i++){
                            $monthName = $monthNames[$i];
                            $output[$monthName]['National'] = $cacheValue[$monthName]['National'];
                        }
                    }
            }
            
            
            //set national ave
            //var_dump($output); exit;
            return $output;
     }
     
     
     //add all missing months for each location.
     public  function addMissingMonths($numerators, $monthNames, $locationNames, $tierText ){
         $helper = new Helper2();
         $numeratorsArray = array();

          //get all the records for  each location and set missing locations to 0
          foreach($locationNames as $location){
              $locationArray = array();
              foreach ($numerators as $numer){
                  if($numer[$tierText] == $location)
                      $locationArray[] = $numer;
                  else
                      continue;
              }

              //now we have all the rows for the current location.
              //we have to ensure it has all the location names represented
              $monthArray = $helper->filterMonths($monthNames, $locationArray, $location, $tierText,  'month_name');
              
              //var_dump($monthArray); exit;
              $numeratorsArray = array_merge($numeratorsArray, $monthArray);
          }

          return $numeratorsArray;
     }
     
}

?>
