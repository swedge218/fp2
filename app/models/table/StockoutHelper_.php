<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of StockoutHelper
 *
 * @author Swedge
 */
class StockoutHelper {
    //put your code here
    
        public function getStockoutFacsWithTrainedHWCountByLocation($longWhereClause, $geoList, $tierText, $tierFieldName){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $helper = new Helper2();
                
                $select = $db->select()
                            ->from(array('c' => 'commodity'),
                              array('COUNT(DISTINCT(c.facility_id)) AS fid_count'))

                            ->joinInner(array('cno'=>'commodity_name_option'), 'cno.id = c.name_id', array())
                            ->joinInner(array('fwtc'=>'facility_worker_training_counts_view'), 'c.facility_id = facid', array())
                            ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array('lga', 'state',  'geo_zone'))
                            ->where($longWhereClause)
                            ->group($tierFieldName)
                            ->order(array($tierText));
                        
              //echo 'CS: ' . $select->__toString() . '<br/>'; exit;

              $result = $db->fetchAll($select);
              
              $locationNames = $helper->getLocationNames($geoList);
              $locationDataArray = $helper->filterLocations($locationNames, $result, $tierText);
               
            //var_dump($locationDataArray); exit;
            return $locationDataArray;
       }
       
       public function getFacilitiesprovidingButStockkedOut($mainWhereClause, $subWhereClause, $geoList, $tierText, $tierFieldName){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $helper = new Helper2();
                
                $subselect = $db->select()
                              ->from(array('c' => 'commodity'), array('DISTINCT(c.facility_id) AS providingfacs'))

                              ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array())
                              ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array())
                              ->where($subWhereClause);
                
                $select = $db->select()
                            ->from(array('c' => 'commodity'))

                            ->joinInner(array('cno' => 'commodity_name_option'), 'cno.id = c.name_id', array())
                            ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array('lga', 'state',  'geo_zone'))
                            ->where($mainWhereClause . ' AND c.facility_id IN (' . $subselect . ')')
                            ->group($tierFieldName)
                            ->order(array($tierText));                          

              //echo $select->__toString() . '<br/><br/>'; exit;

               $result = $db->fetchAll($select);
               
            return $result;
       }
       
        public function getFacilitiesListProvidingButStockedout($mainWhereClause, $subWhereClause, $geoList, $tierText, $tierFieldName){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $helper = new Helper2();
                
                $subselect = $db->select()
                              ->from(array('c' => 'commodity'), array('DISTINCT(c.facility_id) AS providingfacs'))

                              ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array())
                              ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array())
                              ->where($subWhereClause);
                
                $select = $db->select()
                            ->from(array('c' => 'commodity'))

                            ->joinInner(array('cno' => 'commodity_name_option'), 'cno.id = c.name_id', array())
                            ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array('facility_name','lga', 'state',  'geo_zone'))
                            ->where($mainWhereClause . ' AND c.facility_id IN (' . $subselect . ')')
                            ->group("c.facility_id")
                            ->order(array($tierText));                          

             // echo $select->__toString() . '<br/><br/>'; exit;

               $result = $db->fetchAll($select);
               
              
            //var_dump($locationDataArray); exit;
            return $result;
       }
       
       public function getFacsProvidingButStockedout($mainWhereClause, $subWhereClause, $geoList, $tierText, $tierFieldName){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $helper = new Helper2();
                
                $subselect = $db->select()
                              ->from(array('c' => 'commodity'), array('DISTINCT(c.facility_id) AS providingfacs'))

                              ->joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array())
                              ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array())
                              ->where($subWhereClause);
                
                $select = $db->select()
                            ->from(array('c' => 'commodity'),
                              array('COUNT(DISTINCT(c.facility_id)) AS fid_count'))

                            ->joinInner(array('cno' => 'commodity_name_option'), 'cno.id = c.name_id', array())
                            ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = c.facility_id', array('lga', 'state',  'geo_zone'))
                            ->where($mainWhereClause . ' AND c.facility_id IN (' . $subselect . ')')
                            ->group($tierFieldName)
                            ->order(array($tierText));                          

              //echo $select->__toString() . '<br/><br/>'; exit;

               $result = $db->fetchAll($select);
               
              //filter for only valid values
              $locationNames = $helper->getLocationNames($geoList);
              $locationDataArray = $helper->filterLocations($locationNames, $result, $tierText);
               
            //var_dump($locationDataArray); exit;
            return $locationDataArray;
       }
       
       
       public function getStockoutFacsWithTrainedHWOverTime($longWhereClause){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $helper = new Helper2();
                
                //the facility_location_view is used for overtime by location calls
                $select = $db->select()
                            ->from(array('c' => 'commodity'),
                              array('COUNT(DISTINCT(c.facility_id)) AS fid_count', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                            ->joinInner(array('cno'=>'commodity_name_option'), 'cno.id = c.name_id', array())
                            ->joinInner(array('fwtc'=>'facility_worker_training_counts_view'), 'c.facility_id = facid', array())
                            ->joinInner(array('flv'=>'facility_location_view'), 'c.facility_id = flv.id', array())
                            ->where($longWhereClause)
                            ->group('date')
                            ->order(array('date'));

                //echo $select->__toString(); exit;
                
              $result = $db->fetchAll($select);
                             
            //var_dump($result); exit;
            return $result;
       }
}
?>
