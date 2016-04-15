<?php

/*
 * TP:
 * This class is to help modularize logic in the DashboardCHAI class
 */

/**
 * Description of DashboardHelper
 *
 * @author Swedge
 */
require_once('Helper22.php');

class DashboardHelper2 {
    
    //put your code here
    public function getStockOutNumerators($longWhereClause){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $helper = new Helper22();
    
        //get all monthly dates in previous 12 months range
        $monthlyDates = $helper->getPreviousMonthDates(12);
        
        //echo '3------: ';
        //var_dump($monthlyDates); exit;
        
        $commTypes = array('fp','larc');
        $numerators = array();
        
        //koko
        foreach($commTypes as $commType){
            foreach($monthlyDates as $date){
                //get the facilities that have conumption in the last 6 months from this date
                $sql = $helper->getFacilitiesWithConsumptionInLastSixMonths($longWhereClause, $date, $commType, true);
                //echo $sql . '<br><br>'; 
                
                if(!empty($sql)){
                    //create the helper view on the fly
                    try{
                        $sql = 'create or replace view tp_pfso_view as ('.$sql.')';
                        $db->query($sql);
                    }
                    catch (Exception $e) { // normal operation throws "General Error"
                        echo $e->getMessage();
                    }
                
                    //echo 'created view<br><br>'; 
                    //get the list of stocked out facilities
                    $stockedOutFacs = implode(',', $helper->getStockedOutFacilities($longWhereClause, $date, $commType));
                    if(empty($stockedOutFacs)) $stockedOutFacs = "''";

                    //echo 'found SOs<br><br>'; 
                    $select = $db->select()
                                ->from(array('pfso_view' => 'tp_pfso_view'), 'COUNT(facility_id) AS facility_id')
                                ->where ('facility_id IN (' . $stockedOutFacs . ')')
                                ->order(array('facility_id'));
                    $sql = $select->__toString(); 
                    
                    //echo 'selected prov SOs<br><br>'; 
                    
                    $result = $db->fetchAll($sql);
                    echo 'after result: ' . count($result); 
                    $numerators[$commType][$date] = $result[0]['facility_id'];
                 
                }//end if

            }//end for
        }
        
        echo '<br><br>';
        var_dump($numerators); exit;
        return $numerators;
    }
    
    public function getStockOutDenominators($longWhereClause){

        $helper = new Helper22();
    
        //get all monthly dates in previous 12 months range
        $monthlyDates = $helper->getPreviousMonthDates(12);
        
        
        $commTypes = array('fp', 'larc');
        $denominators = array();
        
        //koko
        foreach($commTypes as $commType){
            foreach($monthlyDates as $date){
                //get the facilities that have conumption in the last 6 months from this date
                $result = $helper->getFacilitiesWithConsumptionInLastSixMonths($longWhereClause, $date, $commType, false);
                //var_dump($result); exit;
                $denominators[$commType][$date] = count($result);
            }//end for
        }
        
        return $denominators;
    }
    
    
    public function getFacsProvidingButStockedout($mainWhereClause, $subWhereClause){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $helper = new Helper22();
    
        //get all monthly dates in previous 12 months range
        $monthlyDates = $helper->getPreviousMonthDates(12);
        
        //$commTypes = array('fp','larc');
        $output = array();
        
        //koko
        //foreach($commTypes as $commType){
        foreach($monthlyDates as $date){
                //use 5 months interval because current month is inclusive
                $date6MonthsIntervalWhere = "c.date >= DATE_SUB('$date', INTERVAL 5 MONTH) AND c.date <= '$date'";
                $subselect = $db->select()
                              ->from(array('c' => 'commodity'), array('DISTINCT(c.facility_id) AS providingfacs'))
                              ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array())
                              ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array())
                              ->where($subWhereClause . ' AND ' . $date6MonthsIntervalWhere);
                
                $dateWhere = "c.date = '$date'";
                $select = $db->select()
                            ->from(array('c' => 'commodity'),
                              array('COUNT(DISTINCT(c.facility_id)) AS fid_count'))
                            ->joinInner(array('cno' => 'commodity_name_option'), 'cno.id = c.name_id', array())
                            ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array('lga', 'state',  'geo_zone'))
                            ->where($mainWhereClause . ' AND ' . $dateWhere . ' AND c.facility_id IN (' . $subselect . ')')
                            ->group('date')
                            ->order(array('date'));

              //echo $select->__toString() . '<br/><br/>'; exit

               $result = $db->fetchAll($select);
               //var_dump($result); exit;
               $output[$date] = $result[0]['fid_count'];
              //filter for only valid values
              //$locationNames = $helper->getLocationNames($geoList);
              //$locationDataArray = $helper->filterLocations($locationNames, $result, $tierText);
               
            //var_dump($locationDataArray); exit;
        }
        
        //var_dump($output); exit;
        return $output;
    }
}

?>