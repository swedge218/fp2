<?php
/*
 * Created on Feb 11, 2008
 *
 *  Built for web
 *  Fuse IQ -- todd@fuseiq.com
 *  Leke Seweje -  Techie Planet
 *  
 *
 */
ini_set('display_errors', 'On');

require_once ('ITechController.php');

require_once ('ReportFilterHelpers.php');
require_once ('models/table/Helper22.php');
require_once('models/table/Dashboard-CHAI.php');
require_once('models/table/Dashboard2.php');

class Index2Controller extends ReportFilterHelpers  {
        private $helper; // = new Helper2();
        
	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array()) {
		parent::__construct ( $request, $response, $invokeArgs );
                $this->helper = new Helper22();
    	}

	public function init() {	}
	
        public function indexAction(){
            //fetch consumption by method
//            $dashboard = new Dashboard();
            list($geoList, $tierValue) = $this->buildParameters();                
//            
//            $consumptionbyMethod = $dashboard->fetchConsumptionByMethod();
//            $coverageSummary = $dashboard->fetchCoverageSummary($geoList, $tierValue);
//            
//            $fp_facsProviding = $dashboard->fetchFacsProviding('fp');
//            $larc_facsProviding = $dashboard->fetchFacsProviding('larc');
            
            $dashboard = new Dashboard2();
            $fp_facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedoutOvertime('fp', $geoList, $tierValue, true);
            $larc_facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedoutOvertime('larc', $geoList, $tierValue, true);
            
//            var_dump($fp_facsProvidingStockedout);
//            echo '<br><br>';
//            var_dump($larc_facsProvidingStockedout);
//            exit;
            //$larc_facsProvidingStockedout = $dashboard->fetchFacsProvidingStockedout($geoList, $tierValue);
            
            //var_dump($coverageSummary); exit;
            
//            $this->view->assign('consumption_by_method', $consumptionbyMethod);
//            $this->view->assign('csummary', $coverageSummary);
//            $this->view->assign('fp_facs_providing', $fp_facsProviding);
//            $this->view->assign('larc_facs_providing', $larc_facsProviding);
              $this->view->assign('fp_facs_providing_stockedout', $fp_facsProvidingStockedout);
              $this->view->assign('larc_facs_providing_stockedout', $larc_facsProvidingStockedout);
              
            
            
            $title_date = $this->helper->fetchTitleDate();
            $this->view->assign('title_date', $title_date['month_name'] . ' ' . $title_date['year']);
            
            $overTimeDates = $this->helper->getPreviousMonthDates(12);
            $this->view->assign('end_date', date('F', strtotime($overTimeDates[0])). ' '. date('Y', strtotime($overTimeDates[0]))); 
            $this->view->assign('start_date', date('F', strtotime($overTimeDates[11])). ' '. date('Y', strtotime($overTimeDates[11]))); 
        }
        
        public function itestAction(){
            //fetch consumption by method
            $dashboard = new Dashboard();
            
            //fetch covergae summary
            $coverageSummary = $dashboard->fetchCoverageSummary();
            $this->view->assign('csummary', $coverageSummary);
        }


        

        
        private function coverageCalculations($cs_details){
            $cs_calc = array(
                        'cs_fp_facility_count' => round($cs_details['fp_facility_count']/$cs_details['total_facility_count_month'], 2),
                        'cs_larc_facility_count' => round($cs_details['larc_facility_count']/$cs_details['total_facility_count_month'], 2),
                        'cs_fp_consumption_facility_count' => round($cs_details['fp_consumption_facility_count']/$cs_details['fp_facility_count'], 2),
        	        'cs_larc_consumption_facility_count' => round($cs_details['larc_consumption_facility_count']/$cs_details['larc_facility_count'], 2),
                        'cs_fp_stock_out_facility_count' => round($cs_details['fp_stock_out_facility_count']/$cs_details['fp_facility_count'], 2),
                        'cs_larc_stock_out_facility_count' => round($cs_details['larc_stock_out_facility_count']/$cs_details['larc_facility_count'], 2),
                        'cs_date' => date_format(date_create($cs_details['last_date']), 'F Y'),
                    );
            
            return $cs_calc;
        }
        
        
        
       

	public function languageAction() {
		require_once ('models/Session.php');
		require_once ('models/table/User.php');

		if ($this->isLoggedIn () and array_key_exists ( $this->getSanParam ( 'opt' ), ITechTranslate::getLanguages () )) {
			$user = new User ( );
			$userRow = $user->find ( Session::getCurrentUserId () )->current ();
			$user->updateLocale ( $this->getSanParam ( 'opt' ), Session::getCurrentUserId () );

			$auth = Zend_Auth::getInstance ();
			$identity = $auth->getIdentity ();
			$identity->locale = $this->getSanParam ( 'opt' );
			$auth->getStorage ()->write ( $identity );
			setcookie ( 'locale', $this->getSanParam ( 'opt' ), null, Globals::$BASE_PATH );
		}

		$this->_redirect ( $_SERVER ['HTTP_REFERER'] );

	}

	public function jsAggregateAction() {
		#$headers = apache_request_headers ();

		// Checking if the client is validating his cache and if it is current.
		/*
	    if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) > time() - 60*60*24)) {
	        // Client's cache IS current, so we just respond '304 Not Modified'.
	        header('Last-Modified: '.gmdate('D, d M Y H:i:s',  time()).' GMT', true, 304);
			$this->setNoRenderer();
	    }
		#echo Globals::$BASE_PATH.Globals::$WEB_FOLDER.$file;
		#exit;
		*/

		$response = $this->getResponse ();
		$response->clearHeaders ();

		//allow cache
		#$response->setHeader ( 'Expires', gmdate ( 'D, d M Y H:i:s', time () + 60 * 60 * 30 ) . ' GMT', true );
		#$response->setHeader ( 'Cache-Control', 'max-age=7200, public', true );
		#$response->setHeader ( 'Last-Modified', '', true );
		#$response->setHeader ( 'Cache-Control',  "public, must-revalidate, max-age=".(60*60*24*7), true ); // new ver TS new JS file
		$response->setHeader ( 'Cache-Control',  "must-revalidate, max-age=".(60*60*24*7), true ); // new ver TS new JS file
		#$response->setHeader ( 'Pragma', 'public', true );
		$response->setHeader ( 'Last-Modified',''.date('D, d M Y H:i:s', strtotime('18 March 2013 19:20')).' GMT', true ); // todo update this when thers a new javascript file to force re dl
		$response->setHeader ( 'Content-type', 'application/javascript' ); // should fix inspector warnings (was text/html)

	}

}
?>