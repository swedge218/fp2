<?php

	$conn = new mysqli("localhost", "root", "", "testnodef");

	$tablename = "age_range_option";

	//$results = $conn->query("select * from $tablename");	

	 /* while($row = $results->fetch_array()){
		if($row['timestamp_created'] == "0000-00-00 00:00:00"){
	 		$query = "UPDATE $tablename SET timestamp_created = timestamp_updated WHERE id = " . $row["id"];
	 	 	if($conn->query($query))
	 	 		echo "Updated " . $row["id"] . "<br/>";
			 else
	 	 		echo "Failed " . $row["id"] . "<br/>";
	 	}
	 } */
	 
	 
	 $select = "SELECT COUNT(DISTINCT(p.id)) AS `count`, `flv`.`lga`, `flv`.`state`, `flv`.`geo_zone` FROM `person` AS `p` INNER JOIN `person_to_training` AS `ptt` ON ptt.person_id=p.id INNER JOIN `training` AS `t` ON t.id = ptt.training_id INNER JOIN `training_title_option` AS `tto` ON tto.id = t.training_title_option_id INNER JOIN `facility_location_view` AS `flv` ON flv.id = p.facility_id WHERE YEAR(t.training_end_date) <= '2016' AND tto.system_training_type IN ('fp','larc') AND tto.is_deleted=0 AND t.is_deleted = 0 AND flv.geo_parent_id IN ('1811','1812','1813','1814','1815','1816')ORDER BY `geo_zone` ASC";
	 $results = $conn->query($select);	
	 echo $results->num_rows;

	 $results->free();
	 $conn->close();

?>