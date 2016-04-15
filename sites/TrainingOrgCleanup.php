<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
ini_set('display_errors', 'On');
require_once 'globals.php';

    $dirtyIDs = array(
        array('dirty'=>69, 'clean'=>14),
        array('dirty'=>70, 'clean'=>14),
        array('dirty'=>65, 'clean'=>39),
        array('dirty'=>66, 'clean'=>39),
        array('dirty'=>67, 'clean'=>39),
        array('dirty'=>68, 'clean'=>39),
    );
            
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    $success =0; $failures = '';
    
    
    foreach($dirtyIDs as $idset){
            $db->getProfiler()->setEnabled(true);
            //if($idset['dirty'] != $idset['clean']){  
                //update dirty with clean on training, delete dirty,                 
                    //update training, delete dirty
                    $sql = "UPDATE training " .
                           "SET training_organizer_option_id = '" . $idset['clean'] . "' " .
                           " WHERE training_organizer_option_id=" . $idset['dirty'];
                    $stmt = $db->query($sql);
                    print $db->getProfiler()->getLastQueryProfile()->getQuery();
                    print 'Rows Affected: ' . $stmt->rowCount() . '<br>';
                    
                    $sql = 'UPDATE training_organizer_option SET is_deleted = 1 WHERE id = ' . $idset['dirty'];
                    $stmt = $db->query($sql);                
                    
            //} 
                    print $db->getProfiler()->getLastQueryProfile()->getQuery();
                    print 'Rows Affected: ' . $stmt->rowCount() . '<br><br>';
            
            
            
            //var_dump($stmt);
//            if($rows > 0){
//                $success++;
//            }
//            else 
//                $failures .= 'dirty ID: ' . $idset['dirty'] . ' clean ID: ' . $idset['clean'] . '<br>';
            
            //break;
        }
        $db->getProfiler()->setEnabled(false);
    
    //echo 'The number of successful updates: ' . $success . '<br><br>';
    
    //echo 'Failed updates: ' . '<br><br>' . $failures;
?>

