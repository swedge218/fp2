<?php
/*
 * EXPORT THE INSERTED ROWS INTO A FILE 
 * UPLOAD THE FILE TO UPDATE THE WEB DATABASE
 * mysqldump -h localhost -u root -p --where ="date='2015-04-01'"
 * itechweb_chainigeria facility_report_rate > filename.sql
 */


/* 
 1. Read DHIS2 web service
 2.
 From:  Row  e.g. ["lyVV9bPLlVy","201411","agAcarLl8in","100,0"]


                          ["Not Needed","DATE","facility","reported"]


Put in database 'facility_report_rate': id, facility_external_id, date.
*/
ini_set('display_errors', 'On');
$DB_NAME = 'dev_test';


//constants
$PERIOD_LAST_MONTH_MODE = false;
$PERIOD_HISTORICAL_MODE = false;

$PERIOD_LAST_MONTH = 'LAST_MONTH';

//set to lagos timezone and check that we are on the 25th
date_default_timezone_set('Africa/Lagos');

$PERIOD_HISTORICAL = date("Ym",strtotime("-1 month")); 
//echo $PERIOD_HISTORICAL; exit;
plog('Starting FRR log for ' . $PERIOD_HISTORICAL);

//TP 
$DATA_URL_START = "https://dhis2nigeria.org.ng/dhis/api/analytics.json?dimension=dx:lyVV9bPLlVy&dimension=ou:LEVEL-5;s5DPBsdoE8b&dimension=pe:";
$DATA_URL_END   = "&displayProperty=NAME&outputIdScheme=ID";

//$TABLE_NAME = 'facility_report_rate';

//set this to web OR file to set where the system will fecthc commodity 
//reports data from web service or from a pre-downloaded json file
$DATA_SOURCE = 'web';  //web||file

//use this in file data source mode: Ensure to use the correct file name
$DATA_SOURCE_JSON_FILE = "json_frr/DHIS2Upload-FacilityReportRate-201505.json";
 
//get the current server location to know how to get paths 
//when on local host and when online/live
$HOST_SERVER = $_SERVER['HTTP_HOST'];

//https://dhis2nigeria.org.ng/dhis/api/analytics.json?dimension=pe:LAST_MONTH&dimension=ou:LEVEL-5;s5DPBsdoE8b&displayProperty=NAME&outputIdScheme=ID

$USERNAME = "FP_Dashboard";
$PASSWORD = "CHAI12345";
//get program input arguments
//$options = getopt("m::p::h");

$options = array('p'); 

if(sizeof($options) === 0){
	help();
}else{
	if(in_array('h', $options)){
		help();
		exit;
	}else{
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
    require_once 'globals.php';

 $db = Zend_Db_Table_Abstract::getDefaultAdapter();
  
 $all_errors = '';
 $date = '';
 
 echo date(DATE_RFC2822);
 if($PERIOD_HISTORICAL_MODE){

        //echo 'historical'; exit;

 	$periods = explode(";", $PERIOD_HISTORICAL);
 	for($i=0; $i<sizeof($periods); $i++){
 		print "\n\n ===> UPLOAD PERIOD: " . $periods[$i] . " START\n\n";
 		$DATA_URL = $DATA_URL_START . $periods[$i] . $DATA_URL_END;

                //print '<br/><br/>' . $DATA_URL; exit;
                
 		upload($DATA_URL, $USERNAME, $PASSWORD, $db);
 		print "\n===> UPLOAD PERIOD: " . $periods[$i] . " END\n####################################################################################\n\n";
 	}
}
 if($PERIOD_LAST_MONTH_MODE){
        //echo 'last month'; exit;
 	print "\n\n ===> UPLOAD PERIOD: " . $PERIOD_LAST_MONTH . " START\n\n";
 	$DATA_URL = $DATA_URL_START . $PERIOD_LAST_MONTH . $DATA_URL_END;
        print '<br/><br/>' . $DATA_URL; exit;
        

       // $DATA_URL = "https://dhis2nigeria.org.ng/dhis/api/analytics.json?dimension=dx:lyVV9bPLlVy&dimension=ou:LEVEL-5; s5DPBsdoE8b&dimension=pe:LAST_12_MONTHS&displayProperty=NAME&outputIdScheme=ID";
               
        //$DATA_URL = $DATA_URL_START . $DATA_URL_END;

 	upload($DATA_URL, $USERNAME, $PASSWORD, $db);
 	print "\n===> UPLOAD PERIOD: " . $PERIOD_LAST_MONTH . " END\n\n";
 }
 echo date(DATE_RFC2822);
 
 exit;
 
 /**
  * Upload data
  */
function upload($DATA_URL, $USERNAME, $PASSWORD, $db) {
	
	$error = '';
	global $date;
        global $DATA_SOURCE, $DATA_SOURCE_JSON_FILE;

        //plog('inside upload'); exit;

        // ******************* LOAD DATA FROM DHIS2 WEB SERVICE ***************************

	//$date = "we are finally here 201503";
	// read web service 
	//print "Load data: " . $DATA_URL . "\n\n";
        plog("Load data: " . $DATA_URL);
        echo '<br/><br/>';
        
        // read from web service or file: gives facility report rate
        if($DATA_SOURCE == 'web'){
            //print '<br>WEB MODE<br><br>';
            plog('WEB MODE');
            $data_json = getWebServiceResult($DATA_URL, $USERNAME, $PASSWORD); 
        }
        else if($DATA_SOURCE == 'file'){
            print '<br>FILE MODE<br><br>';
            $data_json = file_get_contents($DATA_SOURCE_JSON_FILE);
        }
        //print 'about to print data json<br><br>';  exit;
        
        //print_r($data_json);
       //"https://dhis2nigeria.org.ng/dhis/api/analytics.json?dimension=dx:lyVV9bPLlVy&dimension=ou:LEVEL-5;%20s5DPBsdoE8b&dimension=pe:LAST_12_MONTHS&displayProperty=NAME&outputIdScheme=ID
        //$data_json = file_get_contents ("FRR_Web_Service_analytics_2015_jan_to_march.json" ); // REMOVE: for test only
	
	$data_json_arr = json_decode($data_json, true);

        /*
       $date = "201503";
       $values = array();
       foreach($data_json_arr["rows"] as $row){
           if($row[0]==$date){
               array_push($values,$row);
           }
       }
       //$data_json_arr['rows'] = array();
       //$data_json_arr['rows'] = $values;
       foreach($data_json_arr["rows"] as $row){
           //print_r($row);echo '<br/><br/>';
       }
       //exit;
       //$data_json = json_encode($data_json_arr);
       */

	$date = $data_json_arr ["metaData"] ["pe"] [0];
	$date_year = substr ( $date, 0, 4 );
	$date_month = substr ( $date, -2 );
	//print "Data period: " . $date_year . "-" . $date_month . "-01\n\n"; 
        plog("Data period: " . $date_year . "-" . $date_month . "-01"); 
	
        
        // check if these date already loaded to database before going any further to save time.
        $date_year_month = date('Y-m', strtotime('-1 month'));
        $sql = "select count(*) as count from facility_report_rate where date='" . $date_year_month . "-01'";
        plog('Check for previous: ' . $sql);
	$db_data_info_count = $db->fetchAll ($sql);
	if($db_data_info_count[0]['count'] > 0){
		plog("Data for this period had been loaded in database earlier.\n\n");
		return;
	}
        else{
            plog('Data for this period not present in db');
        }
        
	//save json output to file
//	$file = fopen("json_frr/DHIS2Upload-FacilityReportRate-". $date . ".json","w");
//	echo fwrite($file,$data_json);
//	fclose($file);
//	echo '<br>Saved file. '; exit;
        
	unset($data_json_arr["metaData"]); // remove this huge object

	//print_r($data_json_arr["rows"]); exit;

	
	// ******************* PARSING DATA ***************************
	
	$count = 0;

        //echo 'This is the length of the new array that is being created '.sizeof($data_json_arr ["rows"]).'<br/><br/><br/>';
        plog('This is the length of the new array that is being created '.sizeof($data_json_arr ["rows"]));
        //exit;
	foreach ( $data_json_arr ["rows"] as $row) {
		$facility_external_id = $row[1];
                //echo 'facility_external id is '.$facility_external_id;
		$report = $row[2];
		if($report !== '100.0'){
			$error = $error . "ERROR: " . $facility_external_id . " has value " . $report . "\n";
		}
                /* TP: Checking for unknown facility
                $faci_exists_count = $db->fetchAll( "SELECT count(*) FROM facility WHERE external_id=".$facility_external_id."");
		if($faci_exists_count<=0){
                    $facility_external_id = "";
                    
                }
                */
                //echo '<br/>';
                //print_r($row);
                //echo 'I am here &nbsp;&nbsp;&nbsp;';
                
                $result = $db->fetchAll("SELECT id  FROM facility WHERE external_id='".$facility_external_id."'");
                //print_r($result);
                
                $id = $result[0]['id'];
                //echo 'Id is '.$id;
                
                $count++;
		$bind = array(
				'facility_external_id'			=>	$facility_external_id,
				'date' => $date_year . "-" . $date_month . "-01",
                                'facility_id'=>$id
		);
		try{
			//$db->insert("facility_report_rate", $bind);
		}catch(Exception $e){
			$error = $error . "ERROR ADD DATA: " . $facility_external_id . " (" . $e->getMessage() . ")\n";
		}
		

		 echo '<br/><br/>'; echo '<br/><br/>';

	}
	
	print "\n=> REPORT RATE LOAD:\n" .  $count . " facilities have been processed.\n\n";
        plog("REPORT RATE LOAD: " .  $count . " facilities have been processed.");
	
	//validate process
	$db_data_info_count = $db->fetchAll ("select count(*) as count from facility_report_rate where date='" . $date_year . "-" . $date_month . "-01'");
	plog($db_data_info_count[0]['count'] . " facilities  in database.");
       
        
        /************* INTERFACE WITH THE COMMODITY SCRIPT *************/
        if($db_data_info_count[0]['count'] > 0){
            plog('Redirecting...');
            redirectToCommodityRates();
        }
        
	
        if(!empty($error)){
               //plog('Errors in the process: ' . $error);
               $file = fopen("json_frr/DHIS2Upload-FacilityReportRate-". $date . ".errors","w");
               fwrite($file,$error);
               fclose($file);
        }
}

//print help how to run script
function help(){
	print "This script uploads data from DHIS2\n\nUSAGE: php DHIS2Upload-FacilityReportRate.php [options] > out\nOptions:\n";
	print "\t-m - upload data for the last month period. USAGE: -m\n";
	print "\t-p - upload data for period [YYYYMM;YYYYMM], if -p is empty then upload periods [201101-201410]. USAGE:-p[YYYYMM;YYYYMM] or -p\n";
	print "\t-h - help\n";
}

	/**
	 * Read web service and return output
	 */
//        function getWebServiceResult2($commodity_data_url, $username, $password){
//            try{
//                echo 'new curl method: ' . $commodity_data_url . '<br><br>';                
//                
//                echo 'user: ' . $username . '<br/>';
//                echo 'pass: ' . $password . '<br/>';
//                
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, $commodity_data_url);
//                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
//                curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
//                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
//                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE); 
//                $output = curl_exec($ch);
//                
//                $info = @curl_getinfo($ch);
//                echo $response;
//                print_r($info);
//                
//                echo 'output: ' . $output;
//                curl_close($ch);
//            } catch (Exception $ex) {
//                echo $ex->getMessage(); exit;
//            }
//            
//            exit;
//        }
        
	function getWebServiceResult($commodity_data_url, $username, $password){
                plog('new curl method: ' . $commodity_data_url);
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
                
                try{
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $commodity_data_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    //comment later, it is for Windows only
                    
                    //echo 'after ssl verifier<br>';
                    
                    curl_setopt($ch, CURLOPT_TIMEOUT, 0);

                    $output = curl_exec($ch);
                    $info = @curl_getinfo($ch);

                    if($output === false){
                        echo 'CURL ERROR: ' . curl_error($ch);
                        plog('CURL ERROR: ' . curl_error($ch));
                        //getWebServiceResult($commodity_data_url, $username, $password);
                        exit;
                    }                    
                } catch (Exception $e){
                    echo $e->getMessage();
                }
                
		curl_close($ch);
                return $output;
	}
	
	function getReportedFacilities(){
// 		select facility.external_id, facility.facility_name from facility
    // 		inner join facility_report_rate  on facility.external_id=facility_report_rate.facility_external_id
// 		where facility_report_rate.date='2014-11-01';
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

    
    function redirectToCommodityRates_curl(){
  		plog('inside redirect method');
                  $commodity_file_url = 'http://techieplanetltd.com/chai/trainsmart/sites/DHIS2Upload-FacilityCommodity.php';
                  
  		$ch = curl_init();
  		curl_setopt($ch, CURLOPT_URL, $commodity_file_url);
                  curl_exec($ch);
  		curl_close($ch);
    }


    function redirectToCommodityRates(){
      shell_exec('php DHIS2Upload-FacilityCommodity.php');
    }
        
        
    function plog($logMessage){
        $logMessage = date('Y-m-d H:i:s') . ' ' . $logMessage . "\n";
        file_put_contents("json_frr/logs.txt", $logMessage, FILE_APPEND);
    }

?>