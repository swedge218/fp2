<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ConsumptionController
 *
 * @author Swedge
 */
require_once ('ReportFilterHelpers.php');
require_once ('models/table/Helper2.php');
require_once('models/table/Consumption.php');
class ConsumptionController extends ReportFilterHelpers {
    //put your code here

    public function init(){
        $burl = Settings::$COUNTRY_BASE_URL;
        if (substr($burl, -1) != '/' && substr($burl, -1) != '\\')
            $this->baseUrl = $burl . '/';
    }
    
    public function preDispatch() {
            parent::preDispatch ();

            if (!$this->isLoggedIn ())
                    $this->doNoAccessError ();

            //if (! $this->setting('module_employee_enabled')){
                    //$_SESSION['status'] = t('The employee module is not enabled on this site.');
                    //$this->_redirect('select/select');
            //}

            //if (! $this->hasACL ( 'employees_module' )) {
                    //$this->doNoAccessError ();
            //}
    }
    
    
    public function showitAction(){
        //var_dump($_POST); exit;
        $helper = new Helper2();
        $this->view->assign('text', 'this is a method');
        $this->view->assign('comms', $helper->getCommodities());
        $this->viewAssignEscaped ('locations', Location::getAll(1) );
    }
    
    
    
    public function consumptionAction(){
        $helper = new Helper2();
        $cons = new Consumption();
        //$this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));

        if( !isset($_POST["comm_id"]) || $_POST["comm_id"] == 0)
            $commodityID = 0;
        else
            $commodityID = $_POST['comm_id'];
        $this->view->assign('commodityID',$commodityID);
        //echo $commodityID; exit;
        
        //get the parameters
        list($geoList, $tierValue) = $this->buildParameters();

        
        $all = false;
        
 
        if($commodityID > 0){  //commodity selected
            if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                list($methodName,$consBySingleCommodity) = $cons->fetchConsumptionBySingleCommodityOverTime($commodityID, $geoList, $tierValue);
                //var_dump($consBySingleCommodity); exit;
              if(strtolower($methodName)!="iucds"){
                    $methodName = strtolower($methodName);
                }
                $this->view->assign('method', $methodName);
                $this->view->assign('consumption_bsm', $consBySingleCommodity);

                end($consBySingleCommodity);
                $endKey = key($consBySingleCommodity);
                $this->view->assign('consumption_bsm_first_element', array('method'=>$methodName, 
                    'consumption'=>$consBySingleCommodity[$endKey]));
            }
            else{ // geo selected
                list($methodName,$consBySingleCommodityAndLocation) = $cons->fetchConsumptionByCommodityAndLocationOverTime($commodityID, $geoList, $tierValue);
                if(strtolower($methodName)!="iucds"){
                    $methodName = strtolower($methodName);
                }
                $this->view->assign('method', $methodName);
                $this->view->assign('consumption_bsmandlocation', $consBySingleCommodityAndLocation);
                
                //var_dump($consBySingleCommodityAndLocation); print '<br><br>';
                
                //get current month consumption 
                end($consBySingleCommodityAndLocation);
                $endKey = key($consBySingleCommodityAndLocation);
                $this->view->assign('consumption_bsmandlocation_first', array('method'=>$methodName, 
                    'consumption'=>$consBySingleCommodityAndLocation[$endKey]));
                
            }
        }
        else{ //ALL: when ALL commodity and/or no geo option selected
            $consByCommodity = $consOverTime = array();
          if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
                $consByCommodity = $cons->fetchConsumptiomPerCommodity($commodityID, $geoList, $tierValue);
                $consOverTime = $cons->fetchConsumptionByCommodityOverTime();
                
                $this->view->assign('consumption_by_method',$consByCommodity);
                $this->view->assign('consumption_overtime',$consOverTime);
          }
          else{ //geo selected
              $consByCommodity = $cons->fetchConsumptiomPerCommodity($commodityID, $geoList, $tierValue);
              //var_dump($consByCommodity); exit;
              //$helper->plog(print_r($consByCommodity));
              //var_dump($consByCommodity); echo '<br><br>';
              //$consOverTime = $cons->fetchConsumptionByCommodityOverTime($geoList, $tierValue);
              list($location,$consAllBySingleLocOverTime) = $cons->fetchAllConsumptionBySingleLocationOverTime($geoList, $tierValue);
              //var_dump($location); echo '<br><br>';
              //var_dump($consAllBySingleLocOverTime); echo '<br><br>'; exit;
              
              $this->view->assign('single_location', $location);
              $this->view->assign('consumption_by_method',$consByCommodity);
              $this->view->assign('cons_all_BSL_overtime',$consAllBySingleLocOverTime);
          }   
        }

        //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
        //GNR:use max commodity date
        $sDate = $helper->fetchTitleDate();
        $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 
        
        $overTimeDates = $helper->getPreviousMonthDates(12);
        $this->view->assign('start_date', date('F', strtotime($overTimeDates[11])). ' '. date('Y', strtotime($overTimeDates[11]))); 
        $this->view->assign('end_date', date('F', strtotime($overTimeDates[0])). ' '. date('Y', strtotime($overTimeDates[0]))); 
        
        //this will provide the commodities list for the drop down
        $this->view->assign('comms', $helper->getCommodities());
        
        $this->viewAssignEscaped('criteria', $this->getLocationCriteria());
        $this->viewAssignEscaped ('locations', Location::getAll(1) );
        
        $this->view->assign('base_url', $this->baseUrl);$this->view->assign('base_url', $this->baseUrl);

    }
    
    
    public function consbygeographyAction() {
        
        $helper = new Helper2();

        $this->view->assign('title',$this->t['Application Name'].space.t('CHAI').space.t('Dashboard'));

        if( !isset($_POST["comm_id"]) || $_POST["comm_id"] == 0)
            $commodityID = 0;
        else
            $commodityID = $_POST['comm_id'];
        //echo $commodityID; exit;
        
        //get the parameters
        list($geoList, $tierValue) = $this->buildParameters();

        //get the location names
        //$locationNames = $helper->getLocationNames($geoList);

        $cons = new Consumption();
        if( !isset($_POST["region_c_id"]) && !isset($_POST["district_id"]) && !isset($_POST["province_id"]) ) { 
            $consByCommodity = $cons->fetchConsumptiomPerCommodity($commodityID, $geoList, $tierValue);
            $this->view->assign('consumption_by_method',$consByCommodity);
        }
        else{
            //echo 'cons cons'; exit;
            //$consByGeo
            $consByCommodity = $cons->fetchConsumptiomPerCommodity($commodityID, $geoList, $tierValue);
            //$consByGeo = $cons->fetchConsumptiomByGeography($commodityID, $geoList, $tierValue);
            //$methodName = $consByGeo['method'];
            //$consByGeoData = $consByGeo['locationdata'];
            
            //var_dump($consByCommodity);
            //var_dump($consByGeoData); exit;

            
            $this->view->assign('consumption_by_method',$consByCommodity);
            //$this->view->assign('consumption_by_geo',$consByGeoData);
            //$this->view->assign('method',$methodName);
      }
        
        

        //$this->view->assign('date', date('F Y', strtotime("-1 months"))); //TA:17:18: take last month
        //GNR:use max commodity date
        $sDate = $helper->fetchTitleDate();
        $this->view->assign('date', $sDate['month_name'].' '.$sDate['year']); 
            
        //this will provide the commodities list for the drop down
        $this->view->assign('comms', $helper->getCommodities());
        
        $this->viewAssignEscaped ('locations', Location::getAll(1) );
        
        $this->view->assign('base_url', $this->baseUrl);$this->view->assign('base_url', $this->baseUrl);

  } // dash996Action
}

?>
