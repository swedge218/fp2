<?php
	ini_set('display_errors','On');
	echo 'mailer';
	$headers = 'From: no-reply@fpdashboard.ng';
	mail('sewejeolaleke@gmail.com', 'Testing', 'message is cool but no reply please', $headers);
?>