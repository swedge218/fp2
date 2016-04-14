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
require_once('Helper2.php');

class DashboardHelper {
    
    //put your code here
    public function getStockOutNumerators(){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $helper = new Helper2();
    
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
                $sql = $helper->getFacilitiesWithConsumptionInLastSixMonths($date, $commType, true);

                if(!empty($sql)){
                    //create the helper view on the fly
                    try{
                        $sql = 'create or replace view tp_pfso_view as ('.$sql.')';
                        $db->query($sql);
                    }
                    catch (Exception $e) { // normal operation throws "General Error"
                        echo $e->getMessage();
                    }
                    
                    //get the list of stocked out facilities
                    $stockedOutFacs = implode(',', $helper->getStockedOutFacilities($date, $commType));
                    if(empty($stockedOutFacs)) $stockedOutFacs = "''";

                    $select = $db->select()
                                ->from(array('pfso_view' => 'tp_pfso_view'), 'COUNT(facility_id) AS facility_id')
                                ->where ('facility_id IN (' . $stockedOutFacs . ')')
                                ->order(array('facility_id'));
                    $sql = $select->__toString(); 
                    
                    
                    
                    $result = $db->fetchAll($sql);
                    $numerators[$commType][$date] = $result[0]['facility_id'];
                }//end if

            }//end for
        }
        
        //var_dump($numerators); exit;
        return $numerators;
    }
    
    public function getStockOutDenominators(){

        $helper = new Helper2();
    
        //get all monthly dates in previous 12 months range
        $monthlyDates = $helper->getPreviousMonthDates(12);
        
        
        $commTypes = array('fp', 'larc');
        $denominators = array();
        
        //koko
        foreach($commTypes as $commType){
            foreach($monthlyDates as $date){
                //get the facilities that have conumption in the last 6 months from this date
                $result = $helper->getFacilitiesWithConsumptionInLastSixMonths($date, $commType, false);
                //var_dump($result); exit;
                $denominators[$commType][$date] = count($result);
            }//end for
        }
        
        return $denominators;
    }
}

?>
