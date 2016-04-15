<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Stockout
 *
 * @author Swedge
 */
require_once('Facility.php');
require_once('Helper2.php');
require_once('StockoutHelper.php');
class Stockout {
    //put your code here
    
    public function fetchPercentStockOutFacsWithTrainedHW($training_type, $geoList, $tierValue, $freshVisit){
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
                $output = array(array('location'=>'National', 'percent'=>0)); 
                $helper = new Helper2();
                $cacheManager = new CacheManager();
            
                $latestDate = $helper->getLatestPullDate();
                if($training_type == 'fp')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_HW_STOCKED_OUT_FP, $latestDate);
                else if($training_type == 'larc')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_HW_STOCKED_OUT_LARC, $latestDate);
                
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
                    if($training_type == 'fp'){
                        $tt_where = "fptrained > 0";
                        $commodityWhere = "commodity_alias = 'so_fp_seven_days'";
                    }
                    else if($training_type == 'larc'){
                        $tt_where = 'larctrained > 0';
                        $commodityWhere = "commodity_alias = 'so_implants'";
                    }


                    $dateWhere = "date = '$latestDate'";
                    $reportingWhere = 'facility_reporting_status = 1';
                    $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';
                    $stockoutWhere = "stock_out='Y'";
                    $longWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                        $tt_where . ' AND ' . $commodityWhere . ' AND ' .
                                        $stockoutWhere. ' AND ' . $locationWhere;

                    $stockoutHelper = new StockoutHelper();                
                    $numerators = $stockoutHelper->getStockoutFacsWithTrainedHWCountByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);

                    //change long where
                    $longWhereClause = $dateWhere . ' AND ' . $tt_where . ' AND ' . $locationWhere;
                    //$denominators = $stockoutHelper->getStockoutFacsWithTrainedHWCountByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);
                    $denominators = $helper->getReportingFacsWithTrainedHWOvertimeByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);
                    
                    $sumsArray = $helper->sumNumersAndDenoms($numerators, $denominators);
                    $output = array_merge($output, $sumsArray['output']);
                    $output[0]['percent'] = $sumsArray['nationalAvg'];

                    //check if to save month national data
                    if(!$cacheValue && $freshVisit){ //fresh in month
                        //do cache insert
                        if($training_type == 'fp')
                            $alias = CacheManager::PERCENT_FACS_HW_STOCKED_OUT_FP;
                        else if($training_type == 'larc')
                            $alias = CacheManager::PERCENT_FACS_HW_STOCKED_OUT_LARC;
                    
                        $dataArray = array(
                            'date_cached'=> $latestDate,
                            'indicator' => 'Percent of facilities with a trained HW stocked out',
                            'indicator_alias' => $alias,
                            'value' => json_encode($output)
                        );
                        $cacheManager->setIndicator($dataArray);
                    }
                    else{
                        //get month national data and put in first array element
                        $cacheValue = json_decode($cacheValue, true);
                        if($cacheValue) $output[0]['percent'] = $cacheValue[0]['percent'];
                    }
                    
                    
                }
                
                //var_dump($output); exit;
                return $output;
    }
    
    
    
    public function fetchPercentStockOutFacsWithTrainedHWPerStates($training_type){
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
                $output = array(array('location'=>'National', 'percent'=>0)); 
                $helper = new Helper2();
                $cacheManager = new CacheManager();           
                $latestDate = $helper->getLatestPullDate();
                
                $tierValue = 2;
                $geoList = $helper->getLocationTierIDs($tierValue);
                $geoList = implode(',',$geoList);
                $freshVisit = false;
                
                if($training_type == 'fp')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_HW_STOCKED_OUT_FP, $latestDate);
                else if($training_type == 'larc')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_HW_STOCKED_OUT_LARC, $latestDate);
                
                //needed variables
                $tierText = $helper->getLocationTierText($tierValue);
                $tierFieldName = $helper->getTierFieldName($tierText);

                //where clauses
                if($training_type == 'fp'){
                    $tt_where = "fptrained > 0";
                    $commodityWhere = "commodity_alias = 'so_fp_seven_days'";
                }
                else if($training_type == 'larc'){
                    $tt_where = 'larctrained > 0';
                    $commodityWhere = "commodity_alias = 'so_implants'";
                }


                $dateWhere = "date = '$latestDate'";
                $reportingWhere = 'facility_reporting_status = 1';
                $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';
                $stockoutWhere = "stock_out='Y'";
                $longWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                    $tt_where . ' AND ' . $commodityWhere . ' AND ' .
                                    $stockoutWhere. ' AND ' . $locationWhere;

                $stockoutHelper = new StockoutHelper();                
                $numerators = $stockoutHelper->getStockoutFacsWithTrainedHWCountByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);

                //change long where
                $longWhereClause = $dateWhere . ' AND ' . $tt_where . ' AND ' . $locationWhere;
                $denominators = $helper->getReportingFacsWithTrainedHWOvertimeByLocation($longWhereClause, $geoList, $tierText, $tierFieldName);

                $sumsArray = $helper->sumNumersAndDenoms($numerators, $denominators);
                
                $arrayToSort = array_slice($sumsArray['output'], 1);
                
                $sortedArray = $helper->msort($arrayToSort,"DESC");
                
                //get month national data and put in first array element
                $cacheValue = json_decode($cacheValue, true);
                if($cacheValue) $output[0]['percent'] = $cacheValue[0]['percent'];
                
                $output = array_merge($output, $sortedArray);
                
                //var_dump($output); exit;
                return $output;
    }
    
    
    
    
     public function fetchFacilitiesProvidingButStockedOut($commodity_type, $geoList, $tierValue, $freshVisit){
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
                $output = array(array('location'=>'National', 'percent'=>0)); 
                $helper = new Helper2();
                $latestDate = $helper->getLatestPullDate();
                
               
                    //needed variables
                    $tierText = $helper->getLocationTierText($tierValue);
                    $tierFieldName = $helper->getTierFieldName($tierText);


                    //where clauses
                    if($commodity_type == 'fp'){
                        $commodityTypeWhere = "commodity_type = 'fp'";
                        $commodityAliasWhere = "commodity_alias = 'so_fp_seven_days'";
                    }
                    else if($commodity_type == 'larc'){
                        $commodityTypeWhere = "commodity_type = 'larc'";
                        $commodityAliasWhere = "commodity_alias = 'so_implants'";
                    }


                    $dateWhere = "c.date = '$latestDate'";
                    //use 5 months interval because current month is inclusive
                    $date6MonthsIntervalWhere = "c.date >= DATE_SUB('$latestDate', INTERVAL 5 MONTH) AND c.date <= '$latestDate'";
                    $reportingWhere = 'facility_reporting_status = 1';
                    $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';
                    $stockoutWhere = "stock_out='Y'";
                    $consumptionWhere = 'consumption > 0';

                    $mainWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                        $commodityAliasWhere . ' AND ' . $stockoutWhere . ' AND ' .
                                        $locationWhere;
                    $subWhereClause = $commodityTypeWhere . ' AND ' . $consumptionWhere . ' AND ' .
                                      $date6MonthsIntervalWhere . ' AND ' . $locationWhere;;

                    $stockoutHelper = new StockoutHelper();                
                    $facilitiesLists = $stockoutHelper->getFacilitiesListProvidingButStockedout($mainWhereClause, $subWhereClause, $geoList, $tierText, $tierFieldName);
                    $facilitiesNames = array();
                    foreach($facilitiesLists as $facilityDetails){
                    $facilitiesNames[] = $facilityDetails['facility_name'];
    
}
                    //change main where
                    
                    //var_dump($numerators); echo '<br><br>';
                    //var_dump($denominators); echo '<br><br>';

                  
                
                
                //print_r($output);exit;
                return $facilitiesNames;
    }
    
    
    public function fetchPercentFacsProvidingButStockedOut($commodity_type, $geoList, $tierValue, $freshVisit, $sortResults){
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
                $output = array(array('location'=>'National', 'percent'=>0)); 
                $helper = new Helper2();
                $latestDate = $helper->getLatestPullDate();
                
                $cacheManager = new CacheManager();
            
                if($commodity_type == 'fp')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_PROVIDING_STOCKED_OUT_FP, $latestDate);
                else if($commodity_type == 'larc')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_PROVIDING_STOCKED_OUT_LARC, $latestDate);
                
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


                    //where clauses
                    if($commodity_type == 'fp'){
                        $commodityTypeWhere = "commodity_type = 'fp'";
                        $commodityAliasWhere = "commodity_alias = 'so_fp_seven_days'";
                    }
                    else if($commodity_type == 'larc'){
                        $commodityTypeWhere = "commodity_type = 'larc'";
                        $commodityAliasWhere = "commodity_alias = 'so_implants'";
                    }


                    $dateWhere = "c.date = '$latestDate'";
                    //use 5 months interval because current month is inclusive
                    $date6MonthsIntervalWhere = "c.date >= DATE_SUB('$latestDate', INTERVAL 5 MONTH) AND c.date <= '$latestDate'";
                    $reportingWhere = 'facility_reporting_status = 1';
                    $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';
                    $stockoutWhere = "stock_out='Y'";
                    $consumptionWhere = 'consumption > 0';

                    $mainWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                        $commodityAliasWhere . ' AND ' . $stockoutWhere . ' AND ' .
                                        $locationWhere;
                    $subWhereClause = $commodityTypeWhere . ' AND ' . $consumptionWhere . ' AND ' .
                                      $date6MonthsIntervalWhere . ' AND ' . $locationWhere;;

                    $stockoutHelper = new StockoutHelper();                
                    $numerators = $stockoutHelper->getFacsProvidingButStockedout($mainWhereClause, $subWhereClause, $geoList, $tierText, $tierFieldName);

                    //change main where
                    $mainWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . $locationWhere;
                    $denominators = $stockoutHelper->getFacsProvidingButStockedout($mainWhereClause, $subWhereClause, $geoList, $tierText, $tierFieldName);

                    $sumsArray = $helper->sumNumersAndDenoms($numerators, $denominators);
                    
                    if($sortResults)
                        $sortedArray = $helper->msort($sumsArray['output'],"DESC");
                    else
                        $sortedArray = $sumsArray['output'];
                    
                   // print_r($sortedArray);
                   // echo '<br/>';
                    $output = array_merge($output, $sortedArray);
                    $output[0]['percent'] = $sumsArray['nationalAvg'];
                    
                   // print_r($numerators); echo '<br><br>';
                    //print_r($denominators); echo '<br><br>';
//exit;
                    //check if to save month national data
                    if(!$cacheValue && $freshVisit){ //fresh in month
                        //do cache insert
                        if($commodity_type == 'fp')
                            $alias = CacheManager::PERCENT_PROVIDING_STOCKED_OUT_FP;
                        else if($commodity_type == 'larc')
                            $alias = CacheManager::PERCENT_PROVIDING_STOCKED_OUT_LARC;
                    
                        $dataArray = array(
                            'date_cached'=> $latestDate,
                            'indicator' => 'Providing Stock Out',
                            'indicator_alias' => $alias,
                            'value' => json_encode($output)
                        );
                        $cacheManager->setIndicator($dataArray);
                    }
                    else{
                        //get month national data and put in first array element
                        $cacheValue = json_decode($cacheValue, true);
                        if($cacheValue) $output[0]['percent'] = $cacheValue[0]['percent'];
                    }
                }
                
                
                return $output;
    }
    
    
    public function fetchStockOutFacsWithTrainedHWOverTime($training_type){
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
                $output = array();
                $helper = new Helper2();
                
                //where clauses
                if($training_type == 'fp'){
                    $tt_where = "fptrained > 0";
                    $commodityWhere = "commodity_alias = 'so_fp_seven_days'";
                }
                else if($training_type == 'larc'){
                    $tt_where = 'larctrained > 0';
                    //$commodityWhere = "commodity_type = 'larc'";
                    $commodityWhere = "commodity_alias = 'so_implants'";
                }
                
                
                $dateWhere = '(date <= (SELECT MAX(date) FROM facility_report_rate) AND date >= DATE_SUB((SELECT MAX(date) FROM facility_report_rate), INTERVAL 11 MONTH))';
                $reportingWhere = 'facility_reporting_status = 1';
                $stockoutWhere = "stock_out='Y'";
                $longWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                    $tt_where . ' AND ' . $commodityWhere . ' AND ' .
                                    $stockoutWhere;
                
                $stockoutHelper = new StockoutHelper();                
                $facsWithHWStockOutOvertime = $stockoutHelper->getStockoutFacsWithTrainedHWOverTime($longWhereClause);
                
                
                //var_dump($output); exit;
                return $facsWithHWStockOutOvertime;
    }
    
}

?>