<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Report
 *
 * @author SWEDGE
 */

class Report {
    //put your code here
    
    //TP: Getting the annual date range 
    public function getAnnualDateRange($start_year,$end_year){
        
        $starts_years = array();
                        $ends_years = array();
                        for($i=$start_year; $i<=$end_year; $i++){
                           $start_date = $i."-01-01";
                           $end_date = $i."-12-31";
                           array_push($starts_years,$start_date);
                           array_push($ends_years,$end_date);
                           }
                           return array($starts_years,$ends_years);
    }
    
    
    public function get_location_category_unique($category,$condition=""){
       // $db = Zend_Db_Table_Abstract::getDefaultAdapter();
      if($category=="zone"){
        $needle = "geo_parent_id,geo_zone";
        $condi = "";
        $name = "geo_zone";
    }else if($category=="state"){
        $needle = "state_id,state";
        $name = "state";
        $condi = "WHERE geo_parent_id='$condition'";
    }else{
        $needle = "lga_id,lga";
        $name = "lga";
        $condi = "WHERE state_id='$condition'";
    }
    
    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
    $sql = "SELECT DISTINCT  ".$needle." FROM facility_location_view ".$condi."  ORDER BY `$name` ASC";
  // echo $sql;exit;
    $result = $db->fetchAll($sql);
   
    
}

public function getNameIdsUsingAlias($aliasCommodities){
    
    $nameids = array();
    foreach($aliasCommodities as $alias){
      
        $nameids[] = $this->getCommodityNameId($alias);
    }
   
    return $nameids;
}
    
public function getCommodityNameId($alias){
    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
    $sql = "SELECT id FROM commodity_name_option WHERE commodity_alias='$alias' LIMIT 1";
    $result = $db->fetchAll($sql);
    return $result[0]['id'];
}
    
    public function check_length_add_one($value){
           if(strlen($value)==1){
               $value = "0".$value;
               
           }
           return $value;
       }
       public function removeSelected($geog){
          if(isset($geog[0]) ){
           
           $size = sizeof($geog);
           $geography = array();
           if($size==1){
               if($geog[0]=="" || $geog[0]==" "){
                   return $geography;
               }else{
                 return $geog;  
               }
           }
          }else{
              return $geog;
          }
       }
    //TP: Getting the monthly date range
    public function getMonthlyDateRange($start_month,$start_year,$end_month,$end_year){
        $year_diff = $end_year-$start_year;
                        $starts_years = array();
                        $ends_years = array();
                        if($year_diff==0){
                            $month_limit = $end_month;
                            for($i=$start_month;$i<=$month_limit;$i++){
                                $i = $this->check_length_add_one($i);
                                $start_date = $end_year."-".$i."-01";
                                $end_date = $end_year."-".$i."-31";
                                array_push($starts_years,$start_date);
                                array_push($ends_years,$end_date);
                                 
                            }
                            
                             }else{
                            for($r=$start_year;$r<=$end_year;$r++){
                                if($r==$start_year){
                                    $month_start = $start_month;
                                    
                                }else{
                                    $month_start = "01";
                                }
                                
                                if($r==$end_year){
                                    $month_limit = $end_month;
                                }
                                else{
                                    $month_limit = "12";
                                }
                                
                              for($i=$month_start;$i<=$month_limit;$i++){
                                  $i = $this->check_length_add_one($i);
                                  $start_date  = $r."-".$i."-01";
                                  $end_date = $r."-".$i."-31";
                                   array_push($starts_years,$start_date);
                                array_push($ends_years,$end_date);
                              }
                                
                            }
                            
                            
                        }
                        return array($starts_years,$ends_years);
        
    }
    public function formatSelection($geog){
        $geog = array();
        if(isset($geog[0])){
        if($geog[0]=="" || $geog[0]==" "){
                   //print_r($localgovernment);
            if(is_array(($geog))){
                $geog = array_slice($geog, 1);
            }else{
                $geog = array();
            }
                    
                   // print_r($localgovernment);
                     if(sizeof($geog)==1){
                        $geog = array();
                    }
                }
        }
                return $geog;
    }
    public function explodeGeogArray($geo,$tier){
        $locations = array();
        $key = $tier-1;
        foreach($geo as $geog){
        $geoArray = explode("_",$geog);
        
        $locations[] = $geoArray[$key];
        }
        $locations = array_unique($locations);
        return $locations;
    }
    
    public function removeEmptySelection($geography){
      //  print_r($geography);exit;
        
     $key = array_search("",$geography);
               $key2 = array_search(" ",$geography);
               if($key){
                   array_splice($geography,$key,1);
               }
               if($key2){
                  array_splice($geography,$key2,1); 
               }
               $geosize = sizeof($geography);
               if($geosize<=0){
                   $geography = array();
               }
               
               
               return $geography;
}

}


?>
