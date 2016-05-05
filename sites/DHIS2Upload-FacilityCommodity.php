<?php
/*
 * EXPORT THE INSERTED ROWS INTO A FILE 
 * UPLOAD THE FILE TO UPDATE THE WEB DATABASE
 * mysqldump -h localhost -u root -p --where ="date='2015-04-01'"
 * itechweb_chainigeria commodity > filename.sql
 */

/* 
 * 1. read 'commodity-names-ids' file with list of commodity ids (from Akinsola file 'DHIS2-commodities-ids.xlsx'  - first column)
 * 2. read web service with facility names and commodity data (or 'commodity-data-json' file)
 * all names: commodity names, facility names, states, zones, LGAs
	[UID]=>[name]
	hierarchy: [facility UID]=>/[skip UID]/[state UID]/[LGA UID]/[skip UID]
	
	Zones and states are hardcoded in database and never updated (no stored UIDs).
	UID are stored only for LGAs, facilities, commodities names.
	UIDs are store for identification.
	
	commodity data: [facility type - we do not need],[commodity name],[facility name],[consumption]
*/

ini_set('display_errors', 'On');

//$DB_NAME = 'dev_test'; // globals is being required down in the code. This file(globals) then overwrites any db connection that might have been established earlier

//constants
$PERIOD_LAST_MONTH_MODE = false;
$PERIOD_HISTORICAL_MODE = false;
//DO NOT CHANGE these values
$UPDATE_FACILITY_MODE = true;
$UPDATE_COMMODITY_NAMES_MODE = true;
$UPDATE_COMMODITY_DATA_MODE = true;

$DEBUG_MODE = false;

$PERIOD_LAST_MONTH = 'LAST_MONTH';

//set to lagos timezone and check that we are on the 25th
date_default_timezone_set('Africa/Lagos');

$PERIOD_HISTORICAL = date("Ym",strtotime("-1 month")); //"201503";//"201103;201104;201105;201106;201107;201108;201109;201110;201111;201112;201201;201202;201203;201204;201205;201206;201207;201208;201209;201210;201211;201212;201301;201302;201303;201304;201305;201306;201307;201308;201309;201310;201311;201312;201401;201402;201403;201404;201405;201406;201407;201408;201409;201410";
plog('Starting COMM log for ' . $PERIOD_HISTORICAL);

//TP
$DATA_URL_START = "https://dhis2nigeria.org.ng/dhis/api/analytics.json?dimension=dx:DiXDJRmPwfh;EIHpURrBm7K;G5mKWErswJ0;H8A8xQ9gJ5b;JyiR2cQ6DZT;QlroxgXpWTL;eChiJMwaOqm;ibHR9NQ0bKL;krVqq8Vk5Kw;mvBO08ctlWw;pYhpegHDt4x;vDnxlrIQWUo;w92UxLIRNTl;wNT8GGBpXKL;yJSLjbC9Gnr&dimension=ou:LEVEL-5;s5DPBsdoE8b&filter=pe:";
$DATA_URL_END = "&hierarchyMeta=true&displayProperty=NAME&ignoreLimit=true";

$USERNAME = "FP_Dashboard";
$PASSWORD = "CHAI12345";

//format: [commodity UID];[name];[out of stock UID]
$COMMODITY_NAMES_IDS_FILE = 'commodity-names-ids';

//Set stock out indicators: if consumption is 0 then stock out 'N', otherwise 'Y'
// separated by comma like: "'JyiR2cQ6DZT', 'jhgyg67cjd'"
$STOCK_OUT_7DAYS = "JyiR2cQ6DZT";
$STOCK_OUT_IMPLANT = "wNT8GGBpXKL";
$STOCK_OUT_FEMALE_CONDOMS = "pYhpegHDt4x";
$STOCK_OUT_EC = "QlroxgXpWTL";

$STOCK_OUT_INDICATORS = array($STOCK_OUT_7DAYS, $STOCK_OUT_IMPLANT, $STOCK_OUT_FEMALE_CONDOMS, $STOCK_OUT_EC);


//INDEX OF VALUES IN DHIS2 ROWS.
$COMMODITY_INDEX = 0;
$FACILITY_INDEX = 1;
$CONSUMPTION_INDEX = 2;

//set this to web OR file to set where the system will fecthc commodity 
//reports data from web service or from a pre-downloaded json file
$DATA_SOURCE = 'web';  //web||file

//use this in file data source mode. Ensure to use the correct file name
$DATA_SOURCE_JSON_FILE = "json_comm/DHIS2Upload-FacilityCommodity-201505.json";


//get the current server location to know how to get paths 
//when on local host and when online/live
$HOST_SERVER = $_SERVER['HTTP_HOST'];

//stock out commodities external id
$STOCK_OUT_COMMODITIES = "'w92UxLIRNTl','DiXDJRmPwfh'";

//get program input arguments
//$options = getopt("m::p::h");
//$options = getopt("m");

$options = array('p'); 


if(sizeof($options) === 0){
	help();
        
}else{
	if(in_array('h', $options)){
		help();
                
		exit;
	}else{
		if(in_array('d', $options)){
			$DEBUG_MODE = true;
		}
		if(in_array('m', $options)){
			$PERIOD_LAST_MONTH_MODE = true;
		}
		if(in_array('p', $options)){
			$PERIOD_HISTORICAL_MODE = true;
			$per = array_search('p', $options);
			if(!empty($per)){
				$PERIOD_HISTORICAL = $per;
			}
		}
		if(!$PERIOD_LAST_MONTH_MODE && !$PERIOD_HISTORICAL_MODE){
			help();
			exit;
		}
	}
}

//to run on local PC

if($HOST_SERVER == 'localhost')
    require_once 'globals.php';
else
    require_once '../../sites/globals.php';

$db = Zend_Db_Table_Abstract::getDefaultAdapter();
 
//to run on server
  //$db = getDB($DB_NAME);
//  $db = getDB('');

// print "USE DATABASE: " . $DB_NAME . "\n\n";

 $all_errors = '';
 $date = '';
 
 // array with out of stock info [out of stock UID]=>[commodity UID]
 $commodity_names_out_of_stock_arr = array();
 
 echo date(DATE_RFC2822);
 if($PERIOD_HISTORICAL_MODE){

        //echo 'historical'; exit;

 	$periods = explode(";", $PERIOD_HISTORICAL);
 	for($i=0; $i<sizeof($periods); $i++){
 		print "\n\n ===> UPLOAD PERIOD: " . $periods[$i] . " START\n\n";
 		$DATA_URL = $DATA_URL_START . $periods[$i] . $DATA_URL_END;

                //print '<br/><br/>' . $DATA_URL; exit;

 		upload($DATA_URL, $USERNAME, $PASSWORD, $UPDATE_FACILITY_MODE, $UPDATE_COMMODITY_NAMES_MODE, $UPDATE_COMMODITY_DATA_MODE, $COMMODITY_NAMES_IDS_FILE, $db, $commodity_names_out_of_stock_arr);
 		print "\n\n ===> UPLOAD PERIOD: " . $periods[$i] . " END\n####################################################################################\n\n";
 	}
 }
 if($PERIOD_LAST_MONTH_MODE){
 	print "\n\n ===> UPLOAD PERIOD: " . $PERIOD_LAST_MONTH . " START\n\n";
 	     
 	$DATA_URL = $DATA_URL_START . $PERIOD_LAST_MONTH . $DATA_URL_END;
       // $DATA_URL = $DATA_URL_START .$DATA_URL_END;
        //echo $data_url;
 	upload($DATA_URL, $USERNAME, $PASSWORD, $UPDATE_FACILITY_MODE, $UPDATE_COMMODITY_NAMES_MODE, $UPDATE_COMMODITY_DATA_MODE, $COMMODITY_NAMES_IDS_FILE, $db, $commodity_names_out_of_stock_arr);
 	print "\n\n ===> UPLOAD PERIOD: " . $PERIOD_LAST_MONTH . " END\n\n";
       // echo 'It is here';
 }
 echo date(DATE_RFC2822);
 
 if(!empty($all_errors)){
 	$file = fopen("json_comm/DHIS2Upload-FacilityCommodity-". $date . ".errors","w");
 	echo fwrite($file,$all_errors);
 	fclose($file);
 }
 
 function checkDataLoaded(){
    global $PERIOD_HISTORICAL, $db;
    $period = $PERIOD_HISTORICAL;
    $periodDate = substr($period,0,4) . '-' . substr($period, -2) . '-01';
    $db_data_info_count = $db->fetchAll ("select count(*) as count from commodity where date='" . $periodDate . "'");
    return $db_data_info_count[0]['count'];
 }
 
 /**
  * Upload data
  */
function upload($DATA_URL, $USERNAME, $PASSWORD, $UPDATE_FACILITY_MODE, $UPDATE_COMMODITY_NAMES_MODE, $UPDATE_COMMODITY_DATA_MODE, $COMMODITY_NAMES_IDS_FILE, $db) {
	
	global $commodity_names_out_of_stock_arr;
	global $date;

    global $COMMODITY_INDEX, $FACILITY_INDEX, $CONSUMPTION_INDEX;
    global $DATA_SOURCE, $DATA_SOURCE_JSON_FILE;
        
    // ******************* LOAD DATA FROM DHIS2 WEB SERVICE ***************************	
	plog("Load data: " . $DATA_URL . "\n\n");
        
        // read from web service or file: gives facility and commodity data
        if($DATA_SOURCE == 'web'){
            plog('WEB MODE');
            $data_json = getWebServiceResult($DATA_URL, $USERNAME, $PASSWORD); 
        }
        else if($DATA_SOURCE == 'file')
            $data_json = file_get_contents($DATA_SOURCE_JSON_FILE);
	
        $data_json_arr = json_decode($data_json, true);
        //print_r($data_json); //exit;

        plog('count of json rows: ' . count($data_json_arr["rows"])); 
        echo 'count of json rows: ' . count($data_json_arr["rows"]); 
        
        //print_r($data_json_arr); 
        
        // check if these date already loaded to database before going any further to save time.
        $db_period_count = checkDataLoaded();
        if($db_period_count > 0){
            print "<br><br>Commodity data for this period had been loaded in database earlier.<br><br>";
            return;
        }
        else{
            plog('Data for this period not present in db');
        }
       	

	//$date = "201503";
       $date = $data_json_arr ["metaData"]["pe"][0];
        //$date = "201503";
       $values = array();
       $count = 0;
       
       /* -----------------
        * THIS WILL HELP TO STRIP OUT ALL COMMODITIES NOT WATCHED BY THIS SYSTEM
        */
       $external_id = array("DiXDJRmPwfh","G5mKWErswJ0","H8A8xQ9gJ5b","ibHR9NQ0bKL","JyiR2cQ6DZT","krVqq8Vk5Kw","mvBO08ctlWw","QlroxgXpWTL","vDnxlrIQWUo","w92UxLIRNTl","wNT8GGBpXKL","yJSLjbC9Gnr","pYhpegHDt4x");
       //WS:"DiXDJRmPwfh","G5mKWErswJ0","H8A8xQ9gJ5b","ibHR9NQ0bKL","JyiR2cQ6DZT","krVqq8Vk5Kw","mvBO08ctlWw","QlroxgXpWTL","vDnxlrIQWUo","w92UxLIRNTl","wNT8GGBpXKL","yJSLjbC9Gnr","Yw92UxLIRNTl"
                    
       
       //These commodities exist in web service but not in the database. 
       //EIHpURrBm7K;eChiJMwaOqm;pYhpegHDt4x;
       
       //print_r($external_id); echo '<br/><br/>';print_r($data_json_arr);
       //print_r($data_json_arr["rows"]); exit;
       foreach($data_json_arr["rows"] as $row){
           $count++;
           //if($row[0]==$date){
               if(in_array($row[$COMMODITY_INDEX], $external_id)){
                    $row[$FACILITY_INDEX] = trim($row[$FACILITY_INDEX]); //remove trailing spaces from facility UUID
                    array_push($values,$row);
               }
           //}
       }
        $data_json_arr['rows'] = array();
        $data_json_arr['rows'] = $values;
      /* STRIPPING OUT ENDS -------------------*/
       
       
       //echo '<br/>The size of the whole file from  is '.$count.'<br/><br/>';
     
        plog('The size of the commodity for the period '.$date.'  is '.sizeof($data_json_arr["rows"]));

     
       //foreach($data_json_arr["rows"] as $row){
         // print_r($row);echo '<br/><br/>';
       //}

 
       $data_json = json_encode($data_json_arr);
       
       // $data_json = file_get_contents("DHIS2Upload-FacilityCommodity-". $date . ".json");
      //  $data_json_arr = json_decode($data_json, true);
    //print_r($data_json_arr);exit;
         //$data_json = file_get_contents("DHIS2Upload-FacilityCommodity-201502.json");
         //$data_json_arr = json_decode($data_json, true);
       
        // remove this huge object (2Mb of size)
        // The object contains all facilities in UUID form
        unset($data_json_arr["metaData"]["ou"]); 
        echo '<br/><br/><br/>';
        print_r($data_json_arr); exit;
      
	//$date = $data_json_arr ["metaData"] ["pe"] [0];
	$date_year = substr ( $date, 0, 4 );
	$date_month = substr ( $date, - 2 );
	//print "<br/>Data period: " . $date_year . "-" . $date_month . "-01\n\n";
        plog("Data period: " . $date_year . "-" . $date_month . "-01");
	
	//save json output to file
//	$file = fopen("json_comm/DHIS2Upload-FacilityCommodity-". $date . ".json","w");
//	echo fwrite($file,$data_json);
//	fclose($file);
//        echo '<br>Saved file. '; exit;

	//echo 'This is the nrwwweopehfefhfej';

	// commodity data: [facility type - we do not need],[commodity name],[facility name],[consumption]

	if (sizeof ( $data_json_arr["rows"] ) == 0) {
		global $all_errors;
		$all_errors = $all_errors . "ERROR: Commodity data is empty in WS.\n";
		exit ();
	}
	
	// ******************* UPDATE FACILITIES NAMES ***********************************************
	if ($UPDATE_FACILITY_MODE) {
		// get DB facility info BEFORE update
		// hash: key - external id, value - array(id, external_id, facility_name)

		$db_facility_info = getDBFacilitiesInfo ( $db );
		
		//hash:  key - [name], value - [id]
		$db_state_info = getDBStateInfo($db);

		updateFacilities ( $data_json_arr ["metaData"] ["ouHierarchy"], $db_facility_info, $data_json_arr ["metaData"]["names"], $db, $date_year . "-" . $date_month . "-01", $db_state_info);       
        }
	//exit;

	// get DB updated facility info AFTER update
	// hash: key - external id, value - array(id, external_id, facility_name)
	$db_facility_info = getDBFacilitiesInfo ( $db );
        //print_r($db_facility_info);
	//echo 'after facility info'; exit;
	
	// ******************* UPDATE COMMODITIES NAMES ***********************************************

        /*TP: 
         * Commenting this block of code out
         * Adding a commodity will be a deliberate action that will be done manually on the database
         * This is because there are now fields such as commodity type and alias that have to be entered 
         * for each commodity and this is not provided from DHIS2. 
         * 
         * Also, there may be code changes based on a commodity additio to the system. A one cap fits all 
         * approach will not work for this anymore.
         */
//	if ($UPDATE_COMMODITY_NAMES_MODE) {
//            
//
//		// read commodity names ids file - format [id1]\n[id2]\n...
//		print "=> Get commodities name ids from file\n\n";
//		//format: [commodity UID];[name];[out of stock UID]
//		$commodity_names_ids = array_filter ( preg_split ( "/[\r\n]+/", file_get_contents ( $COMMODITY_NAMES_IDS_FILE ) ) );
//		
//		// get DB commodities names info BEFORE update
//		// hash: key - external id, value - array(id, external_id, facility_name)
//		$db_commodity_info = getDBCommodityNamesInfo ( $db );
//		
//		// take files with commodity names id and check if in DB, if not add new commodity name to DB 'commodity_name_option' table
//		updateCommoditiesNames ( $data_json_arr ["metaData"] ["names"], $commodity_names_ids, $db, $db_commodity_info, $date_year, $date_month );
//
//	}

	
	// get DB commodities names info AFTER update
	// hash: key - external id, value - array(id, external_id, facility_name)
	$db_commodity_info = getDBCommodityNamesInfo ( $db );

	//print_r($db_commodity_info);

	// ******************* UPDATE COMMODITIES DATA ***********************************************
	if ($UPDATE_COMMODITY_DATA_MODE) {
		// get DB commodity data info BEFORE update
		// hash: key - facility id, key - name id, value - id
           
		$db_commodity_data_info = getDBCommoditiesDataInfo ( $db, $date_year . "-" . $date_month . "-01" );
		
		updateCommoditiesData ( $data_json_arr ["rows"], $db_commodity_info, $db_facility_info, $db_commodity_data_info, $db, $date_year . "-" . $date_month . "-01" ); 
        }

}

// take files with commodity names id and check if in DB, if not add new commodity name to DB 'commodity_name_option' table
function updateCommoditiesNames($names, $commodity_name_ids, $db, $db_commodity_info, $date_year, $date_month){
	global $commodity_names_out_of_stock_arr;
	global $DEBUG_MODE;
	$error = '';
	if($DEBUG_MODE)
		print "=> UPDATE COMMODITIES NAMES START ...\n\n";
	$count = 0;
	foreach ( $commodity_name_ids as $commodity_info) {
		$count++;
		//format: [commodity UID];[name];[out of stock UID]
		$commodity_info_arr = explode(";", $commodity_info);

                //print_r($commodity_info_arr);

		$commodity_external_id = $commodity_info_arr[0];
		//find commodity name by external_id from WS 'names'
		if(array_key_exists($commodity_external_id, $names)){
			$commodity_name = trim($names[trim($commodity_external_id)]);
		}else{
			$commodity_name = $commodity_info_arr[1];
		}
		if($commodity_name){
			//add/update commodity name in DB
			if($db_commodity_info && array_key_exists($commodity_external_id, $db_commodity_info)){
				// if commodity name are different then update
				if($commodity_name !== $db_commodity_info[$commodity_external_id]['commodity_name']){
					try{

						$db->query("UPDATE commodity_name_option SET commodity_name='" . $commodity_name . "' WHERE external_id='" . addslashes($commodity_external_id). "'");

						if($DEBUG_MODE)
							print "EDIT COMMODITY: " . $commodity_external_id . "=>" . $commodity_name ."\n\n";
					}catch(Exception $e){
						$error = $error . "ERROR: " . $commodity_external_id . " (" . $e->getMessage() . ")\n";
					}
				}
			}else{
				//add new commodity_name
				try{
					$bind = array(
						'external_id'			=>	$commodity_external_id,
						'commodity_name'		=>	$commodity_name,
						'timestamp_created' => $date_year . "-" . $date_month . "-01"

					);
					//all value automatically will be removed white spaces at the END during insertion to DB
					$db->insert("commodity_name_option", $bind);
					$commodity_id=$db->lastInsertId();
					if($DEBUG_MODE)
						print "ADD COMMODITY: " . $commodity_external_id . "=>" . $commodity_name ."\n\n";
				}catch(Exception $e){
					$error = $error . "ERROR: " . $commodity_external_id . " (" . $e->getMessage() . ")\n";
				}
			}
		}else{
			$error = $error . "ERROR: " . $commodity_external_id . " not found in 'names' WS.\n";
		}
		// check this commodity has out of stock info
               /* echo '<br/><br/><br/>';
                 echo $commodity_external_id;
                 echo 'Na here the thing dey <br/>';
                 echo $commodity_info_arr[1];*/

		if(!empty($commodity_info_arr[2])){
			// array with out of stock info [out of stock UID]=>[commodity UID]
			$commodity_names_out_of_stock_arr[$commodity_info_arr[2]] = $commodity_external_id;
		}
	}
	if(!empty($error)){
		global $all_errors;
		$all_errors = $all_errors . "\n=> UPDATE COMMODITIES NAMES:\n" . $error . "\n\n";
	}
	print "\n=> UPDATE COMMODITIES NAMES END:\n" .  $count . " commodities names ids have been processed.\n";

	//validate process
	$db_commodity_names_info_count = $db->fetchAll ("select count(*) as count from commodity_name_option");
	print $db_commodity_names_info_count[0]['count'] . " commodities names in database.\n\n";
}



// add commodity data to facilities - 'commodities' table
function updateCommoditiesData($commodity_data, $db_commodity_info, $db_facility_info, $db_commodity_data_info, $db, $date){
        echo 'inside updateCommoditiesData'; 
	global $commodity_names_out_of_stock_arr;
	global $DEBUG_MODE;
	global $STOCK_OUT_7DAYS;
        global $STOCK_OUT_IMPLANT;
        global $STOCK_OUT_COMMODITIES;
        global $STOCK_OUT_INDICATORS;
        global $COMMODITY_INDEX, $FACILITY_INDEX, $CONSUMPTION_INDEX;
        
        $commodity_not_id = $not_facility = $commodity_not_inserted = $commodity_inserted = array(); 
        
        //get list of reporting facilities for the month
        $facilities_array = getReportingFacilities($db, $date);
        

	// get commodity names ids which has out of stock info
	$db_commodity_names_ids_with_has_out_off_stock = array();
	$query_arr = array_values($commodity_names_out_of_stock_arr);
       

	//foreach ($query_arr as &$value)
	//	$value = "'" .$value . "'";
        
	//$res = $db->fetchAll ("select id from commodity_name_option where external_id in (" . implode(", ", $query_arr) . ")");
	//foreach ($res as $value)
	//	array_push($db_commodity_names_ids_with_has_out_off_stock, $value['id']);

       // print_r($db_commodity_names_ids_with_has_out_off_stock);


	//means that out of stock 'Y'
	//format: [facility external id]=> array of commodity externals id for which out of stock 'Y'
	$commodity_names_out_of_stock_arr_to_update = array();
	$error = '';


               
        
	if($DEBUG_MODE)
		print "=> UPDATE COMMODITIES DATA START ...\n\n";

	$count = 0;      

       
         // print_r($db_facility_info);
        $degade = 0;
        $counter = 0; $facPassCounter = $loopCounter = $commPassCounter = $commPassCounter = $insertCounter = 0; 
        echo 'This is the inside size of the commodity database'.sizeof($commodity_data).'<br/><br/>';

        echo '<br>------------- Commodity Rows -------------<br>';
        //print_r($commodity_data); exit;
	foreach ($commodity_data as $commodity) {
                $loopCounter++;
		$commodity_external_id = $commodity[$COMMODITY_INDEX];
                $consumption = $commodity[$CONSUMPTION_INDEX];
                //$commodity_id = $db_commodity_info[$commodity_external_id]['id'];
                //continue;
                

		//if this is out of stock data
              //echo $commodity_external_id.' this is the commodity tingy <br/>';
               // print_r($commodity_names_out_of_stock_arr); echo '<br/><br/><br/>';
		/*if($commodity_names_out_of_stock_arr && array_key_exists($commodity_external_id, $commodity_names_out_of_stock_arr)){
			//add facility and out of stock info
			
                       //echo $consumption.' is consumption<br/>';
			//if($consumption > 0){
				$data_arr = array();
				$facility_external_id = $commodity[2];
                                // echo 'Commodity external id '.$commodity_external_id.' consumption '.$consumption.' facility_external id '.$facility_external_id.'<br/>';

				if(array_key_exists($facility_external_id, $commodity_names_out_of_stock_arr_to_update)){
					$data_arr = $commodity_names_out_of_stock_arr_to_update[$facility_external_id];
				}
				array_push($data_arr, $commodity_names_out_of_stock_arr[$commodity_external_id] . "=" . $consumption);
				$commodity_names_out_of_stock_arr_to_update[$facility_external_id] = $data_arr; 
		//	}
			continue;
		}*/
		

               //if commodity name in the list of needed commodities
               $number = 0;
               
              // echo 'The commodity id is now '.$commodity_external_id.'<br/>';

                //TP: check if the commodity external id is registered in our system
		if($db_commodity_info && array_key_exists($commodity_external_id, $db_commodity_info)){
                    $commPassCounter++; 
                    // echo $commodity_external_id.' this is the correct thing <br/>';
                    //print 'comodity print<br>';
                    //print_r($commodity);
            // echo 'the the number of the real call ups '.$number++;
              //echo '<br/><br/><br/>';
               // echo '<br/><br/><br/>';
                     //print_r($db_commodity_info).'<br/>';
			$commodity_id = $db_commodity_info[$commodity_external_id]['id'];

                        
                        //echo 'This is the commodity _id '.$commodity_id.'<br/>';
                       //print_r($commodity); echo '<br/><br/>';//continue;
			$facility_external_id = $commodity[$FACILITY_INDEX];
                        
                                
			if($db_facility_info && array_key_exists($facility_external_id, $db_facility_info)){
                           //echo 'inside facility check <br/><br/>';

                           $facPassCounter++; 
                            
                           // echo 'THis is number '.$counter.'<br/><br/>';
                            
				$count++;
				$facility_id = $db_facility_info[$facility_external_id]['id'];	

				//$consumption = $commodity[3];
                                $uuid = $commodity[$COMMODITY_INDEX];
                                //$STOCK_OUT_7DAYS = "JyiR2cQ6DZT";
                                //$STOCK_OUT_IMPLANT = "wNT8GGBpXKL";
                                //echo "consumption: $consumption facility id: $facility_id uuid: $uuid <br/><br/>";exit;

                                //$stock_out = (($uuid==$STOCK_OUT_7DAYS || $uuid==$STOCK_OUT_IMPLANT) && ($consumption=="1.0" || $consumption=="1" || $consumption=="100" || $consumption=="100.0"))?"Y":"N";
                                $consumptionArray = array("1", "1.0", "100", "100.0");
                                $stock_out = (in_array($uuid,$STOCK_OUT_INDICATORS) && (in_array($consumption,$consumptionArray))) ? "Y" : "N";
                                $bringer = 0;
				try{
                                    $counter = $counter + 1;

					//if you bave to do an update then better remove the  data for the month 
                                        //from db then reload. Might need to put the system in maintenance mode for a bit tho.

                                        
                                          if(array_key_exists($facility_id,$facilities_array)){
                                              $flag = 1;
                                          }else{
                                              $flag = 0;
                                          }
                                        

                                        //echo 'after flag<br><br>';
                                        // add new dataecho  
                                        // echo  'commodity id is '.$commodity_id.' facility id is '.$facility_id.' date is '.$date.' flag is '.$flag.' consumption is '.$consumption.' stock_out '.$stock_out.'<br/><br/>';

                                        //continue;
                                          $bind = array(
						'name_id'         =>  $commodity_id,
                                                'date'            =>  $date,
                                                'consumption'     => $consumption,
						'stock_out'       => $stock_out,
                                                'facility_id'     =>	$facility_id,
                                                'facility_reporting_status'=>$flag,
						'timestamp_created' => date("Y-m-d H:i:s"),
						'modified_by' => '0'
                                              );
                                          $degade = $degade + 1;

                                          
                                          //$insertBoolean = $db->insert("commodity", $bind);
                                          //echo 'insert: ' . $insertBoolean . '<br><br>';
                                          
                                          if((!$insertBoolean) || ($insertBoolean<1))
                                              $commodity_not_inserted[] = $commodity;
                                          else{
                                              $commodity_inserted[] = $commodity;
                                              $insertCounter++;
                                          }


					if($DEBUG_MODE)
						print "ADD COMMODITY DATA: " . $commodity_external_id . " to facility " . $facility_external_id . "=" . $consumption . "\n";
					
				}catch(Exception $e){
					$error = $error . "ERROR ADD COMMODITY DATA: " . $commodity_external_id . "=>" . $facility_external_id . "=" . $consumption . " (" . $e->getMessage() . ")\n";
				}
			}else{
                            $not_facility[] = $facility_external_id;
                        }
		}else{
                    $commodity_not_id[] = $commodity_external_id; 
                }
                
	}
       // echo 'This is the facility array ';
       // print_r($facilities_array);echo '<br/><br/>';
        print '------------------- COMMS NOT FOUND IN DB ---------------<br/>';
        //print_r($commodity_not_id); echo '<br/><br/>';
        
        print '------------------- FACS NOT FOUND IN DB ---------------<br/>';
        //print_r($not_facility); echo '<br/><br/>';
        
        
        print '------------------- COMMS INSERTED INTO DB ---------------<br/>';
        //print_r($commodity_inserted); echo '<br/><br/>';
        
        print '------------------- COMMS NOT INSERTED INTO DB ---------------<br/>';
        //print_r($commodity_not_inserted); echo '<br/><br/>';
        
        //print "counter: $counter, facPassCounter: $facPassCounter, loopCounter: $loopCounter, commPassCounter: $commPassCounter, insertCounter: $insertCounter<br>";
        plog("counter: $counter, facPassCounter: $facPassCounter, loopCounter: $loopCounter, commPassCounter: $commPassCounter, insertCounter: $insertCounter");
        
        echo 'facility id is here '.$counter.'<br/>';

        //print_r($not_facility);

        echo 'the aftermath is '.$degade;
        echo '<br/><br/><br/>';
        //print_r($db_facility_info);
        echo 'This is the facility info for us';
       // exit; 
        //echo '<br/><br/>';

	//print "\n=> UPDATE COMMODITIES DATA END:\n" .  $count . " commodities data have been processed.\n";
        plog("UPDATE COMMODITIES DATA END:\n" .  $count . " commodities data have been processed.");

	$db_commodity_info_count = $db->fetchAll ("select count(*) as count from commodity where date='" . $date . "'");
	print $db_commodity_info_count[0]['count'] . " commodities data in database.\n\n";
	
	//get all current commodities data

	//$db_commodity_data_info = getDBCommoditiesDataInfo ( $db, $date);

        
}//end updateCommoditiesData


/*
 * DHIS2 WS does not have information about 'zone' in 'hierarchy' tag: "[facility UID]":"/ [skip] / [state UID] / [LGA UId] / [skip]�
 * we have hard coded in DB Location table zones and states with no external UID, but LGA and facility we will store with external UID.
 *  
 */

function updateFacilities($hierarchy, $db_facility_info, $names, $db, $date, $db_state_info){        
        
	global $DEBUG_MODE;
	$error = '';
	if($DEBUG_MODE)
		print "=> UPDATE FACILITIES START ...\n\n";
	$count = 0;
	foreach ( $hierarchy as $facility_external_id=>$location_path ) {
		if(!$location_path || empty($location_path)){
			$error = $error . "ERROR: " . $facility_external_id . " (facility location is empty)\n";
			continue;
		}
		$location = explode("/", $location_path);
		// parse only for facilities.
		//Path should be [facility UID]=>/[UID - skip]/[state UID]/[LGA UID]/[UID - skip]
		if(count($location) != 5){
			continue;
		}
		$count++;
		$state_external_id =  $location[2];
		$lga_external_id =  $location[3];
		//remove prefix before name, like 'Kd Radiance clinic' - remove 'Kd'
		$facility_name = substr(strstr(trim($names[$facility_external_id])," "), 1);
		$state_name = substr(strstr(trim($names[$state_external_id])," "), 1);
		$lga_name = substr(strstr(trim($names[$lga_external_id])," "), 1);
		// remove 'Local Government Area' from LGA names
		/*or do this: UPDATE location SET location_name = REPLACE(location_name, 'Local Government Area', '') where tier=3 and location_name like '%Local Government Area';*/
		$lga_name = trim(str_replace("Local Government Area","",$lga_name));
		// remove ' from names for LGA like "Jama'are"
		$lga_name = trim(str_replace("'","",$lga_name));
		if($DEBUG_MODE)
			print "\nProcessing facility " . $count . ": " . $facility_name . "/" . $state_name . "/" . $lga_name . " (" . $facility_external_id . "=>" . $location_path .")\n";
		$facility_name = trim($facility_name);		
		if(empty($facility_name)){
			$error = $error . "ERROR: " . $facility_external_id . " (facility name is empty)\n";
			continue;
		}
		if($db_facility_info && array_key_exists($facility_external_id, $db_facility_info)){
			// if facility name are different then update
			if($facility_name !== $db_facility_info[$facility_external_id]['facility_name']){
				try{
					$db->query("UPDATE facility SET facility_name='" . $facility_name . "' WHERE external_id='" . $facility_external_id . "'");
					if($DEBUG_MODE)
						print "EDIT FACILITY: " . $facility_external_id . "=>" . $facility_name ."\n\n";
				}catch(Exception $e){
					$error = $error . "ERROR: EDIT FACILITY: " . $facility_external_id . " (" . $e->getMessage() . ")\n";
				}
			}
		}else{
			//ADD NEW FACILITY
				// remove '-' from state name
				$state_name = str_replace('-', ' ', trim($state_name));
				// remove 'state' word from $state_name
				$state_name = trim(ucwords(str_replace('state', '', strtolower($state_name))));
				//find state in database (hardcoded)
				if($db_state_info && array_key_exists($state_name, $db_state_info)){
					$state_id = $db_state_info[$state_name];
					if($state_id){
						$lga_id = isLocationExist($lga_external_id, $db);
						if($lga_id === NULL){
							$lga_id = addLocation($lga_external_id, $lga_name, 3, $state_id, $db, $date);
						}
						$bind = array(
								'external_id'			=>	$facility_external_id,
								'facility_name'		=>	$facility_name,
								'location_id'=>	$lga_id,
								'timestamp_created' => $date,
						);
						try{
							//all value automatically will be removed white spaces at the END during insertion to DB
							$db->insert("facility", $bind);
							$facility_id=$db->lastInsertId();
							if($DEBUG_MODE)
								print "ADD FACILITY: " . $facility_external_id . "=>" . $facility_name ."\n\n";
						}catch(Exception $e){
							$error = $error . "ERROR: ADD FACILITY: " . $facility_external_id . " does not have prefix (" . $e->getMessage() . ")\n";
						}
					}else{
						$error = $error . "ERROR: ADD FACILITY: cannot add new facility '" . $facility_name . "': state '" . $state_name . "' does not exist in database.\n";
					}
				}else{
					$error = $error . "ERROR: ADD FACILITY: cannot add new facility '" . $facility_name . "': state '" . $state_name . "' does not exist in database.\n";
				}
		}
	}
	if(!empty($error)){
		global $all_errors;
		$all_errors = $all_errors . "\n=> UPDATE FACILITIES:\n" . $error . "\n\n";
	}
	print "\n=> UPDATE FACILITIES END:\n" .  $count . " facilities have been processed.\n";

	//validate process
	$db_facility_info_count = $db->fetchAll ("select count(*) as count from facility");
	print $db_facility_info_count[0]['count'] . " facilities in database.\n\n";
}

//returns location id
function isLocationExist($external_location_id, $db){
	$db_location_info = $db->fetchAll ("select id, location_name from location where external_id = '" . $external_location_id . "'");
	if($db_location_info){
		return $db_location_info[0]['id'];
	}
	return NULL; // not found
}

function addLocation($external_location_id, $location_name, $location_tier, $location_parent_id, $db, $date){
	global $DEBUG_MODE;
	$bind = array(
			'external_id'			=>	$external_location_id,
			'location_name'		=>	$location_name,
			'tier'=>	$location_tier,
			'parent_id'=>	$location_parent_id,
			'timestamp_created' => $date,
	);
	try{
	 $db->insert("location", $bind);
	 $location_id=$db->lastInsertId();
	 if($DEBUG_MODE)
		print "ADD LOCATION: " . $external_location_id . "=>" . $location_name . "\n";
	 return $location_id;
	}catch(Exception $e){
		throw new Exception("ERROR: " . $external_location_id . " (" . $e->getMessage() . ")\n");
	}
}

function getDBCommoditiesDataInfo($db, $date){
	echo "\n=>Get commodity data info from database...\n\n";
	$db_commodity_data_info = $db->fetchAll ("select id, name_id, facility_id from commodity where date = '" . $date . "'");
	$db_commodity_data_info_hash = array();
	foreach ( $db_commodity_data_info as $db_commodity_data_row ) {
		$db_commodity_data_info_hash[$db_commodity_data_row['facility_id']][$db_commodity_data_row['name_id']] = $db_commodity_data_row['id'];
	}
	return $db_commodity_data_info_hash;
}

//hash: key - external id, value - array(id, external_id, facility_name)
function getDBFacilitiesInfo($db){
	echo "\n=>Get facility info from database...\n\n";
	 $db_facility_info = $db->fetchAll ("select id, external_id, facility_name from facility");
	 $db_facility_info_hash = array();
	 foreach ( $db_facility_info as $db_facility_row ) {
	 	$db_facility_info_hash[$db_facility_row['external_id']] = $db_facility_row;
	 }
	 return $db_facility_info_hash;
}

//hash: key - external id, value - array(id, external_id, commodity_name)
function getDBCommodityNamesInfo($db){
	echo "\n=>Get commodity names info from database...\n\n";
	$db_commodity_info = $db->fetchAll ("select id, external_id, commodity_name from commodity_name_option");
	$db_commodity_info_hash = array();
	foreach ( $db_commodity_info as $db_commodity_row ) {
		$db_commodity_info_hash[$db_commodity_row['external_id']] = $db_commodity_row;
	}
	return $db_commodity_info_hash;
}

//hash:  key - [name], value - [id]
function getDBStateInfo($db){
	echo "\n=>Get states info from database...\n\n";
	$db_state_info = $db->fetchAll ("select id, location_name, parent_id from location where tier=2");
	$db_state_info_hash = array();
	foreach ( $db_state_info as $db_state_row ) {
		$db_state_info_hash[$db_state_row['location_name']] = $db_state_row['id'];
	}
	return $db_state_info_hash;
}

//print help how to run script
function help(){
	print "This script uploads data from DHIS2\n\nUSAGE: php DHIS2Upload-FacilityCommodity.php [options] > out\nOptions:\n";
	print "\t-m - upload data for the last month period. USAGE: -m\n";
	print "\t-p - upload data for period [YYYYMM;YYYYMM], if -p is empty then upload periods [201101-201410]. USAGE:-p[YYYYMM;YYYYMM] or -p\n";
	print "\t-d - print debug output\n";
	print "\t-h - help\n";
}

	/**
	 * Read web service and return output
	 */
	function getWebServiceResult($commodity_data_url, $username, $password){
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $commodity_data_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // comment later, it is for Windows only
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_TIMEOUT, 0);

		//echo 'This is the ch we loaded '.$ch;
		$output = curl_exec($ch);
                //echo 'THis is the output '.$output;
		if($output === false){
			echo 'ERROR: ' . curl_error($ch);
		}
               
// 		else{
// 			return $output;
// 		}
                //echo  'helo this is the file oooooooooooo';
		curl_close($ch);
                
		return $output;
	}

/**
 * Read 'commodity-names' file to array
 */
function csv_to_array($filename = '', $delimiter = ',') {
	if (! file_exists ( $filename ) || ! is_readable ( $filename ))
		return FALSE;
	$header = NULL;
	$data = array ();
	if (($handle = fopen ( $filename, 'r' )) !== FALSE) {
		while ( ($row = fgetcsv ( $handle, 1000, $delimiter )) !== FALSE ) {
			if (! $header)
				$header = $row;
			else
				$data [] = array_combine ( $header, $row );
		}
		fclose ( $handle );
	}
	return $data;
}

function getDB($db_name){
	require_once 'settings.php';
	require_once 'Zend/Db.php';

	//set a default database adaptor
	$db = Zend_Db::factory('PDO_MYSQL', array(
			'host'     => Settings::$DB_SERVER,
			'username' => Settings::$DB_USERNAME,
			'password' => Settings::$DB_PWD,

			'dbname'   => empty($db_name) ? Settings::$DB_DATABASE : $db_name,

	));

	require_once 'Zend/Db/Table/Abstract.php';
	Zend_Db_Table_Abstract::setDefaultAdapter($db);
	$db = Zend_Db_Table_Abstract::getDefaultAdapter();
	return $db;

}


function getReportingFacilities($db, $date){
    // echo 'my friend the date is '.$date;
    $sql = "SELECT facility_id FROM facility_report_rate WHERE date='".$date."'";
	 $facility_reported = $db->fetchAll($sql);
				//echo '<br/>This is the sql '.$sql;	
         $facilities_array = array();
        // print_r($facility_reported);
        foreach($facility_reported as $fac){
           $facilities_array[$fac['facility_id']] = $fac['facility_id']; 
       }   
       return $facilities_array;
}

    function plog($logMessage){
        $logMessage = date('Y-m-d H:i:s') . ' ' . $logMessage . "\n";
        file_put_contents("json_comm/logs.txt", $logMessage, FILE_APPEND);
    }


?>