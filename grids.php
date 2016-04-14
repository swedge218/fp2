<?php
	
	$link = mysql_connect('localhost', 'techie17_user50', 'user50pass');
	//if($link) echo 'link';
	$db = mysql_select_db('techie17_trainsmart');
	//if($db) echo 'db'; exit;
	if($db){
		try{
				$result = mysql_query("SELECT f.id as fid from facility f JOIN facility_report_rate frr
where frr.facility_external_id = f.external_id and frr.date = '2015-01-01'");

				$ids = ''; $count = 0;
				while ($row = mysql_fetch_assoc($result)) {
					   $id =  $row['fid'];

					   $sql = "update commodity set facility_reporting_status = 1 where facility_id =$id AND date = '2014-12-01'";
					   //echo $sql; exit;
					   if(mysql_query($sql))
					   		$count++;
				}
	} catch(Exception $e){
		echo $e->getMessage();
	}



		echo $count;


	}

	
?>