<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Dashboard
 *
 * @author Swedge
 */
require_once 'Helper2.php';
require_once 'Facility.php';
require_once 'DashboardHelper.php';
require_once 'CacheManager.php';
require_once 'Coverage.php';
require_once 'Stockout.php';
require_once 'CoverageNationalHelper.php';

class Dashboard {
    //put your code here
    
    public function fetchConsumptionByMethod(){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        $output = array ();
        $helper = new Helper2();
        $cacheManager = new CacheManager();
        
        $latestDate = $helper->getLatestPullDate();
        $cacheValue = $cacheManager->getIndicator(CacheManager::CONSUMPTIONON_BY_METHOD,$latestDate);
        
        if(!$cacheValue){
            $where = "(commodity_type='fp' OR commodity_type = 'larc') AND date = '$latestDate'";
            $select = $db->select()
                         ->from(array('c'=>'commodity'), array('SUM(consumption) as consumption'))
                         ->joinRight(array('cno'=>'commodity_name_option'), 'cno.id = c.name_id', 
                                                    array('commodity_name as method'))
                         ->where($where)
                         ->group('commodity_name')
                         ->order(array('display_order'));
            $result = $db->fetchAll($select);
            
            //do cache insert
            $dataArray = array(
                    'date_cached'=> $latestDate,
                    'indicator' => 'Consumption By Method',
                    'indicator_alias' => CacheManager::CONSUMPTIONON_BY_METHOD,
                    'value' => json_encode($result)
                );
            $cacheManager->setIndicator($dataArray);
        }
        else{ //data is cached. Retrieve it
            $result = json_decode($cacheValue, true);
        }
        
        return $result;               
    }
    
    
    /*TP: Rewriting the fetchCSDetails method
       * This time we will modularize and make use of views that will already filter the date
       * This method get the last DHIS2 download date and use it as argument for 
       * the 3 categories of calls: 
       */
      public function fetchCoverageSummary($geoList, $tierValue){          
           //$output = $this->fetchCSDetails1(null);
           //var_dump($output); 
           
           $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	   $output = array(); $helper = new Helper2(); $params = array(); $fac = new Facility();
           $cacheManager = new CacheManager();
           $coverage = new Coverage(); $stockout = new Stockout();
           
           $latestDate = $helper->getLatestPullDate();
           $cacheValue = $cacheManager->getIndicator(CacheManager::HR_SUMMARY,$latestDate);
        
            if(!$cacheValue){                
                //echo 'no cache'; exit;
                $freshVisit = false;
                
                $cs_fp_trained_facility_count = $coverage->fetchPercentFacHWTrained('fp', $geoList, $tierValue, $freshVisit);
                $output['cs_fp_trained_facility_count'] = $cs_fp_trained_facility_count[0]['percent'];
                
                $cs_larc_trained_facility_count = $coverage->fetchPercentFacHWTrained('larc', $geoList, $tierValue, $freshVisit);
                $output['cs_larc_trained_facility_count'] = $cs_larc_trained_facility_count[0]['percent'];
                
                $cs_fp_consumption_facility_count = $coverage->fetchFacsWithHWProviding('fp', 'fp', $geoList, $tierValue, $freshVisit);
                $output['cs_fp_consumption_facility_count'] = $cs_fp_consumption_facility_count[0]['percent'];
                
                $cs_larc_consumption_facility_count = $coverage->fetchFacsWithHWProviding('larc', 'larc', $geoList, $tierValue, $freshVisit);
                $output['cs_larc_consumption_facility_count'] = $cs_larc_consumption_facility_count[0]['percent'];
                
                $cs_fp_stock_out_facility_count = $stockout->fetchPercentStockOutFacsWithTrainedHW('fp', $geoList, $tierValue, $freshVisit);
                $output['cs_fp_stock_out_facility_count'] = $cs_fp_stock_out_facility_count[0]['percent'];
                
                $cs_larc_stock_out_facility_count = $stockout->fetchPercentStockOutFacsWithTrainedHW('larc', $geoList, $tierValue, $freshVisit);
                $output['cs_larc_stock_out_facility_count'] = $cs_larc_stock_out_facility_count[0]['percent'];
                
                //do cache insert
                $dataArray = array(
                    'date_cached'=> $latestDate,
                    'indicator' => 'HR Summary',
                    'indicator_alias' => CacheManager::HR_SUMMARY,
                    'value' => json_encode($output)
                );
                $cacheManager->setIndicator($dataArray);
            }
            else{ //data is cached. Retrieve it
                $output = json_decode($cacheValue, true);
            }
                        
          return $output;
            
   }//end fetchCoverageSummary
      
      
      public function fetchFacsProviding($commodity_type) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $output = array(); $helper = new Helper2();
            $cacheManager = new CacheManager();

            //$denominator = $this->getMonthlyFacilitiesReportingWithConsumption();
            $latestDate = $helper->getLatestPullDate();
            
            if($commodity_type == 'fp')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_PROV_OVERTIME_FP,$latestDate);
            if($commodity_type == 'larc')
                $cacheValue = $cacheManager->getIndicator(CacheManager::PERCENT_FACS_PROV_OVERTIME_LARC,$latestDate);
            
                if(!$cacheValue){
                        //where clauses
                        if($commodity_type == 'fp')
                            $ct_where = "(commodity_type = 'fp' OR commodity_type = 'larc')";
                        else if($commodity_type == 'larc')
                            $ct_where = "commodity_type = 'larc'";

                        $dateWhere = '(date <= (SELECT MAX(date) FROM facility_report_rate) AND date >= DATE_SUB((SELECT MAX(date) FROM facility_report_rate), INTERVAL 11 MONTH))';
                        $reportingWhere = 'facility_reporting_status = 1';
                        $consumptionWhere = 'consumption > 0';
                    
                        $nationalHelper = new CoverageNationalHelper();
                        $longWhereClause = $reportingWhere . ' AND ' . $dateWhere . ' AND ' . 
                                           $consumptionWhere . ' AND ' . $ct_where;
                        $numerators = $nationalHelper->getNationalFacProvidingOverTime($longWhereClause);
                        
                        $denominators = $nationalHelper->getNationalReportingFacsOvertime($dateWhere);
                        
                        foreach ($numerators as $i => $row){
                            $output[] = array(
                                "month" => $numerators[$i]['month_name'],
                                "percent" => $numerators[$i]['fid_count']/$denominators[$i]['fid_count'],
                            );
                        }
                        
                       
                        if($commodity_type == 'fp')
                            $alias = CacheManager::PERCENT_FACS_PROV_OVERTIME_FP;
                       if($commodity_type == 'larc')
                            $alias = CacheManager::PERCENT_FACS_PROV_OVERTIME_LARC;
                       
                        //do cache insert
                        $dataArray = array(
                            'date_cached'=> $latestDate,
                            'indicator' => 'Percent Facs Providing Over Time',
                            'indicator_alias' => $alias,
                            'value' => json_encode($output)
                        );
                        $cacheManager->setIndicator($dataArray);
                }
                else{ //data is cached. Retrieve it
                    $output = json_decode($cacheValue, true);
                }
            

            //var_dump($output); exit;
            //return array_reverse($output, true);
            return $output;
        }	
        
        
        /*
         * This method is now obsolete but keeping for some time just in case
         */
        public function fetchFacsProvidingStockedout($geoList, $tierValue, $freshVisit) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $output = array();  $helper = new Helper2();
            $cacheManager = new CacheManager();
            
            $latestDate = $helper->getLatestPullDate();
            $cacheValue = $cacheManager->getIndicator(CacheManager::STOCK_OUTS,$latestDate);
            //$cacheValue = null;
            
            if($cacheValue && $freshVisit){ //data is cached. Use it
                $output = json_decode($cacheValue, true);
            }
            else{
                /******************** BEGIN NEW QUERY ************************/
                $tierText = $helper->getLocationTierText($tierValue);
                $tierFieldName = $helper->getTierFieldName($tierText);
                $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';
                
                $dashBoardHelper = new DashboardHelper();
                $numerators = $dashBoardHelper->getStockOutNumerators($locationWhere, $tierText, $tierFieldName);
                //var_dump($numerators); echo '<br><br>'; exit;
                $denominators = $dashBoardHelper->getStockOutDenominators($locationWhere);     
                //var_dump($denominators); exit;
                /******************** END NEW QUERY **************************/

                foreach ($numerators['fp'] as $key=>$row){
                    $output[] = array(
                        "month" => date('F', strtotime($key)),
                        "year" => date('Y', strtotime($key)),
                        "implant_percent" => $numerators['larc'][$key] / $denominators['larc'][$key], // implant
                        "seven_days_percent" => $row / $denominators['fp'][$key]
                    );
                }

                //check if to save month national data
                if(!$cacheValue){ //fresh in month...this will be always true if execution gets here
                    //do cache insert
                    $dataArray = array(
                        'date_cached'=> $latestDate,
                        'indicator' => 'Stock Outs',
                        'indicator_alias' => CacheManager::STOCK_OUTS,
                        'value' => json_encode($output)
                    );
                    $cacheManager->setIndicator($dataArray);
                }
            }
            

            //var_dump($output); exit;
            return array_reverse($output,true);

        }	
        
        
        
        //this method replaces the above method
        public function fetchFacsProvidingStockedoutOvertime($commodity_type, $geoList, $tierValue, $freshVisit) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
                $output = array(); 
                $helper = new Helper2();
                $latestDate = $helper->getLatestPullDate();
                
                $cacheManager = new CacheManager();
            
                if($commodity_type == 'fp')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::STOCK_OUTS_OVERTIME_FP, $latestDate);
                else if($commodity_type == 'larc')
                    $cacheValue = $cacheManager->getIndicator(CacheManager::STOCK_OUTS_OVERTIME_LARC, $latestDate);
                
                //$cacheValue = null;
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
                        $percentIndexText = 'seven_days_percent';
                    }
                    else if($commodity_type == 'larc'){
                        $commodityTypeWhere = "commodity_type = 'larc'";
                        $commodityAliasWhere = "commodity_alias = 'so_implants'";
                        $percentIndexText = 'implant_percent';
                    }


                    $dateWhere = "c.date = '$latestDate'";
                    //use 5 months interval because current month is inclusive
                    $date6MonthsIntervalWhere = "c.date >= DATE_SUB('$latestDate', INTERVAL 5 MONTH) AND c.date <= '$latestDate'";
                    $reportingWhere = 'facility_reporting_status = 1';
                    $locationWhere = $tierFieldName . ' IN (' . $geoList . ')';
                    $stockoutWhere = "stock_out='Y'";
                    $consumptionWhere = 'consumption > 0';

                    $mainWhereClause = $reportingWhere . ' AND ' . 
                                        $commodityAliasWhere . ' AND ' . $stockoutWhere . ' AND ' .
                                        $locationWhere;
                    $subWhereClause = $commodityTypeWhere . ' AND ' . $consumptionWhere . ' AND ' .
                                      $locationWhere;;

                    $dashboardHelper = new DashboardHelper();                
                    $numerators = $dashboardHelper->getFacsProvidingButStockedout($mainWhereClause, $subWhereClause);

                    //change main where
                    $mainWhereClause = $reportingWhere . ' AND ' . $locationWhere;
                    $denominators = $dashboardHelper->getFacsProvidingButStockedout($mainWhereClause, $subWhereClause);

                    foreach ($numerators as $date=>$numer){
                        $output[] = array(
                                    'month' => date('F', strtotime($date)),
                                    $percentIndexText => $numer / $denominators[$date]
                        );
                    }
                    
                    //print_r($numerators); echo '<br><br>';
                    //print_r($denominators); echo '<br><br>';
                    //print_r($output); echo '<br><br>';
                    //exit;
                    
                    //check if to save month national data
                    if(!$cacheValue && $freshVisit){ //fresh in month
                        //do cache insert
                        if($commodity_type == 'fp')
                            $alias = CacheManager::STOCK_OUTS_OVERTIME_FP;
                        else if($commodity_type == 'larc')
                            $alias = CacheManager::STOCK_OUTS_OVERTIME_LARC;
                    
                        $dataArray = array(
                            'date_cached'=> $latestDate,
                            'indicator' => 'Providing Stocked Out Over time',
                            'indicator_alias' => $alias,
                            'value' => json_encode($output)
                        );
                        $cacheManager->setIndicator($dataArray);
                    }
                }
                
                
                return array_reverse($output);
        }
    
}


?>