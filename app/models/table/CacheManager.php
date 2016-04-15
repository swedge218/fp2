<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DataCache
 *
 * @author Swedge
 * Techie Planet
 */

class CacheManager {
    //put your code here
    const CONSUMPTIONON_BY_METHOD = 'consumption_by_method';
    
    const HR_SUMMARY = 'hr_summary';
    
    const PERCENT_FACS_PROV_OVERTIME_FP = 'percent_facs_prov_over_time_fp';
    const PERCENT_FACS_PROV_OVERTIME_LARC = 'percent_facs_prov_over_time_larc';
    
    const STOCK_OUTS = 'stock_outs';
    
    const PERCENT_FACS_TRAINED_FP = 'percent_facs_trained_fp';
    const PERCENT_FACS_TRAINED_LARC = 'percent_facs_trained_larc';
    
    const PERCENT_FACS_PROVIDING_FP = 'percent_facs_providing_fp';
    const PERCENT_FACS_PROVIDING_LARC = 'percent_facs_providing_larc';
    const PERCENT_FACS_PROVIDING_INJECTABLES = 'percent_facs_providing_inj';
    
    const PERCENT_FACS_HW_PROVIDING_FP = 'percent_facs_hw_providing_fp';
    const PERCENT_FACS_HW_PROVIDING_LARC = 'percent_facs_hw_providing_larc';
    
    const PERCENT_COVERAGE_OVERTIME_FP = 'percent_coverage_overtime_fp';
    const PERCENT_COVERAGE_OVERTIME_LARC = 'percent_coverage_overtime_larc';
    
    const PERCENT_PROVIDING_OVERTIME_FP = 'percent_providing_overtime_fp';
    const PERCENT_PROVIDING_OVERTIME_LARC = 'percent_providing_overtime_larc';
    
    const PERCENT_FACS_HW_STOCKED_OUT_FP = 'percent_facs_hw_stocked_out_fp';
    const PERCENT_FACS_HW_STOCKED_OUT_LARC = 'percent_facs_hw_stocked_out_larc';
    
    const PERCENT_PROVIDING_STOCKED_OUT_FP = 'percent_providing_stock_out_fp';
    const PERCENT_PROVIDING_STOCKED_OUT_LARC = 'percent_providing_stock_out_larc';
    
    const STOCK_OUTS_OVERTIME_FP = 'stock_outs_overtime_fp';
    const STOCK_OUTS_OVERTIME_LARC = 'stock_outs_overtime_larc';
    
    
    
    
    const MAX_GEO_SELECTION = 6;
    const TOP_LEVEL_TIER = 1;
    
    public function getIndicator($alias, $date){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        
        $whereClause = "indicator_alias = '$alias' AND date_cached = '$date'";
        
        $select = $db->select()
                     ->from(array('dc'=>'data_cache'), array('value'))
                     ->where($whereClause);
        
        $result = $db->fetchRow($select);
        return $result['value'];
    }
    
    public function setIndicator($dataArray){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        $db->insert('data_cache', $dataArray);
    }
    
    public function updateIndicator($dataArray, $where){
//        print_r($dataArray);
//        print_r($where);
//        exit;
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        //echo $where;exit;
        $db->update('data_cache', $dataArray, $where);
       // print $data->__toString();
    }
    
    public function findIndicator(){
        
    }
}

?>