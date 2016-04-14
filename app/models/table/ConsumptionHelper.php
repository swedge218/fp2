<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ConsumptionHelper
 *
 * @author Swedge
 */
require_once 'Helper2.php';

class ConsumptionHelper {
    //put your code here
    
    public function getCommConsumptionByCommodity($commID=0, $longWhere, $geoList){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        $helper = new Helper2(); $output = array();
        
        $select = $db->select()
                     ->from(array('c'=>'commodity'), array('SUM(consumption) AS consumption'))
                     ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array('commodity_name as method'))
                     ->joinInner(array('flv'=>'facility_location_view'), 'flv.id = c.facility_id', array())
                     ->where($longWhere)
                     ->group('c.name_id')
                     ->order(array('display_order'));       
         
        //echo $select->__toString(); 
        //exit;
        //$helper->plog($select->__toString());
        
        $result = $db->fetchAll($select);
        
        if(!empty($result)){
            foreach($result as $row)
                $row['consumption'] = empty($row['consumption']) ? $row['consumption'] : 0;
            $output = $result;
        }
        else{
            $commodityName = $helper->getCommodityName($commID);
            $output = array('method' => $commodityName, 'consumption'=>0);
        }
        
        //the query above creates a sum for consumptions
        //sum does not add any commodities that do not have consumption 
        //even if LEFT JOIN is used
        //add any missing commodities to the list 
        $commNames = $helper->getCommodityNames();
        $commNamesArray = explode(',', $commNames);
        foreach($commNamesArray as $key=>$cn){
            $found = false;
            foreach($output as $methodList){
                if(strtolower($cn) == strtolower($methodList['method'])){
                    $commNamesArray[$key] = array('method' => $cn, 'consumption'=>$methodList['consumption']);
                    $found = true;
                    break;
                }
            }
            //if not found then set zero consumption for that commodity
            if(!$found)
                $commNamesArray[$key] = array('method' => $cn, 'consumption'=>0);
        }
        
        //var_dump($commNamesArray); exit;
        //exit;
        return $commNamesArray;
   }
   
   //public function getCommConsumptionByLocation($longWhere, $locationNames, $geoList, $tierText, $tierFieldName){
    public function getCommConsumptionByGeography($commID=0, $longWhereClause, $locationNames, $geoList, $tierText, $tierFieldName){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        $helper = new Helper2(); $output = array();
        
        $select = $db->select()
                     ->from(array('c'=>'commodity'), array('SUM(consumption) AS consumption'))
                     ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array('commodity_name as method'))
                     ->joinInner(array('flv'=>'facility_location_view'), 'flv.id = c.facility_id', array('lga', 'state', 'geo_zone'))
                     ->where($longWhereClause)
                     ->group($tierFieldName)
                     ->order(array($tierText));       
         
        //echo $select->__toString(); exit;
         
        $result = $db->fetchAll($select);
        
        if(!empty($result))
            $methodName = $result[0]['method'];
        else{
            $methodName = $helper->getCommodityName($commID);
        }
        //var_dump($result); exit;
        
        $locationdata = $this->filterLocations($locationNames, $result, $tierText);
        //var_dump($locationdata); exit;
        return $output = array('method'=>$methodName, 'locationdata'=>$locationdata);
   }
   
   
   
   public function filterLocations($locationNames, $result, $tierText){
           $locationDataArray = array();
           if(!empty($result)){
                  //echo 'not empty: ' . $tierText; exit;
                    //var_dump($locationNames);exit;
                foreach($locationNames as $key=>$locationName){
                    $locationValue = '';
                    foreach($result as $entry){
                        //echo 'tier: ' . $tierText . '<br/>';
                        //var_dump($coverageEntry); exit;
                        if($locationName == $entry[$tierText]){                                    
                            $locationValue = $entry['consumption']; 
                            break;
                        }
                    }

                    if(empty($locationValue))
                        $locationValue = 0;

                    $locationDataArray[$locationName] = $locationValue;
                }
            }
            else{
                //echo 'empty: ' . $tierText; exit;
                foreach($locationNames as $key=>$locationName)
                    $locationDataArray[$locationName] = 0;
            }
            
            return $locationDataArray;
       }
   
       
       public function getConsumptionByCommodityOverTimePdf($longWhere, $commNames){
           $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
            $helper = new Helper2(); $output = array();

            $select = $db->select()
                         ->from(array('c'=>'commodity'), 
                                 array('SUM(consumption) AS consumption', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                         ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array('commodity_name as method'))
                         ->where($longWhere)
                         ->group(array('commodity_name', 'date'))
                         ->order(array('display_order', 'date'));       

            //echo $select->__toString(); exit;

            $result = $db->fetchAll($select);
            //var_dump($result); exit;
            //get the month names
            $monthNames = array();  $i =0;
            while($i<12){
                $monthNames[] = $result[$i]['month_name'];
                $i++;
            }
            
            for($i=0; $i<count($monthNames); $i++){
                $monthName = $monthNames[$i];
                $output[$monthName] = array();
                $j = $i;
                foreach($commNames as $comm){
                    $output[$monthName][$comm] = $result[$j]['consumption'];
                    $j += 12;
                }
            }
            
            //var_dump($output); exit;
            return $output;
       }
       
       
       public function getConsumptionByCommodityOverTime($longWhere, $commNames){
           $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
            $helper = new Helper2(); $output = array();

            $select = $db->select()
                         ->from(array('c'=>'commodity'), 
                                 array('SUM(consumption) AS consumption', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                         ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array('commodity_name as method'))
                         ->where($longWhere)
                         ->group(array('commodity_name', 'date'))
                         ->order(array('display_order', 'date'));       

            //echo $select->__toString(); exit;

            $result = $db->fetchAll($select);
            //var_dump($result); exit;
            //get the month names
            $monthNames = array();  $i =0;
            while($i<12){
                $monthNames[] = $result[$i]['month_name'];
                $i++;
            }
            
            for($i=0; $i<count($monthNames); $i++){
                $monthName = $monthNames[$i];
                $output[$monthName] = array();
                $j = $i;
                foreach($commNames as $comm){
                    $output[$monthName][$comm] = $result[$j]['consumption'];
                    $j += 12;
                }
            }
            
            //var_dump($output); exit;
            return $output;
       }
       
       public function getAllConsumptionBySingleLocationOverTime($dateWhere, $commodityWhere, $locationWhere){
           $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
            $helper = new Helper2(); $output = array();

            $sql = 'SELECT DISTINCT(commodity_name), commodity_type, c.name_id, COALESCE(SUM(c.consumption),0) as consumption, c.date, MONTHNAME(c.date) as month_name, YEAR(c.date) ' .
                   'FROM  commodity_name_option  cno ' .
                   'LEFT JOIN ( ' .
                                'SELECT c.* FROM commodity c ' .
                                'INNER JOIN facility_location_view AS flv ON flv.id = c.facility_id ' .
                                'WHERE (' . $locationWhere . ' AND ' . $dateWhere . ')' .
                               ') as c ' .
                   'ON cno.id = c.name_id WHERE (' . $commodityWhere . ') ' .
                   'GROUP BY commodity_name, date ' .
                   'ORDER BY display_order, date';

            //echo $select->__toString() . '<br><br>'; exit;
            //echo $sql; exit;
            $helper->plog($sql);
            $result = $db->fetchAll($sql);
            
            $commNames = explode(',',$helper->getCommodityNames('', false));
            //var_dump($commNames);
            //get the month names
            $monthNames = array();  $i =0;
            $dates = $helper->getPreviousMonthDates(12);
            foreach($dates as $key=>$date){
                $monthNames[] = date('F', strtotime($date));
            }
            $monthNames = array_reverse($monthNames);
            //var_dump($monthNames); exit;
            
            
            $loopStart = 0;
            for($i=0; $i<count($monthNames); $i++){
                $monthName = $monthNames[$i];
                $output[$monthName] = array();
                foreach($commNames as $comm){
                    //echo $monthName . ' ' . $comm;
                    for($j = 0; $j<count($result); $j++){
                        if($monthName == $result[$j]['month_name'] && $comm == $result[$j]['commodity_name']){
                            $output[$monthName][$comm] = $result[$j]['consumption'];
                            //$loopStart = $j;
                            break;
                        }                            
                    }
                    if(!isset($output[$monthName][$comm]))
                        $output[$monthName][$comm] = 0;
                }
            }
            
            //var_dump($output); exit;
            return $output;
       }
       public function getAllConsumptionBySingleLocationOverTimePdf($dateWhere, $commodityWhere, $locationWhere,$upperDate,$lowerDate){
           $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
            $helper = new Helper2(); $output = array();

            $sql = 'SELECT DISTINCT(commodity_name), commodity_type, c.name_id, COALESCE(SUM(c.consumption),0) as consumption, c.date, MONTHNAME(c.date) as month_name, YEAR(c.date) ' .
                   'FROM  commodity_name_option  cno ' .
                   'LEFT JOIN ( ' .
                                'SELECT c.* FROM commodity c ' .
                                'INNER JOIN facility_location_view AS flv ON flv.id = c.facility_id ' .
                                'WHERE (' . $locationWhere . ' AND ' . $dateWhere . ')' .
                               ') as c ' .
                   'ON cno.id = c.name_id WHERE (' . $commodityWhere . ') ' .
                   'GROUP BY commodity_name, date ' .
                   'ORDER BY display_order, date';

            //echo $select->__toString() . '<br><br>'; exit;
            //echo $sql; exit;
            $helper->plog($sql);
            $result = $db->fetchAll($sql);
            
            $commNames = explode(',',$helper->getCommodityNames('', false));
            //var_dump($commNames);
            //get the month names
            $monthNames = array();  $i =0;
            $dates = $helper->getPreviousMonthDatesPdf(12,$upperDate,$lowerDate);
            foreach($dates as $key=>$date){
                $monthNames[] = date('F', strtotime($date));
            }
            $monthNames = array_reverse($monthNames);
            //var_dump($monthNames); exit;
            
            
            $loopStart = 0;
            for($i=0; $i<count($monthNames); $i++){
                $monthName = $monthNames[$i];
                $output[$monthName] = array();
                foreach($commNames as $comm){
                    //echo $monthName . ' ' . $comm;
                    for($j = 0; $j<count($result); $j++){
                        if($monthName == $result[$j]['month_name'] && $comm == $result[$j]['commodity_name']){
                            $output[$monthName][$comm] = $result[$j]['consumption'];
                            //$loopStart = $j;
                            break;
                        }                            
                    }
                    if(!isset($output[$monthName][$comm]))
                        $output[$monthName][$comm] = 0;
                }
            }
            
            //var_dump($output); exit;
            return $output;
       }
       
       
       public function getConsumptionBySingleCommodityOverTime($longWhereClause, $geoList, $tierText, $tierFieldName){
           $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
            $helper = new Helper2(); $output = array();

            $select = $db->select()
                         ->from(array('c'=>'commodity'), 
                                 array('SUM(consumption) AS consumption', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                         ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array('commodity_name as method'))
                         ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array())
                         ->where($longWhereClause)
                         ->group(array('commodity_name', 'date'))
                         ->order(array('display_order', 'date'));       

            //echo $select->__toString(); exit;

            $result = $db->fetchAll($select);
            
            foreach($result as $row){
                $output[$row['month_name']] = $row['consumption'];
            }
            
            //var_dump($output); exit;
            return $output;
       }
       
       
       
       public function getConsumptionByCommodityAndLocationOverTime($longWhereClause, $locationNames, $geoList, $tierText, $tierFieldName){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
            $output = array();
            $helper = new Helper2();
            
            $select = $db->select()
                         ->from(array('c'=>'commodity'), 
                                 array('SUM(consumption) AS consumption', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                         ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array('commodity_name as method'))
                         ->joinInner(array('flv'=>'facility_location_view'), 'flv.id = c.facility_id', array('lga','state','geo_zone'))
                         ->where($longWhereClause)
                         ->group(array($tierFieldName, 'c.date'))
                         ->order(array($tierText, 'c.date'));

            //echo $select->__toString(); exit;

            $result = $db->fetchAll($select);
            
            //get the month names
            $prevMonths = $helper->getPreviousMonthDates(12);
            $i = 0;
            while($i<12){
                $monthNames[] = date('F', strtotime($prevMonths[$i]));
                $i++;
            }
            $monthNames = array_reverse($monthNames);
            
            ////////////////////////////////////////
            $i = 0;
            foreach ($locationNames as $location){
                foreach ($monthNames as $monthName){
                    if($monthName == $result[$i]['month_name']){
                        $output[$monthName][$location] = $result[$i]['consumption'];
                        $i++;
                    }
                    else
                        $output[$monthName][$location] = 0;
                }
            }
            //var_dump($output); exit;
            //////////////////////////////////////////
            
//            for($i=0; $i<count($monthNames); $i++){
//                $monthName = $monthNames[$i];
//                $output[$monthName] = array();
//                $j = $i;
//                foreach($locationNames as $location){
//                    //echo 'i: ' . $i . ' j: . ' . $j . ' monthname: ' . $monthName . ' Rmonth: ' . $result[$j]['month_name'] . '<br><br>'; var_dump ($result[6]); 
//                    //echo '<br><br>';
//                    $output[$monthName][$location] = $result[$j]['consumption'];
//                    $j += 12;
//                }
//            }
//            var_dump($output); exit;
            
            //$output = $helper->primeMonthLocations($output, $locationNames, $monthNames);
            
            //var_dump($output); exit;
            return $output;
       }
}

?>
