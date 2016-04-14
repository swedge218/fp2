<?php
/*
 * Created on Feb 11, 2008
 *
 *  Built for web
 *  Fuse IQ -- todd@fuseiq.com
 *
 */
require_once ('models/table/OptionList.php');
require_once ('controllers/ITechController.php');

class DesktopController extends ITechController {
	
	private $desktop_db = null;
	
	private $zip_name = 'trainsmart.zip';
	
	private	$package_dir = "";
	
	private $error_message = "";
	
	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array()) {
		parent::__construct ( $request, $response, $invokeArgs );
		
	}
	
	public function init() {
		// Site specific files go here (/app/desktop/distro/data/settings.xml + copy of emtpy sqlite db file)
		$this->package_dir = Globals::$BASE_PATH.'sites/'.str_replace(' ', '_', Globals::$COUNTRY).'/desktop';
	
	}
	
	public function preDispatch() {
		$rtn = parent::preDispatch ();
		
		return $rtn;
	
	}
	
	public function downloadDotNetAction() {
		$this->init();
		$go = $this->getSanParam('go');
		if ($go == 1) {
			header("Location: " . Settings::$COUNTRY_BASE_URL . "/dotnetfx.zip");
			exit;
		}
	}

	// Sean Smith 10/20/11: Package and download actions split out to separate "actions"
	// for slow net link users. Server set up to cron this action every hour, so that users
	// only have to download the app.
	public function createAction() {
		//ini_set ( "memory_limit", "256M" );
		
		// Sean Smith: 10/26/2011 - Reworked app packaging to handle multiple 
		// sites calling this function at once

		$this->init();

		// Check for existing site directory, copy package files from code 
		// source location (/app/desktop/distro) to site dir
		if (!$this->_prepareSiteDirectory()) {
			// If dir structure not right, or a file copy failed, 
			// bail and render error page (download.phtml)
			$this->view->assign ( 'error_message', 'Could not create package. The error was: '.$this->error_message );
			return; 
		}

		// Populate sqlite db from mysql db
		$this->dbAction ();

		//zip up
		require_once('app/desktop/Zip.php');
		$file_collection = array();
		$zipNameLen = strlen($this->zip_name);

		unlink($this->package_dir.'/'.$this->zip_name);
		$archive = new Archive_Zip($this->package_dir.'/'.$this->zip_name);

		// Gather up all files in distro directory
		// initialize an iterator pass it the directory to be processed
		$iterator = new RecursiveIteratorIterator ( new RecursiveDirectoryIterator ( Globals::$BASE_PATH.'app/desktop/distro' ) );
		// iterate over the directory add each file found to the archive
		foreach ( $iterator as $key => $value ) {
			// Exclude the zip file itself (if exists from a previous creation)
			if (substr($key, $zipNameLen * -1, $zipNameLen) != $this->zip_name)
				$core_file_collection []= realpath ( $key );
		}
		
		// Gather up the site specific files (data/settings.xml and data/sqlite.db)
		$iterator = new RecursiveIteratorIterator ( new RecursiveDirectoryIterator ( $this->package_dir ) );
		foreach ( $iterator as $key => $value ) {
			// Exclude the zip file itself
			if (substr($key, $zipNameLen * -1, $zipNameLen) != $this->zip_name)
				$site_file_collection []= realpath ( $key );
		}	

		// Files added in two stages because there are two paths to remove 
		// (app/desktop/distro and package_dir) but zip procs will only take one path to remove per call
		$archive->create($site_file_collection,array('remove_path'=>$this->package_dir, 'add_path'=>'TS'));	
		$archive->add($core_file_collection,array('remove_path'=>Globals::$BASE_PATH.'app/desktop/distro', 'add_path'=>'TS'));
		
	}
	
	private function _prepareSiteDirectory() {
		// Make sure site has it's own directory of files to zip, so no 
		// collisions with other sites creating app package at the same time
		try {
			// Make sure directory structure exists. All files in this tree, plus /app/desktop/distro tree will be zipped together
			$old = umask(0); 
			if (!file_exists($this->package_dir)) {
				if (! mkdir($this->package_dir, 0777, true)) throw new Exception('Could not create site directory'); // Make recursive dir structure (make all dirs)
				chmod($this->package_dir,0777);
			}
			if (!file_exists($this->package_dir.'/data')) {
				if (! mkdir($this->package_dir.'/data',0777)) throw new Exception('Could not create site data directory'); 
			}
			umask($old); 
		} catch (Exception $e) {
			$this->error_message = $e->getMessage();
			$this->view->assign ( 'error_message', $this->error_message );
			return false;
		}
			
		
		// Copy site specific settings file and a blank copy of the database to the site's 
		// desktop dir. The database will be populated later, before zip is created
		try {
			// Create settings file in the site's desktop directory (/sites/<countryName>/data)
			//copy (Globals::$BASE_PATH.Globals::$WEB_FOLDER.'/Settings.xml', $dp.'data/Settings.xml') 
			$curFile = 'settings';
			$this->settingsAction ($this->package_dir.'/data/');	

			// Always start with a fresh blank datbase file
			$curFile = 'sqlite';
			if (! copy (Globals::$BASE_PATH.'/app/desktop/trainsmart.template.sqlite', $this->package_dir.'/data/trainsmart.active.sqlite') ) throw('PHP copy function did not succeed');
		
		} catch (Exception $e) {
			$this->error_message = 'Failure copying '.$curFile.' file. The exact error was '.$e->getMessage();
			$this->view->assign ( 'error_message', $this->error_message );
			return false;
		}
						
		
		if (!file_exists($this->package_dir.'/data')) {
			$this->view->assign ( 'error_message', 'Could not initialize site directory.' );
			return false;
		}
		
		return true;
	}

	
	
	// Sean Smith 10/20/11: This function is deprecated. 
	// Create and download functions have been split into two separate procedures
	public function distroAction() {
		if (! $this->isLoggedIn ())
			$this->doNoAccessError ();
		
		if (! $this->hasACL ( 'edit_country_options' ) && ! $this->hasACL ( 'use_offline_app' )) {
			$this->doNoAccessError ();
		}
		
		$this->createAction(); // Package the sqlite db and offline app into a zip file
		
    $this->_pushZip();

	}
	
	public function dbAction() {
		set_time_limit ( 300 );

		require_once 'Zend/Db.php';
		//set a default database adaptor
		//$db = Zend_Db::factory ( 'PDO_SQLITE', array ('dbname' => Globals::$BASE_PATH . 'app/desktop/trainsmart.active.sqlite' ) );
		$db = Zend_Db::factory ( 'PDO_SQLITE', array ('dbname' => $this->package_dir .'/data/trainsmart.active.sqlite' ) );
		$GLOBALS['debug'] = $this->package_dir .'/data/trainsmart.active.sqlite';
		
		require_once 'Zend/Db/Adapter/Abstract.php';
		if (! $db instanceof Zend_Db_Adapter_Abstract) {
			require_once 'Zend/Db/Table/Exception.php';
			throw new Zend_Db_Table_Exception ( 'Could not create sqlite adaptor' );
		}
		$this->desktop_db = $db;
		$litedb = $this->desktop_db;
		
		//$liteSysTable = new System(array( Zend_Db_Table_Abstract::ADAPTER => $litedb));
		//$liteSysTable->select('*');
		
		require_once 'sync/SyncCompare.php';
		$option_tables = SyncCompare::$compareTypes;
		
		// require_once('models/table/System.php');
		foreach ( $option_tables as $opt ) {
			//$GLOBALS['debug'] = $opt;
			try {
				$step = 1;
			$optTable = new OptionList ( array ('name' => $opt ) );

				$step = 2;
			$liteTable = new OptionList ( array ('name' => $opt, Zend_Db_Table_Abstract::ADAPTER => $litedb ) );
			} catch (Exception $e) {
				echo "--- err matching/finding tables $opt, step $step [".($step==1?"db":"sqlite")."] --- <BR>";
				echo $e->getMessage();
			}

			$optTable->select ( '*' );
			$rowset = $optTable->fetchAll ();
			$liteKeys = $liteTable->createRow ()->toArray ();
#			if ($opt == 'age_range_option') echo "!!keys:: ".print_r($liteKeys, true);
#			if ($opt == 'age_range_option') echo "!!data:: ".count($rowset);

			foreach ( $rowset as $optRow ) {
				$data = $optRow->toArray ();
				
				foreach ( array_keys ( $data ) as $k ) {
					if ($opt == 'age_range_option') echo "@@: $k";
					if (! array_key_exists ( $k, $liteKeys ))
						unset ( $data [$k] );
				}
				try {
					if(isset($data['training_id']) && $data['training_id'] == null){
						$data['training_id'] = 0; //bugfix - FK training_id not accepting nulls. production db has "bad" data
					}
					#debug
#					if ($opt == 'age_range_option') {
#						echo " adding row @ '$opt': ".print_r($data,true).PHP_EOL;
#					}
				$liteTable->insert ( $data );
				} catch (Exception $e) {
					echo "############## skipping data ($opt)";
					echo '<br><pre>'.$e->getMessage()."\n";
					print_r($data);
				}
			
			}
		}
	}

	public function downloadAction() {
		$this->init();
		
		// Sean Smith 10/20/11: Moved security check to distro action and to download action
		// This way a text based browser (Lynx) can package the application without
		// having to log in.
		if (! $this->isLoggedIn ())
			$this->doNoAccessError ();
		
		if (! $this->hasACL ( 'edit_country_options' ) && ! $this->hasACL ( 'use_offline_app' )) {
			$this->doNoAccessError ();
		}
		
		// If no zip file, then Create App never performed (do this first)
		if (! file_exists($this->package_dir.'/'.$this->zip_name)) 
		{
			$this->createAction();
		}
				
		// Assumes createAction called every hour by cron job
		// Allows slow net link users to only download a prepackaged zip file
		// instead of having to wait for the entire package event and then the
		// entire download (combined time was breaking in remote locations)
		$this->_pushZip();
	}
		
	
	function _pushZip() {
		$this->init();
		
		try {
      if ($fd = @fopen($this->package_dir.'/'.$this->zip_name, 'rb')) {
				// Sean Smith: 10/26/2011 - Had to rework. Wasn't working in production. Super paranoid mode on.
				ini_set('magic_quotes_runtime', 0);
		    ob_end_clean();
		    ob_start(); 
		    $buffer = "";
		
		    header('Content-Type:'); // application/zip
		    header('Content-Disposition: inline; filename="' . $this->zip_name.'"');
		    header('Cache-Control: private, max-age=0, must-revalidate');
		    header('Pragma: public');

        while (!feof($fd)) {
        	$buffer = fread($fd, 1024); // Next two lines was simply:  print fread($fd, 1024);
	        echo $buffer;
	        ob_flush(); 
	        flush();         	
        }
        fclose($fd);
      } else {
      	$this->view->assign ( 'error_message', 'Could not open file '.$this->package_dir.'/'.$this->zip_name .'. ' );
      	return;
      }
      exit(); 
    } catch (Exception $e) {
    	$this->view->assign ( 'error_message', 'Could not open file '.$this->package_dir.'/'.$this->zip_name.'. The error was '.$e->getMessage() );
    } 
	}
	
	public function settingsAction($path) {
		require_once ('models/table/System.php');
		$sysTable = new System ( );
		
		@date_default_timezone_set ( "GMT" );
		$settingsWriter = new XMLWriter ( );
		
		// Output directly to the user 
		

		$settingsWriter->openURI ( $path.'Settings.xml' );
		$settingsWriter->startDocument ( '1.0' );
		
		$settingsWriter->setIndent ( 4 );
		$settingsWriter->startElement ( 'system' );
		$settingsWriter->writeAttribute ( 'version', '1.0' );
		$settingsWriter->writeAttribute ( 'password', 'mango' );
		$sysTable->select ( '*' );
		$row = $sysTable->fetchRow ();
		foreach ( $row->toArray () as $k => $v ) {
			$settingsWriter->writeAttribute ( $k, $v );
		}
		
		$option_tables = array ('translation' );
		
		foreach ( $option_tables as $opt ) {
			$settingsWriter->startElement ( str_replace ( '_option', '', $opt ) );
			$optTable = new OptionList ( array ('name' => $opt ) );
			$optTable->select ( '*' );
			foreach ( $optTable->fetchAll () as $row ) {
				$settingsWriter->startElement ( 'add' );
				foreach ( $row->toArray () as $k => $v ) {
					if ($k == 'id') {
						$settingsWriter->writeAttribute ( 'key', $v );
					} else if (strpos ( $k, '_phrase' )) {
						$settingsWriter->writeAttribute ( 'value', $v );
					} else {
						$settingsWriter->writeAttribute ( $k, $v );
					}
				}
				$settingsWriter->endElement ();
			}
			
			$settingsWriter->endElement ();
		}
		
		// End Item 
		$settingsWriter->endElement ();
	
	}
}
?>