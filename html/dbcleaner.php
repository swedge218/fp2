<?php

	$conn = new mysqli("localhost", "root", "", "trainsmartdb");

	$tablename = "age_range_option";

	$results = $conn->query("select * from $tablename");	

	 while($row = $results->fetch_array()){
		if($row['timestamp_created'] == "0000-00-00 00:00:00"){
	 		$query = "UPDATE $tablename SET timestamp_created = timestamp_updated WHERE id = " . $row["id"];
	 	 	if($conn->query($query))
	 	 		echo "Updated " . $row["id"] . "<br/>";
			 else
	 	 		echo "Failed " . $row["id"] . "<br/>";
	 	}
	 }

	 $results->free();
	 $conn->close();

?>