<?php

/*
 * TP:
 * This class is used as a funcitons helper class
 * Created by TP as we did not want to tamper with the initial 
 * Helper class created by ITECH.
 * 
 */

/**
 * Description of Helper2
 *
 * @author Swedge
 */
class Helper2 {
    //put your code here
    
    public function getDbAdapter(){
        return $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    }
    
    /*
     * gets the latest 12 DHIS@ pull dates
     * Args: numberOfMonths - number of months to backtrack
     */
    public function getPreviousMonthDates($numberOfMonths){
        $db = $this->getDbAdapter();
        $sql = $db->select()
            ->from(array('c'=>'commodity'), 'DISTINCT(date) as dates')
            ->limit($numberOfMonths)
            ->order(array('dates DESC'))
            ->__toString();

        //echo $sql; exit;
        
        $result = $db->fetchAll($sql);
        
        //echo '1------: <br/>';
        //var_dump($result); 
        
        $dates = array();
        foreach($result as $key=>$date)
            $dates[] = $date['dates'];
        
        //echo '2------: <br/>';
        //var_dump($dates); 
        return $dates;
    }
    
    /* TP: 
   * This method will return number of facilities reporting for a particular month
   * and have trained HW either in FP or LARC
   * Args: date - the month, $training_type - the type of training to select
   * Sample: $training_type - fptrained, larctrained
   */
   public function getReportingFacilityWithTrainedHW($date, $training_type){
          $db = $this->getDbAdapter();
          if($training_type == 'fp')
              $where = "fptrained > 0";
          else if($training_type == 'larc')
              $where = 'larctrained > 0';
          
          //$where .= " AND facility_reporting_status = 1 AND date='$date'";
          $where .= " AND date='$date'";
          
          $select = $db->select()
                        -> from(array('fwtc' => 'facility_worker_training_counts_view'),
                            array('COUNT(facid) AS fid_count'))
                        -> joinInner(array('frr' => 'facility_report_rate'), 'facid = frr.facility_id', array())
                        -> where($where);
          
        $sql = $select->__toString();
	//echo 'RFTrained: ' . $sql . '<br/>'; exit;
        
        $result = $db->fetchAll($select);
	 return $result[0]['fid_count'];
  }
  
  /* TP: 
   * This method will return number of facilities that have trained HW either in FP or LARC
   * Args: $training_type - the type of training to select
   * Sample: $training_type - fptrained, larctrained
   */
   public function getFacilityWithTrainedHWCount($training_type){
          $db = $this->getDbAdapter();
          if($training_type == 'fp')
              $where = "fptrained > 0";
          else if($training_type == 'larc')
              $where = 'larctrained > 0';
          
          $select = $db->select()
                        -> from(array('fwtc' => 'facility_worker_training_counts_view'),
                            array('COUNT(facid) AS fid_count'))
                        -> where($where);
          
        $sql = $select->__toString();
	//echo 'RFTrained: ' . $sql . '<br/>'; exit;
        
        $result = $db->fetchAll($select);
	 return $result[0]['fid_count'];
  }
  
  
  /* TP: 
   * This method will return number of facilities reporting for a particular month
   * and have trained HW either in FP or LARC and have consumption for that month as well
   * Args: date - usually first day of the month to represent the month, 
   *       $commodity_type - the type of commodity to select
   * Sample: $training_type - fptrained, larctrained
   */
  public function getReportingConsumptionFacilities($date, $commodity_type=''){
      $db = $this->getDbAdapter();
      if($commodity_type == 'fp') //fp covers both types of commodities
          $where = "(commodity_type = 'fp' OR commodity_type = 'larc') AND fptrained > 0";
      else if($commodity_type == 'larc')
          $where = "commodity_type = 'larc' AND larctrained > 0";
      
      $where .= ' AND facility_reporting_status = 1 AND consumption > 0 AND c.date = \'' . $date . '\'';
      
          $select = $db->select()
                        -> from(array('fwtc' => 'facility_worker_training_counts_view'),
                            array('COUNT(DISTINCT(c.facility_id)) AS fid_count'))
                        -> joinInner(array('c' => 'commodity'), 'facid = c.facility_id', array())
                        -> joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array())
                        -> where($where); 
            //echo 'CS Cnsmp: ' . $select->__toString() . '<br/>'; exit;;
        
         $result = $db->fetchAll($select);
	 return $result[0]['fid_count'];
  }


  /* TP: 
   * This method will return number of facilities that have trained HW  
   * and are reporting in the months covered in the date range arg
   * grouped by date. This does not need location related arguments
   */
   public function getReportingFacsWithTrainedHWCountOvertime($dateWhere){
          $db = $this->getDbAdapter();
          
          $select = $db->select()
                        -> from(array('fwtc' => 'facility_worker_training_counts_view'),
                            array('COUNT(facid) AS fid_count'))
                        ->joinInner(array('frr'=>'facility_report_rate'), 'facid = frr.facility_id', array('MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                        ->where($dateWhere)
                        ->group('date')
                        ->order(array('date'));
          
        $sql = $select->__toString();
	//echo 'RFTrained: ' . $sql . '<br/>'; exit;
        
        $result = $db->fetchAll($select);
	return $result;
  }
  
  
  
  /* TP: 
   * This method will return number of facilities that have trained HW  
   * and are reporting in the months covered in the date range arg
   */
  
   public function getReportingFacsWithTrainedHWOvertimeByLocation($longWhereClause, $geoList, $tierText, $tierFieldName){
          $db = $this->getDbAdapter();
          
          $select = $db->select()
                        ->from(array('fwtc' => 'facility_worker_training_counts_view'),
                            array('COUNT(DISTINCT(facid)) AS fid_count'))
                        ->joinInner(array('frr'=>'facility_report_rate'), 'facid = frr.facility_id', array('MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                        ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = facility_id', array('lga', 'state', 'geo_zone'))
                        ->where($longWhereClause)
                        ->group(array($tierFieldName, 'date'))
                        ->order(array($tierText,'date'));
          
        //echo $sql = $select->__toString(); exit;
        
        $result = $db->fetchAll($select);
        
        //filter for only valid values
        $locationNames = $this->getLocationNames($geoList);
        $locationDataArray = $this->filterLocations($locationNames, $result, $tierText);
        
	return $locationDataArray;
  }
  

  /* TP: 
   * This method will return number of facilities that are 
   * reporting in the months covered in the date range 
   * IT DOES NOT MATTER IF THE FACILITIES DO NOT HAVE TRAINED HW
   * IT DOES NOT CONSIDER ANY LOCATION(S)
   */
   public function getReportingFacsOvertime($longWhereClause){
          $db = $this->getDbAdapter();
          
          $select = $db->select()
                        ->from(array('frr' => 'facility_report_rate'),
                            array('COUNT(DISTINCT(facility_id)) AS fid_count', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                        ->where($longWhereClause)
                        ->group('date')
                        ->order(array('date'));   
          
        //echo $sql = $select->__toString(); exit;
        
        $result = $db->fetchAll($select);        
	return $result;
  }
  
  
  /* TP: 
   * This method will return number of facilities that are 
   * reporting in the months covered in the date range and locations provided arg
   * IT DOES NOT MATTER IF THE FACILITIES DO NOT HAVE TRAINED HW
   */
   public function getReportingFacsOvertimeByLocation($longWhereClause, $geoList, $tierText, $tierFieldName){
          $db = $this->getDbAdapter();
          
          $select = $db->select()
                        ->from(array('frr' => 'facility_report_rate'),
                            array('COUNT(DISTINCT(facility_id)) AS fid_count', 'MONTHNAME(date) as month_name', 'YEAR(date) as year'))
                        ->joinInner(array('flv' => 'facility_location_view'), 'flv.id = facility_id', array('lga', 'state', 'geo_zone'))
                        ->where($longWhereClause)
                        ->group(array($tierFieldName, 'date'))
                        ->order(array($tierText, 'date'));   
        
         $sql = $select->__toString(); 
         //echo $sql; exit;
       // echo $select->__toString();exit;
       //   echo $select->__toString();echo $tierText.'<br/><br/>';
       
          $result = $db->fetchAll($select);
//         if($tierText=="lga"){
//                   $geoList = implode(",",$this->fetchlocwithparentid($geoList));
//                   //print_r($geoList);exit;
//               }
        
        //filter for only valid values
        $locationNames = $this->getLocationNames($geoList);
        $locationDataArray = $this->filterLocations($locationNames, $result, $tierText);
        
	return $locationDataArray;
  }
  
  /*TP:
   * This method gets the parent id of a given location id
   * Args: the location id of a given location
   */
  public function getParentID($location_id){
       $db = $this->getDbAdapter();
    
        $sql = $db->select()
                  ->from(array('l'=>'location'))
                  ->where("l.id ='$location_id' ");
  
        //echo $sql->__toString();exit;
        $result = $db->fetchAll($sql);
       
        $parent_id = $result[0]['parent_id'];
        return $parent_id;
  }
  
  
  
  /* TP: 

   * This method will return facility IDs of reporting facilities for the past 6 months 
   * from date parameter
   * Args: date - usually first day of the month to represent the month
   *       returnSql - whether to return just the sql generated or actually execute it and return resuls
   */
  public function getFacilitiesWithConsumptionInLastSixMonths($date, $commodity_type, $returnSql = false){
      $db = $this->getDbAdapter();
      if($commodity_type == 'fp'){
        $temp = $db->select()
                  ->from(array('c'=>'commodity'), 'DISTINCT(facility_id) as facility_id')
                  ->where("c.date >= DATE_SUB('$date', INTERVAL 6 MONTH) AND c.date <= '$date'" .
                            " AND consumption > 0");
          //echo 'fp6: <br/>'; 
      }
      else if($commodity_type == 'larc'){
          $temp = $db->select()
                  ->from(array('c'=>'commodity'), 'DISTINCT(facility_id) as facility_id')
                  ->joinInner(array('cno'=>'commodity_name_option'), "cno.id = c.name_id AND commodity_type = 'larc'")
                  ->where("c.date >= DATE_SUB('$date', INTERVAL 6 MONTH) AND c.date <= '$date'" .
                          " AND consumption > 0");
          //echo 'larc6: <br/>'; 
      }
      
      $sql = $temp->__toString(); 
      $sql = str_replace('`facility_id`,', 'facility_id', $sql);
      $sql = str_replace('`cno`.*', '', $sql);
      //if($commodity_type =='larc') {echo $sql; exit;}
     
      return $returnSql ? $sql : $db->fetchAll($sql);
  }
  
  /* TP: 
   * This method will return number of facilities reporting for a particular month
   * and have trained HW either in FP or LARC and have consumption for that month as well
   * Args: date - the month, $training_type - the type of training to select
   * Sample: $training_type - fptrained, larctrained
   */
  public function getReportingStockedOutFacilitiesWithTrainedHW($date = null, $commodity_type){
      $db = $this->getDbAdapter();
      if($commodity_type == 'fp') //fp covers both types of commodities
          //$where = "(commodity_type = 'fp' OR commodity_type = 'larc') AND fptrained > 0 AND c.stock_out = 'Y'";
          $where = "commodity_alias = 'so_fp_seven_days' AND fptrained > 0 AND c.stock_out = 'Y'";
      else if($commodity_type == 'larc')
          $where = "commodity_alias = 'so_implants' AND larctrained > 0 AND c.stock_out = 'Y'";
          
      $where .= ' AND facility_reporting_status = 1 AND c.date = \'' . $date . '\'';
      
          $select = $db->select()
                        -> from(array('c' => 'commodity'),
                            array('DISTINCT(c.facility_id) AS fid'))
                        -> joinInner(array('fwtc' => 'facility_worker_training_counts_view'), 'facid = c.facility_id', array())
                        -> joinInner(array('cno' => 'commodity_name_option'), 'c.name_id = cno.id', array())
                        -> where($where);          
	
         //echo 'CS Stockout: ' . $select->__toString() . '<br/>'; exit;;
        
                    
         return $result = $db->fetchAll($select);
	 //return $result[0]['fids'];
  }
  
  public function getReportingStockedOutFacilitiesWithTrainedHWCount($date, $commodity_type){
      $stockedOutFacs = $this->getReportingStockedOutFacilitiesWithTrainedHW($date, $commodity_type);
      return count($stockedOutFacs);
  }

  
    public function  getStockedOutFacilities($date, $commodity_type){
        $db = $this->getDbAdapter();
        if($commodity_type == 'fp')
            $where = "cno.commodity_alias = 'so_fp_seven_days' AND c.stock_out = 'Y'";
        else if($commodity_type == 'larc')
            $where = "cno.commodity_alias = 'so_implants' AND c.stock_out = 'Y'";

        $select = $db->select()
                      ->from(array('c'=>'commodity'), 'DISTINCT(facility_id)')
                      ->joinInner(array('cno'=>'commodity_name_option'), array())
                      ->where("cno.id = c.name_id AND " . $where . " AND c.date='" . $date . "'")
                      ->order(array('facility_id'));
        //echo $sql = $select->__toString(); exit;
        
        $result = $db->fetchAll($select);
        
        $facs = array();
        foreach($result as $fac)
            $facs[] = $fac['facility_id'];
        
        return $facs;
    }
  
    
    public function getLocationFacilityIDs($where){
        if(empty($where)) return '';
        
        $db = $this->getDbAdapter();
        
        $select = $db->select()
                     ->from(array('flv' => 'facility_location_view'), 'id AS facility_id')
                     ->where($where);
        
        //echo $select->__toString(); exit;
        $result = $db->fetchAll($select);
        
        $facs = '';
        foreach($result as $fac)
            $facs .=  $fac['facility_id'] . ',';
        
        $facs = substr(trim($facs),0, -1); 
        return $facs;
    }
    
    public function getLocationTierText($tierValue){
        $text = '';
        switch($tierValue){
            case 3:
                $text = 'lga';
                break;
            case 2:
                $text = 'state';
                break;
            case 1:
                $text = 'geo_zone';
                break;
            default :
                break;
        }
        
        return $text;
    }
    
    
    public function getTierFieldName($tierText){
        $text = '';
        switch($tierText){
            case 'lga':
                $text = 'flv.location_id';
                break;
            case 'state':
                $text = 'flv.parent_id';
                break;
            case 'geo_zone':
                $text = 'flv.geo_parent_id';
                break;
            default :
                break;
        }
        
        return $text;
    }
    
    
    public function getLocationNames($locationIDList){
        $db = $this->getDbAdapter();
        $select = $db->select()
                      ->from(array('l'=>'location'),array('location_name','id'))
                      ->where('l.id IN (' . $locationIDList . ')')
                      ->order(array('location_name'));
        //echo $select->__toString(); exit;
        $result = $db->fetchAll($select->__toString());
        
        $namesArray = array();
        foreach($result as $row)
            $namesArray[$row['id']] = $row['location_name'];
        
        return $namesArray;
    }
    
    
    public function getLocationTierIDs($tierValue){
        $db = $this->getDbAdapter();
        $select = $db->select()
                      ->from(array('l'=>'location'),'id')
                      ->where('tier =' . $tierValue);
        //echo $select->__toString(); exit;
        $result = $db->fetchAll($select);
        
        $array = array();
        foreach($result as $row)
            $array[] = $row['id'];
        
        return $array;
    }
    
    
    public function getLatestPullDate(){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select()
                      ->from(array('frr' => 'facility_report_rate'), 'MAX(frr.date) as maxdate');
	
	    $result = $db->fetchRow($select); 
            
            return $result['maxdate'];
            //return '2014-12-01';
    }

    

    public function fetchTitleDate() {            
            $maxdate = $this->getLatestPullDate();
            $titleDate = array(
                            'month_name' => date('F', strtotime($maxdate)),
                            'year' => date('Y', strtotime($maxdate))
                        );

	    return $titleDate;
	}
        
    public function sumNumersAndDenoms($numerators, $denominators){
        $numerSum = $denomSum = 0; $output = array();
        foreach ($numerators as $location=>$numer){
            $nationalNumerator += $numer;
            $nationalDenominator += $denominators[$location];

            $output[] = array(
                        'location' => $location,
                        'percent' => $numer / $denominators[$location]
            );

            $numerSum += $numer;
            $denomSum += $denominators[$location];
        }
        
        //divide national avg by length of national zones
        $nationalAvg = $numerSum / $denomSum;
        
        return array('output'=>$output, 'nationalAvg' => $nationalAvg);
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
                            $locationValue = $entry['fid_count']; 
                            break;
                        }
                    }

                    if($locationValue == '')
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
       
       
       //add all missing months for each location.
       public function filterMonths($monthNames, $locationArray, $focusLocation, $tierText, $monthField){
           $monthDataArray = array(); $monthValue = 0;
           if(!empty($locationArray)){
                foreach($monthNames as $key=>$monthName){
                    $monthValue = '';
                    foreach($locationArray as $entry){
                        if($monthName == $entry[$monthField]){                                    
                            $monthValue = $entry['fid_count']; 
                            break;
                        }
                    }

                    if($monthValue == '')
                        $monthValue = 0;

                    $monthDataArray[] = array(
                                'month_name' => $monthName,
                                $tierText => $focusLocation,
                                'fid_count' => $monthValue
                        );
                }
            }
            else{
                //echo 'empty: ' . $tierText; exit;
                foreach($monthNames as $key=>$monthName)
                    $monthDataArray[] = array(
                                'month_name' => $monthName,
                                $tierText => $focusLocation,
                                'fid_count' => $monthValue
                        );
            }
            
            return $monthDataArray;
       }
       
       
       //add all missing months and locations for the array
       //Array format: $output[$monthName][$location] = $result[$j]['consumption'];
       public function primeMonthLocations($dataArray, $locationArray, $monthNames){
           //var_dump($monthNames); echo '<br><br>';
           //var_dump($locationArray); echo '<br><br>';
           
           if(!empty($dataArray)){
               for($i=0; $i < count($monthNames); $i++){
                   $month = $monthNames[$i];
                   if(array_key_exists($month, $dataArray)){
                        foreach($locationArray as $location){
                            $value = $dataArray[$month][$location];
                            $dataArray[$month][$location] = !empty($value) ? $value : 0;
                        }
                   }
                   else{
                        $insertArray = array();
                        foreach($locationArray as $location){
                            $insertArray[$month][$location] = 0;
                        }
                        $dataArray = array_splice($dataArray, i, 0, $insertArray);
                   }
               }
           }
           else{
               for($i=0; $i < count($monthNames); $i++){
                   $month = $monthNames[$i];
                   $insertArray = array();
                    foreach($locationArray as $location){
                        $insertArray[$month][$location] = 0;
                    }
                    $dataArray[] = $insertArray;
               }
           }
           //var_dump($dataArray);exit;
           return $dataArray;
       }
       
       
       public function addlocationnames(array $sortedArray){
           $length = sizeof($sortedArray);
           for($i=0;$i<$length;$i++){
              $locname =  $sortedArray[$i]['location'];
              
              $location_id = $this->fetchlocaid($locname);
              $sortedArray[$i]['location_id'] = $location_id;
           }
           return $sortedArray;
       }
       
       
       public function fetchlocaid($locname){
           $locationname = strtolower(trim($locname));
          $db = Zend_Db_Table_Abstract::getDefaultAdapter();
           $select = $db->select()
                    ->from (array('loc'=>'location'), array('id'))
                     ->where ("location_name='$locationname'");
           $result = $db->fetchRow($select);
           //echo $select->__toString();exit;
            return $result['id'];  
       }
       
       public function fetchlocwithparentid($parent_id){
            //echo $parent_id;exit;
          $db = Zend_Db_Table_Abstract::getDefaultAdapter();
           $select = $db->select()
                    ->from (array('loc'=>'location'), array('id'))
                     ->where ("parent_id IN ('$parent_id')");
           $result = $db->fetchAll($select);
           $locations = array();
           //echo $select->__toString();exit;
           foreach($result as $loc){
               $locations[] = $loc['id'];
           }
            return $locations; 
       }
       
       public function getAllReportingFacsCount($date){
           $db = Zend_Db_Table_Abstract::getDefaultAdapter();
           $select = $db->select()
	    -> from(array('facility_report_rate' => 'facility_report_rate'),
                    array('COUNT(DISTINCT(facility_external_id)) as count'))
                    ->where("date='$date'");
           
            //echo $select->__toString(); exit;
           
           $result = $db->fetchAll($select);
           return $result[0]['count'];
       }
       
       public function coverageCalculations($cs_details){
           
            $cs_calc = array(
                        'cs_fp_trained_facility_count' => round($cs_details['fp_trained_facility_count']/$cs_details['total_facility_count'], 2),
                        'cs_larc_trained_facility_count' => round($cs_details['larc_trained_facility_count']/$cs_details['total_facility_count'], 2),
                        'cs_fp_consumption_facility_count' => round($cs_details['fp_consumption_facility_count']/$cs_details['fp_reporting_facility_count'], 2),
        	        'cs_larc_consumption_facility_count' => round($cs_details['larc_consumption_facility_count']/$cs_details['larc_reporting_facility_count'], 2),
                        'cs_fp_stock_out_facility_count' => round($cs_details['fp_stock_out_facility_count']/$cs_details['fp_reporting_facility_count'], 2),
                        'cs_larc_stock_out_facility_count' => round($cs_details['larc_stock_out_facility_count']/$cs_details['larc_reporting_facility_count'], 2),
                        //'cs_date' => date_format(date_create($cs_details['last_date']), 'F Y'),
                    );
            
            return $cs_calc;
        }
        
        public function getCommodities($commodity_type=''){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            if($commodity_type =='fp')
                $commodityWhere = "commodity_type = 'fp'";
            else if($commodity_type == 'larc')
                $commodityWhere = "commodity_type = 'larc'";
            else
                $commodityWhere = "commodity_type = 'fp' OR commodity_type = 'larc'";
            
            $select = $db->select()
                         ->from(array('cno'=>'commodity_name_option'), array('id', 'commodity_name'))
                         ->where($commodityWhere)
                         ->order(array('display_order'));
            
            $result = $db->fetchAll($select);
            return $result;
        }
        
        public function getCommodityNames($commodity_type='', $keysOnly = false){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            if($commodity_type =='fp')
                $commodityWhere = "commodity_type = 'fp'";
            else if($commodity_type == 'larc')
                $commodityWhere = "commodity_type = 'larc'";
            else
                $commodityWhere = "commodity_type = 'fp' OR commodity_type = 'larc'";
            
            $select = $db->select()
                         ->from(array('cno'=>'commodity_name_option'), array('id', 'commodity_name'))
                         ->where($commodityWhere)
                         ->order(array('display_order'));
            
            $result = $db->fetchAll($select);
            
            $values ='';
            if($keysOnly){
                foreach($result as $row)
                    $values .=  "'" . $row['id'] . "',";
                    $values = substr($values, 0, -1);
            }
            else{
                 foreach($result as $row)
                     $values .=  $row['commodity_name'] . ",";
                 $values = substr($values, 0, -1);
            }

            return $values;
        }
        
        
        public function getCommodityName($commID){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            if($commID == 0) return '';
            
            $select = $db->select()
                         ->from(array('cno'=>'commodity_name_option'), array('commodity_name'))
                         ->where('cno.id = ' . $commID);
            
            $result = $db->fetchRow($select);
            return $result['commodity_name'];            
        }
        
        
        function doOverTimePercents($numerArray, $denomArray){
            $output = array();
            foreach ($numerArray as $i=>$numer){
               $output[] = array(
                           'month' => $numer['month_name'],
                           'year' => $numer['year'],
                           'percent' => $numer['fid_count'] / $denomArray[$i]['fid_count']
               );
           }
           return $output;
        }
        
        
        public function msort($array,$order="ASC"){
            for($i=0; $i<count($array)-1; $i++){                
                for($j=$i+1; $j<count($array); $j++){
                    if($order=="ASC"){
                    if($array[$i]['percent'] > $array[$j]['percent']){
                        $temp = $array[$i];
                        $array[$i] = $array[$j];
                        $array[$j] = $temp;
                    }
                    }else{
                       if($array[$i]['percent'] < $array[$j]['percent']){
                        $temp = $array[$i];
                        $array[$i] = $array[$j];
                        $array[$j] = $temp;
                    }  
                    }
                    
                    
                    
                }
            }
            
            //echo '<br/>bros<br>';
            //var_dump($array); exit;
            return $array;
        }
}

?>
