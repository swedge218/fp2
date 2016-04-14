<?php

require_once ('app/controllers/ITechController.php');
require_once ('models/table/Helper2.php');
class ReportFilterHelpers extends ITechController {

	public function __construct($request, $response, $invokeArgs = array()) {
		parent::__construct ( $request, $response, $invokeArgs = array () );

	}

	/**
	 * Get the location filter values
	 *
	 * @param unknown_type $criteria
	 * @param unknown_type $prefix
	 *
	 * return $criteria, location tier, location id, city, and city_parent_id
	 */
	protected function getLocationCriteriaValues($criteria = array(), $prefix = '') {
	if ( $prefix != '' ) $prefix .= '_';

		$criteria [$prefix.'city'] = $this->getSanParam ( $prefix.'city' ); // set city
		$rgns = array('province_id', 'district_id','region_c_id','region_d_id','region_e_id','region_f_id','region_g_id','region_h_id','region_i_id');
		// get value from each region sent by form
		foreach($rgns as $rgn_name) {
			$tmp = $this->getSanParam($prefix.$rgn_name);
			if (is_array ( $tmp ) ) {
				if ( $tmp [0] === "") { // "All"
					$criteria [$prefix.$rgn_name] = array ();
			} else {
					foreach($tmp as $key => $val) {
						if (strstr ( $val, '_' ) !== false) {
							$parts = explode ( '_', $val );
							#$tmp [$key] = $parts [count($parts)-1];
							$tmp [$key] = array_pop($parts);
						}
					}
					$criteria [$prefix.$rgn_name] = $tmp;
				}
			} else {
				if (strstr ( $tmp, '_' ) !== false) {
					$parts = explode ( '_', $tmp );
					$tmp = array_pop($parts);
				}
				$criteria [$prefix.$rgn_name] = $tmp;
			}
		}

		$city_parent_id = 0; // todo: small bug here, on receiving array input for region_ids, city_parent_id returns an array of ids, possibly even wrong ids -- probably ok - its not used in reports anyway...
		if ( $this->setting ( 'display_region_i' ) ) {
			$city_parent_id = $criteria[$prefix.'region_i_id'];
		} else if ( $this->setting ( 'display_region_h' ) ) {
			$city_parent_id = $criteria[$prefix.'region_h_id'];
		} else if ( $this->setting ( 'display_region_g' ) ) {
			$city_parent_id = $criteria[$prefix.'region_g_id'];
		} else if ( $this->setting ( 'display_region_f' ) ) {
			$city_parent_id = $criteria[$prefix.'region_f_id'];
		} else if ( $this->setting ( 'display_region_e' ) ) {
			$city_parent_id = $criteria[$prefix.'region_e_id'];
		} else if ( $this->setting ( 'display_region_d' ) ) {
			$city_parent_id = $criteria[$prefix.'region_d_id'];
		} else if ( $this->setting ( 'display_region_c' ) ) {
			$city_parent_id = $criteria[$prefix.'region_c_id'];
    } else if ( $this->setting ( 'display_region_b' ) ) {
			$city_parent_id = $criteria[$prefix.'region_b_id'];
    } else {
			$city_parent_id = $criteria['_id'];
    }
    $criteria [$prefix.'city_parent_id'] = $city_parent_id;


    $location_tier = 1;
    $location_id = $criteria [$prefix.'province_id'];
    if ( $criteria [$prefix.'district_id'] ) {
      $location_id = $criteria [$prefix.'district_id'];
      $location_tier = 2;
    }
    if ( $criteria [$prefix.'region_c_id'] ) {
      $location_id = $criteria [$prefix.'region_c_id'];
      $location_tier = 3;
    }
		if ( $criteria [$prefix.'region_d_id'] ) {
			$location_id = $criteria [$prefix.'region_d_id'];
			$location_tier = 4;
		}
		if ( $criteria [$prefix.'region_e_id'] ) {
			$location_id = $criteria [$prefix.'region_e_id'];
			$location_tier = 5;
		}
		if ( $criteria [$prefix.'region_f_id'] ) {
			$location_id = $criteria [$prefix.'region_f_id'];
			$location_tier = 6;
		}
		if ( $criteria [$prefix.'region_g_id'] ) {
			$location_id = $criteria [$prefix.'region_g_id'];
			$location_tier = 7;
		}
		if ( $criteria [$prefix.'region_h_id'] ) {
			$location_id = $criteria [$prefix.'region_h_id'];
			$location_tier = 8;
		}
		if ( $criteria [$prefix.'region_i_id'] ) {
			$location_id = $criteria [$prefix.'region_i_id'];
			$location_tier = 9;
		}

    return array($criteria, $location_tier, $location_id);
  }

	// helper to generate a where clause based on 9 available levels of regions in $criteria
	// return value: " `$tablePrefix`.region_X_id IN (val, val, val) ";
	protected function getLocationCriteriaWhereClause(&$criteria, $prefix = '', $tablePrefix = '') {

		$tableCols = array('', 'province_id', 'district_id', 'region_c_id','region_d_id','region_e_id','region_f_id','region_g_id','region_h_id','region_i_id');
		list($crit, $tier, $location_id) = $this->getLocationCriteriaValues($criteria, $prefix, $tablePrefix);

		if ($prefix)
			$prefix = $prefix . "_";

		$tableString = $tablePrefix ? "$tablePrefix." : '';

		if ($location_id)
			$location_id = $this->_array_me($location_id); // sometimes $location_id is a string because it comes from a form input[]

		if ($tier && !empty ( $location_id ))
			return " " . $tableString . $tableCols[$tier] . " IN ( " . implode(',', $location_id) . " ) ";

		return false;
	}

	//_is_filter_all
	// is string (pass) or array with out "" (pass)
	// "" is used for --ALL-- in drop downs, now we are supporting multi-value <selects>
	protected function _is_filter_all($arrOrStr)
	{
		if (is_array($arrOrStr)) {
			if (!in_array("", $arrOrStr))
				return false;
			else
				return true;
		}else if ($arrOrStr || $arrOrStr === '0') {  //not really used, should be safe though
			return false;
		}else if ($arrOrStr == "") {
			return true;
		}
		return false;
	}

	protected function _is_not_filter_all($arrOrStr)
	{
		return (! $this->_is_filter_all($arrOrStr));
	}
        
        
        /*TP:
         * This method will operate on the post variable from form and 
         * construct the right parameters to be used for calls to the model methods
         * This method should be usable by all controllers
         */
        public function buildParameters(){
            $selectionLimit = 6;
            $geoList='';
            $tierValue = 0;
            $helper = new Helper2();
                
                
            if( isset($_POST["region_c_id"]) && 
                (count($_POST["region_c_id"])>1 ||
                (count($_POST["region_c_id"])==1 AND !empty($_POST["region_c_id"][0]))))
            { // CHAINigeria LGA: TP changing this if statement to be more robust
                if(count($_POST['region_c_id']) > $selectionLimit) 
                    $_POST['region_c_id'] = array_slice ($_POST['region_c_id'], 0, $selectionLimit);

                foreach ($_POST['region_c_id'] as $i => $value){
                    if($value == '') continue;
                    $geo = explode('_',$value);
                    $geoList .= '\'' . $geo[2]. '\', ';
                }

                $geoList = substr(trim($geoList), 0, -1);  //remove trailing comma
                $tierValue = 3;

            } else if( isset($_POST["district_id"]) && 
                (count($_POST["district_id"])>1 || 
                (count($_POST["district_id"])==1 AND !empty($_POST["district_id"][0]))))
            {
                if(count($_POST['district_id']) > $selectionLimit) 
                    $_POST['district_id'] = array_slice ($_POST['district_id'], 0, $selectionLimit);

                foreach ($_POST['district_id'] as $i => $value){
                    if($value == '') continue;
                    $geo = explode('_',$value);
                    $geoList .= '\'' . $geo[1]. '\', ';
                }

                $geoList = substr(trim($geoList), 0, -1);  //remove trailing comma
                $tierValue = 2;


            } else if( isset($_POST["province_id"]) && 
                (count($_POST["province_id"])>1 || 
                (count($_POST["province_id"])==1 AND !empty($_POST["province_id"][0]))))
            {
                if(count($_POST['province_id']) > $selectionLimit) 
                    $_POST['province_id'] = array_slice ($_POST['province_id'], 0, $selectionLimit);

                //$where .= 'AND flv.geo_parent_id IN (';
                foreach ($_POST['province_id'] as $i => $value){
                    if($value == '') continue;
                    $geo = explode('_',$value);
                    $geoList .= '\'' . $geo[0]. '\', ';
                }

                $geoList = substr(trim($geoList), 0, -1);  //remove trailing comma
                $tierValue = 1;
            }
            else { //no geo selection
                $tierValue = 1;
                $geoIDsArray = $helper->getLocationTierIDs($tierValue);
                foreach($geoIDsArray as $key=>$geoid)
                    $geoIDsArray[$key] = "'$geoid'";

                //var_dump($geoIDsArray); exit;
                $geoList = implode(',', $geoIDsArray);
            }
            
            return array($geoList, $tierValue);
      }
}

?>