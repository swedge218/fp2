<?php
	
	$link = mysql_connect('localhost', 'techie17_user50', 'user50pass');
	//if($link) echo 'link';
	$db = mysql_select_db('techie17_trainsmart');
	//if($db) echo 'db'; exit;
	if($db){
		try{
				$result = mysql_query("SELECT f.id as fid,f.external_id as fext from facility f JOIN facility_report_rate frr
where frr.facility_external_id = f.external_id and frr.date = '2014-12-01'");

				$ids = ''; $count = 0;

				//if($result) echo 'has result: ' . mysql_num_rows($result); exit;

				while ($row = mysql_fetch_assoc($result)) {
					   $id =  $row['fid'];
					   $ext = "'" . $row['fext'] . "'";
					   $sql = "update facility_report_rate set facility_id = $id where facility_external_id =$ext AND date = '2014-12-01'";

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