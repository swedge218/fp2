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
            
            //var_dump($output); exit;
            
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
        
        
        
        public function fetchFacsProvidingStockedout() {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $output = array();  $helper = new Helper2();
            $cacheManager = new CacheManager();
            
            $latestDate = $helper->getLatestPullDate();
            $cacheValue = $cacheManager->getIndicator(CacheManager::STOCK_OUTS,$latestDate);
        
            if(!$cacheValue){
                /******************** BEGIN NEW QUERY ************************/
                 $dashBoardHelper = new DashboardHelper();
                 $numerators = $dashBoardHelper->getStockOutNumerators();

                 $denominators = $dashBoardHelper->getStockOutDenominators();     
                /******************** END NEW QUERY ************************/

                foreach ($numerators['fp'] as $key=>$row){
                    $output[] = array(
                        "month" => date('F', strtotime($key)),
                        "year" => date('Y', strtotime($key)),
                        "implant_percent" => $numerators['larc'][$key] / $denominators['larc'][$key], // implant
                        "seven_days_percent" => $row / $denominators['fp'][$key]
                    );
                }

                //do cache insert
                $dataArray = array(
                    'date_cached'=> $latestDate,
                    'indicator' => 'Stock Outs',
                    'indicator_alias' => CacheManager::STOCK_OUTS,
                    'value' => json_encode($output)
                );
                $cacheManager->setIndicator($dataArray);
            }
            else{ //data is cached. Retrieve it
                $output = json_decode($cacheValue, true);
            }

            //var_dump($output); exit;
            return array_reverse($output,true);

        }	
    
}


?>
