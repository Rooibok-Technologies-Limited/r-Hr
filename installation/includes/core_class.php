<?php

class Core {

	/*Function to set JSON output*/
	function output($Return=array()){
		/*Set response header*/
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json; charset=UTF-8");
		/*Final JSON response*/
		exit(json_encode($Return));
	}
	// Function to validate the post data
	function validate_post($data)
	{
		/* Validating the hostname, the database name and the username. The password is optional. */
		return !empty($data['hostname']) && !empty($data['username']) && !empty($data['database']);
	}

	// Function to show an error
	function show_message($type,$message) {
		return $message;
	}

	// Function to write the config file
	function write_config($hostname,$username,$password,$database) {

		// Config path
		$template_path 	= './config/Database.php';
		$output_path 	= '../app/Config/Database.php';

		// Open the file
		$database_file = file_get_contents($template_path);

		$new  = str_replace("%HOSTNAME%",$hostname,$database_file);
		$new  = str_replace("%USERNAME%",$username,$new);
		$new  = str_replace("%PASSWORD%",$password,$new);
		$new  = str_replace("%DATABASE%",$database,$new);

		// Write the new database.php file
		$handle = fopen($output_path,'w+');

		// Chmod the file with secure permissions (owner read/write only)
		@chmod($output_path,0644);

		// Verify file permissions
		if(is_writable($output_path)) {

			// Write the file
			if(fwrite($handle,$new)) {
				fclose($handle);
				return true;
			} else {
				fclose($handle);
				return false;
			}

		} else {
			if($handle) { fclose($handle); }
			return false;
		}
	}
}