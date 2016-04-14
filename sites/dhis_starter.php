<?php
	//this will start the FRR script which will on completion of 
	//its own process, trigger the CRR script
	$output = shell_exec('php DHIS2Upload-FacilityReportRate.php');
?>