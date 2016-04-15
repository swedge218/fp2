<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of NationalHelper
 *
 * @author Swedge
 */
class CoverageNationalHelper {
    //put your code here
    
    public function getNationalFacProvidingOverTime($longWhereClause){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $helper = new Helper2();
                
                $select = $db->select()
                            ->from(array('c' => 'commodity'),
                              array('COUNT(DISTINCT(c.facility_id)) AS fid_count', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                            ->joinInner(array('cno' => 'commodity_name_option'), 'cno.id = c.name_id', array())
                            ->where($longWhereClause)
                            ->group('date')
                            ->order(array('date'));   
                
             //echo 'Providing: ' . $select->__toString() . '<br/>'; exit;

              $result = $db->fetchAll($select);
               
            //var_dump($locationDataArray); exit;
            return $result;
       }
       
       
       /* TP: 
        * This method will return number of facilities that are 
        * reporting in the months covered in the date range
        * IT DOES NOT MATTER IF THE FACILITIES DO NOT HAVE TRAINED HW
        */
        public function getNationalReportingFacsOvertime($dateWhere){
               $db = Zend_Db_Table_Abstract::getDefaultAdapter ();

               $select = $db->select()
                             ->from(array('frr' => 'facility_report_rate'),
                                 array('COUNT(DISTINCT(facility_id)) AS fid_count', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                             ->where($dateWhere)
                             ->group('date')
                             ->order(array('date'));   

             //echo $sql = $select->__toString(); exit;

             $result = $db->fetchAll($select);
             return $result;
       }
       
       
       /* TP:
         * This method gets the number of facs with trained health workwes by location
         * Depending on the content of the $longWhereClause, it may be used to get
         * the count of facs trained for FP/LARC by location or total count of facs by location
         */
        public function getFacWithTrainedHWCountByLocation($ttWhere){
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $helper = new Helper2();
                
                $select = $db->select()
                            ->from(array('fwtc' => 'facility_worker_training_counts_view'),
                              array('COUNT(facid) AS fid_count'))
                            ->where($ttWhere);
                
              $result = $db->fetchRow($select);
              
            return $result['fid_count'];
       }
       
       
}

?>
