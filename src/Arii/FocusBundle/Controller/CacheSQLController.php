<?php

namespace Arii\FocusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// Recuperation directe entre les 2 bases de données
class CacheSQLController extends Controller
{
    protected $mode = 'WEB';
    
    public function job_runtimesAction($db) { // Si plusieurs base de données, $db permet de retrouver la connexion
        set_time_limit(600);
        $arii = $this->container->get('arii_core.db');
        $data = $arii->Connector('data');

        // On fait une image des jobs
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('fj.ID JOB_ID','fj.PATH as JOB_NAME',
                        'fs.ID as SPOOLER_ID','fs.NAME as SPOOLER'))
                .$sql->From(array('FOCUS_JOBS fj'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fj.spooler_id','fs.id'));
        
        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $c_job=0;
        while ( $line = $data->sql->get_next($res) ) {
                $spooler = $line['SPOOLER'];
                $job = $line['JOB_NAME'];
                $JobID[$spooler.$job] = $line['JOB_ID']; 
                $SpoolerID[$spooler] = $line['SPOOLER_ID']; 
                $c_job++;
        }
        $this->PrintMessage("Jobs: $c_job");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));

        // On fait une image de la table
        $qry = $sql->Select(array('fjs.ID','fjs.JOB_ID','fjs.RUN_TIME','fjs.RUNS','fjs.CRC','fjs.HISTORY_ID',
                                    'fj.PATH as JOB_NAME',
                                    'fs.NAME as SPOOLER_ID'))
                .$sql->From(array('FOCUS_JOB_RUNTIMES fjs'))
                .$sql->LeftJoin('FOCUS_JOBS fj',array('fjs.JOB_ID','fj.ID'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fj.SPOOLER_ID','fs.ID'));
                
        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $c_job=0;
        $StatusID = array();
        $JobCRC = array();
        $max_id = 0; // Dernier ID traite, inutile d'aller plus loin la fois d'après
        while ( $line = $data->sql->get_next($res) ) {
                $spooler_id = $line['SPOOLER_ID'];
                $job_id = $line['JOB_ID'];
                $job_name = $line['JOB_NAME'];
                $JobCRC[$spooler_id.$job_name] = $line['CRC'];
                $StatusID[$spooler_id.$job_name] = $line['ID'];
                $OldRuntimes[$spooler_id.$job_name] = $line['RUN_TIME'];
                $OldRuns[$spooler_id.$job_name] = $line['RUNS'];
                if ($line['HISTORY_ID']>$max_id)
                    $max_id = $line['HISTORY_ID'];
                $c_job++;
        }
        $this->PrintMessage("History ID: $max_id");        
        $this->PrintMessage("Status: $c_job");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $timestamp = time();

        // On recupere les temps de la base du scheduler
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $dbscheduler = $dhtmlx->Connector('data');
        $qry = $sql->Select(array('ID','SPOOLER_ID','JOB_NAME','START_TIME','END_TIME'))
                .$sql->From(array('SCHEDULER_HISTORY'))
                .' where ID>'.$max_id
                .' and ERROR=0'
                .$sql->OrderBy(array('ID','SPOOLER_ID','JOB_NAME'));
        $timer = microtime(true);
        $res = $dbscheduler->sql->query( $qry );
        $max_loop = 1000; // par lot de 1000
        $History = array();
        while ( ($line = $dbscheduler->sql->get_next($res)) and ($max_loop>0) ) {
            $id = $line['SPOOLER_ID'].'/'.$line['JOB_NAME'];
            // si le job n'est pas en cours, on supprime
            if (!isset($JobID[$id])) continue;
            $end_time = $line['END_TIME'];
            if ($end_time=='') continue;
            $History[$id] = $line['ID'];
            $start_time = $line['START_TIME'];
            $duration = strtotime($line['END_TIME'])-strtotime($line['START_TIME']);
            if (isset($NewRuns[$id])) {
                $NewRuns[$id]++;
                $NewRuntimes[$id] += $duration;
            }
            else { 
                $NewRuns[$id]=1;
                $NewRuntimes[$id] = $duration;
            }
            $max_loop--;
        }
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        // On peut construire les requêtes
        $qry=0; $n=0;
        $Queries = array();
        foreach ($History as $id => $history_id) {  
            if (isset($JobID[$id])) {
                if (isset($StatusID[$id])) {
                    $runs = $OldRuns[$id]+$NewRuns[$id];
                    $run_time = ($OldRuntimes[$id]*$OldRuns[$id]+$NewRuntimes[$id])/$runs;
                    $diff = $OldRuntimes[$id]-($NewRuntimes[$id]/$NewRuns[$id]);
                    $update = 'HISTORY_ID='.$history_id.',RUN_TIME='.$run_time.',RUNS='.$runs;
                    $crc = hash('crc32',$update);
                    if ($JobCRC[$id] != $crc ) {
                        $qry = 'update FOCUS_JOB_RUNTIMES set '.$update.',DIFF='.$diff.',UPDATED='.$timestamp.',CRC="'.$crc.'" where job_id='.$JobID[$id];
                        array_push($Queries, $qry);
                    }
                }
                else {
                    $runs = $NewRuns[$id];
                    $run_time = $NewRuntimes[$id]/$runs;
                    $update = 'HISTORY_ID='.$history_id.',RUN_TIME='.$run_time.',RUNS='.$runs;
                    $crc = hash('crc32',$update);
                    $qry = 'insert into FOCUS_JOB_RUNTIMES (JOB_ID,HISTORY_ID,RUN_TIME,RUNS,DIFF,UPDATED,CRC)'
                            . ' values ( '.$JobID[$id].','.$history_id.','.$run_time.','.$runs.',0,'.$timestamp.',"'.$crc.'" )';
                    array_push($Queries, $qry);
                }
            }
            else {
                if (isset($StatusID[$id])) {
                    $qry = 'delete from FOCUS_JOB_RUNTIMES where id='.$StatusID[$id];
                    array_push($Queries, $qry);
                }
            }
            $n++;
        }
        if (empty($Queries)) exit();
        print_r($Queries);
        $qry = implode(';', $Queries);
        $res = $data->sql->multi_query( $qry );
        exit();
    }

    // Recuperation directe entre les 2 bases de données
    public function job_statusAction() { // Si plusieurs base de données, $db permet de retrouver la connexion
        set_time_limit(600);
        $arii = $this->container->get('arii_core.db');
        $data = $arii->Connector('data');
        $sql = $this->container->get('arii_core.sql');        
/*
ATTENTION! Il faudra gérer les référentiels multiples.
        $database = 'scheduler';
        $table = 'scheduler_history_id';
        
        // Point de synchro
        $Fields = array('NAME' => $table, 'REPOSITORY' => $database );
        $qry = $sql->Select(array('ID,VALUE'))
                .$sql->From(array('FOCUS_SYNCHRO'))
                .$sql->where($Fields);
        $res = $data->sql->query( $qry );
        $line = $data->sql->get_next($res);
        if (isset($line['VALUE'])) {
            $max_id =  $line['VALUE'];
            $synchro_id = $line['ID'];
        }
        else {
            $max_id = $synchro_id = -1;
        }
 */        
        // On fait une image des jobs
        $qry = $sql->Select(array(  'fj.ID as JOB_ID','fj.PATH as JOB_NAME',
                                    'fjs.ID','fjs.START_TIME','fjs.END_TIME','fjs.CRC','fjs.HISTORY_ID',                                    
                                    'fs.NAME as SPOOLER_ID'))
                .$sql->From(array('FOCUS_JOBS fj'))
                .$sql->LeftJoin('FOCUS_JOB_STATUS fjs',array('fj.ID','fjs.JOB_ID'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fj.spooler_id','fs.id'));
 
        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $StatusID = array();
        $JobCRC = array();
        $c_job=0;
        $max_id = 0;
        while ( $line = $data->sql->get_next($res) ) {
                $spooler = $line['SPOOLER_ID'];
                $job_name = $line['JOB_NAME'];
                $id = $spooler.$job_name;

                $SpoolerID[$spooler] = $line['SPOOLER_ID'];
                $JobID[$id] = $line['JOB_ID']; 
                $StatusID[$id] = $line['ID'];
                $JobCRC[$id] = $line['CRC'];
                
                //  on cherche le max_id 
                if ($line['HISTORY_ID']>$max_id)
                    $max_id = $line['HISTORY_ID'];
                $c_job++;
        }
        
        print "<pre>";
        print "<h1>JobID</h1>";
        
        $this->PrintMessage("Jobs: $c_job");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $this->PrintMessage("History ID: $max_id");        
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $timestamp = time();

        // On recupere les temps de la base du scheduler
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $dbscheduler = $dhtmlx->Connector('data');
        $qry = $sql->Select(array('ID','SPOOLER_ID','JOB_NAME','START_TIME','END_TIME','EXIT_CODE','ERROR','ERROR_TEXT','PID','CAUSE'))
                .$sql->From(array('SCHEDULER_HISTORY'))
// inutile d'aller en dessous de ce qui a été traité
                .' where ID>'.$max_id
                .$sql->OrderBy(array('ID desc','SPOOLER_ID','JOB_NAME'));

        $timer = microtime(true);
        $res = $dbscheduler->sql->query( $qry );
        $max_loop = 10000; // par lot de 10000
        $History = array();        
        while ( ($line = $dbscheduler->sql->get_next($res)) and ($max_loop>0) ) {
            $id = $line['SPOOLER_ID'].'/'.$line['JOB_NAME'];
            // si le job est deja traite on passe au suivant
            if (isset($History[$id])) {
                continue;
            }
            // Le job n'est pas référencé on oublie
            if (!isset($JobID[$id])) {
                continue;
            }
            $History[$id] = $line['ID'];
            $Info[$id] = $line;
            $max_loop--;
        }
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        
        // On peut construire les requêtes
        $Queries = array();
        // On conserve le max_id
/*
        if ($synchro_id>=0)
            array_push($Queries,'update FOCUS_SYNCHRO set VALUE='.$max_id.' where ID='.$synchro_id);
        else 
            array_push($Queries,'insert into FOCUS_SYNCHRO (REPOSITORY,NAME,VALUE) values ("'.$database.'","'.$table.'",'.$max_id.')' );
*/
        $qry=0; $n=0;
        foreach ($History as $id => $history_id) {
            $update = 'HISTORY_ID='.$history_id.',START_TIME="'.$Info[$id]['START_TIME'].'",END_TIME="'.$Info[$id]['END_TIME'].'",EXIT_CODE='.$Info[$id]['EXIT_CODE'].',ERROR='.$Info[$id]['ERROR'].',ERROR_TEXT="'.$Info[$id]['ERROR_TEXT'].'",PID="'.$Info[$id]['PID'].'",CAUSE="'.$Info[$id]['CAUSE'].'"';
            $crc = hash('crc32',$update);
            if (isset($StatusID[$id])) {
                if (isset($JobCRC[$id]) and ($JobCRC[$id]!= $crc )) {
                    $qry = 'update FOCUS_JOB_STATUS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$StatusID[$id];
                    array_push($Queries, $qry);
                }
            }
            else {
                $qry = 'insert into FOCUS_JOB_STATUS (JOB_ID,HISTORY_ID,START_TIME,END_TIME,EXIT_CODE,ERROR,ERROR_TEXT,PID,CAUSE,UPDATED,CRC)'
                        . ' values ( '.$JobID[$id].','.$history_id.',"'.$Info[$id]['START_TIME'].'","'.$Info[$id]['END_TIME'].'","'.$Info[$id]['EXIT_CODE'].'","'.$Info[$id]['ERROR'].'","'.$Info[$id]['ERROR_TEXT'].'","'.$Info[$id]['PID'].'","'.$Info[$id]['CAUSE'].'",'.$timestamp.',"'.$crc.'" )';
                array_push($Queries, $qry);
            }
            $n++;
        }
        if (empty($Queries)) exit();
        print_r($Queries);
        if (true) {
            $qry = implode(';', $Queries);
            $res = $data->sql->multi_query( $qry );
        }
        else {
            foreach ($Queries as $q) {
                $res = $data->sql->query( $q );
            }
        }
        exit();
    }

    public function order_runtimesAction($db) { // Si plusieurs base de données, $db permet de retrouver la connexion
        set_time_limit(600);
        $arii = $this->container->get('arii_core.db');
        $data = $arii->Connector('data');

        // On fait une image des ordres
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('fo.ID as ORDER_ID','fo.PATH as ORDER_NAME',
                        'fs.ID as SPOOLER_ID','fs.NAME as SPOOLER'))
                .$sql->From(array('FOCUS_ORDERS fo'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fo.spooler_id','fs.id'));
        
        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $c_order=0;
        while ( $line = $data->sql->get_next($res) ) {
                $spooler = $line['SPOOLER'];
                $order = $line['ORDER_NAME'];
                $OrderID[$spooler.$order] = $line['ORDER_ID']; 
                $SpoolerID[$spooler] = $line['SPOOLER_ID']; 
                $c_order++;
        }
        $this->PrintMessage("Orders: $c_order");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));

        // On fait une image de la table
        $qry = $sql->Select(array('fr.ID','fr.ORDER_ID','fr.RUN_TIME','fr.RUNS','fr.CRC','fr.HISTORY_ID',
                                    'fo.PATH as ORDER_NAME',
                                    'fs.NAME as SPOOLER_ID'))
                .$sql->From(array('FOCUS_ORDER_RUNTIMES fr'))
                .$sql->LeftJoin('FOCUS_ORDERS fo',array('fr.ORDER_ID','fo.ID'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fo.SPOOLER_ID','fs.ID'));
                
        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $c_order=0;
        $StatusID = array();
        $OrderCRC = array();
        $max_id = 0; // Dernier ID traite, inutile d'aller plus loin la fois d'après
        while ( $line = $data->sql->get_next($res) ) {
                $spooler_id = $line['SPOOLER_ID'];
                $order_id = $line['ORDER_ID'];
                $order_name = $line['ORDER_NAME'];
                $OrderCRC[$spooler_id.$order_name] = $line['CRC'];
                $StatusID[$spooler_id.$order_name] = $line['ID'];
                $OldRuntimes[$spooler_id.$order_name] = $line['RUN_TIME'];
                $OldRuns[$spooler_id.$order_name] = $line['RUNS'];
                if ($line['HISTORY_ID']>$max_id)
                    $max_id = $line['HISTORY_ID'];
                $c_order++;
        }
        $this->PrintMessage("History ID: $max_id");        
        $this->PrintMessage("Status: $c_order");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $timestamp = time();

        // On recupere les temps de la base du scheduler
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $dbscheduler = $dhtmlx->Connector('data');
        $qry = $sql->Select(array('HISTORY_ID as ID','SPOOLER_ID','JOB_CHAIN','ORDER_ID','START_TIME','END_TIME'))
                .$sql->From(array('SCHEDULER_ORDER_HISTORY'))
                .' where HISTORY_ID>'.$max_id
                .$sql->OrderBy(array('HISTORY_ID','SPOOLER_ID','JOB_CHAIN','ORDER_ID'));
        $timer = microtime(true);
        $res = $dbscheduler->sql->query( $qry );
        $max_loop = 1000; // par lot de 1000
        $History = array();
        while ( ($line = $dbscheduler->sql->get_next($res)) and ($max_loop>0) ) {
            $id = $line['SPOOLER_ID'].'/'.$line['JOB_CHAIN'].','.$line['ORDER_ID'];
            // si le job n'est pas en cours, on supprime
            if (!isset($OrderID[$id])) continue;
            $end_time = $line['END_TIME'];
            if ($end_time=='') continue;
            $History[$id] = $line['ID'];
            $start_time = $line['START_TIME'];
            $duration = strtotime($line['END_TIME'])-strtotime($line['START_TIME']);
            if (isset($NewRuns[$id])) {
                $NewRuns[$id]++;
                $NewRuntimes[$id] += $duration;
            }
            else { 
                $NewRuns[$id]=1;
                $NewRuntimes[$id] = $duration;
            }
            $max_loop--;
        }
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        // On peut construire les requêtes
        $qry=0; $n=0;
        $Queries = array();
        foreach ($History as $id => $history_id) {  
            if (isset($OrderID[$id])) {
                if (isset($StatusID[$id])) {
                    $runs = $OldRuns[$id]+$NewRuns[$id];
                    $run_time = ($OldRuntimes[$id]*$OldRuns[$id]+$NewRuntimes[$id])/$runs;
                    $diff = $OldRuntimes[$id]-($NewRuntimes[$id]/$NewRuns[$id]);
                    $update = 'HISTORY_ID='.$history_id.',RUN_TIME='.$run_time.',RUNS='.$runs;
                    $crc = hash('crc32',$update);
                    if ($OrderCRC[$id] != $crc ) {
                        $qry = 'update FOCUS_ORDER_RUNTIMES set '.$update.',DIFF='.$diff.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$StatusID[$id];
                        array_push($Queries, $qry);
                    }
                }
                else {
                    $runs = $NewRuns[$id];
                    $run_time = $NewRuntimes[$id]/$runs;
                    $update = 'HISTORY_ID='.$history_id.',RUN_TIME='.$run_time.',RUNS='.$runs;
                    $crc = hash('crc32',$update);
                    $qry = 'insert into FOCUS_ORDER_RUNTIMES (ORDER_ID,HISTORY_ID,RUN_TIME,RUNS,DIFF,UPDATED,CRC)'
                            . ' values ( '.$OrderID[$id].','.$history_id.','.$run_time.','.$runs.',0,'.$timestamp.',"'.$crc.'" )';
                    array_push($Queries, $qry);
                }
            }
            else {
                if (isset($StatusID[$id])) {
                    $qry = 'delete from FOCUS_ORDER_RUNTIMES where id='.$StatusID[$id];
                    array_push($Queries, $qry);
                }
            }
            $n++;
        }
        if (empty($Queries)) exit();
        print_r($Queries);
        $qry = implode(';', $Queries);
        $res = $data->sql->multi_query( $qry );
        exit();
    }

    // Recuperation directe entre les 2 bases de données
    public function order_statusAction() { // Si plusieurs base de données, $db permet de retrouver la connexion
        set_time_limit(600);
        $arii = $this->container->get('arii_core.db');
        $data = $arii->Connector('data');
        $sql = $this->container->get('arii_core.sql');        
        
        // On fait une image des orders
        $qry = $sql->Select(array(  'fo.ID as ORDER_ID','fo.PATH as ORDER_NAME',
                                    'fos.ID','fos.START_TIME','fos.END_TIME','fos.CRC','fos.HISTORY_ID',                                    
                                    'fs.NAME as SPOOLER_ID'))
                .$sql->From(array('FOCUS_ORDERS fo'))
                .$sql->LeftJoin('FOCUS_ORDER_STATUS fos',array('fo.ID','fos.ORDER_ID'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fo.spooler_id','fs.id'));

        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $StatusID = array();
        $OrderCRC = array();
        $c_order=0;
        $max_id = 0;
        while ( $line = $data->sql->get_next($res) ) {
                $spooler = $line['SPOOLER_ID'];
                $order_name = $line['ORDER_NAME'];
                $id = $spooler.$order_name;

                $SpoolerID[$spooler] = $line['SPOOLER_ID'];
                $OrderID[$id] = $line['ORDER_ID']; 
                $StatusID[$id] = $line['ID'];
                $OrderCRC[$id] = $line['CRC'];
                
                //  on cherche le max_id 
                if ($line['HISTORY_ID']>$max_id)
                    $max_id = $line['HISTORY_ID'];
                $c_order++;
        }
        
        print "<pre>";
        print "<h1>OrderID</h1>";
        
        $this->PrintMessage("Orders: $c_order");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $this->PrintMessage("History ID: $max_id");        
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $timestamp = time();

        // On recupere les temps de la base du scheduler
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $dbscheduler = $dhtmlx->Connector('data');
        $qry = $sql->Select(array('HISTORY_ID','SPOOLER_ID','JOB_CHAIN','ORDER_ID','START_TIME','END_TIME','STATE','STATE_TEXT'))
                .$sql->From(array('SCHEDULER_ORDER_HISTORY'))
// inutile d'aller en dessous de ce qui a été traité
                .' where HISTORY_ID>'.$max_id
                .$sql->OrderBy(array('HISTORY_ID desc','SPOOLER_ID','JOB_CHAIN','ORDER_ID'));

        $timer = microtime(true);
        $res = $dbscheduler->sql->query( $qry );
        $max_loop = 10000; // par lot de 10000
        $History = array();        
        while ( ($line = $dbscheduler->sql->get_next($res)) and ($max_loop>0) ) {
            $id = $line['SPOOLER_ID'].'/'.$line['JOB_CHAIN'].','.$line['ORDER_ID'];
            // si le job est deja traite on passe au suivant
            if (isset($History[$id])) {
                continue;
            }
            // Le job n'est pas référencé on oublie
            if (!isset($OrderID[$id])) {
                continue;
            }
            $History[$id] = $line['HISTORY_ID'];
            $Info[$id] = $line;
            $max_loop--;
        }
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));

        // On peut construire les requêtes
        $Queries = array();

        $qry=0; $n=0;
        foreach ($History as $id => $history_id) {
            $update = 'HISTORY_ID='.$history_id.',START_TIME="'.$Info[$id]['START_TIME'].'",END_TIME="'.$Info[$id]['END_TIME'].'",STATE="'.$Info[$id]['STATE'].'",STATE_TEXT="'.$Info[$id]['STATE_TEXT'].'"';
            $crc = hash('crc32',$update);
            if (isset($StatusID[$id])) {
                if (isset($OrderCRC[$id]) and ($OrderCRC[$id]!= $crc )) {
                    $qry = 'update FOCUS_ORDER_STATUS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$StatusID[$id];
                    array_push($Queries, $qry);
                }
            }
            else {
                $qry = 'insert into FOCUS_ORDER_STATUS (ORDER_ID,HISTORY_ID,START_TIME,END_TIME,STATE,STATE_TEXT,UPDATED,CRC)'
                        . ' values ( '.$OrderID[$id].','.$history_id.',"'.$Info[$id]['START_TIME'].'","'.$Info[$id]['END_TIME'].'","'.$Info[$id]['STATE'].'","'.$Info[$id]['STATE_TEXT'].'",'.$timestamp.',"'.$crc.'" )';
                array_push($Queries, $qry);
            }
            $n++;
        }
        if (empty($Queries)) exit();
        print_r($Queries);
        if (true) {
            $qry = implode(';', $Queries);
            $res = $data->sql->multi_query( $qry );
        }
        else {
            foreach ($Queries as $q) {
                $res = $data->sql->query( $q );
            }
        }
        exit();
    }
    
    public function node_runtimesAction($db) { // Si plusieurs base de données, $db permet de retrouver la connexion
        set_time_limit(600);
        $arii = $this->container->get('arii_core.db');
        $data = $arii->Connector('data');

        // On fait une image des nodes
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('fn.ID NODE_ID','fn.STATE',
                        'fo.NAME as ORDER_NAME','fo.ID as ORDER_ID','fo.PATH as ORDER_PATH',                        
                        'fc.PATH as CHAIN_PATH',
                        'fs.ID as SPOOLER_ID','fs.NAME as SPOOLER'))
                .$sql->From(array('FOCUS_ORDERS fo'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS fc',array('fo.job_chain_id','fc.id'))
                .$sql->LeftJoin('FOCUS_JOB_CHAIN_NODES fn',array('fn.job_chain_id','fc.id'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fn.spooler_id','fs.id'));
        
        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $c_node=0;
        while ( $line = $data->sql->get_next($res) ) {
                $spooler = $line['SPOOLER'];
                $node = str_replace(',','/',$line['ORDER_PATH']).'/'.$line['STATE'];
                $NodeID[$spooler.$node] = $line['NODE_ID']; 
                $OrderID[$spooler.$node] = $line['ORDER_ID']; 
                $SpoolerID[$spooler] = $line['SPOOLER_ID']; 
                $c_node++;
        }
        $this->PrintMessage("Nodes: $c_node");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));

        // On fait une image de la table
        $qry = $sql->Select(array('fr.ID','fr.HISTORY_ID','fr.TASK_ID','fr.ORDER_ID','fr.JOB_CHAIN_NODE_ID','fr.RUN_TIME','fr.RUNS','fr.CRC',
                                    'fo.PATH','fo.NAME as ORDER_NAME',
                                    'fc.NAME as CHAIN_NAME',
                                    'fn.STATE',
                                    'fs.NAME as SPOOLER_ID'))
                .$sql->From(array('FOCUS_ORDER_STEP_RUNTIMES fr'))
                .$sql->LeftJoin('FOCUS_JOB_CHAIN_NODES fn',array('fr.JOB_CHAIN_NODE_ID','fn.ID'))
                .$sql->LeftJoin('FOCUS_ORDERS fo',array('fr.ORDER_ID','fo.ID'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS fc',array('fo.JOB_CHAIN_ID','fc.ID'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fn.SPOOLER_ID','fs.ID'));
       
        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $c_node=0;
        $StatusID = $OrderCRC = array();
        $max_id = 0; // Dernier ID traite, inutile d'aller plus loin la fois d'après
        while ( $line = $data->sql->get_next($res) ) {
                $spooler_id = $line['SPOOLER_ID'];
                if ($spooler_id =='') continue; // pb purge
                $node_name = str_replace(',','/',$line['PATH']).'/'.$line['STATE'];
                $NodeCRC[$spooler_id.$node_name] = $line['CRC'];
                $StatusID[$spooler_id.$node_name] = $line['ID'];
                $OldRuntimes[$spooler_id.$node_name] = $line['RUN_TIME'];
                $OldRuns[$spooler_id.$node_name] = $line['RUNS'];
                if ($line['TASK_ID']>$max_id)
                    $max_id = $line['TASK_ID'];
                $c_node++;
        }
        $this->PrintMessage("Task ID: $max_id");     
        $this->PrintMessage("Status: $c_node");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $timestamp = time();

        // On recupere les temps de la base du scheduler
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $dbscheduler = $dhtmlx->Connector('data');
        $qry = $sql->Select(array(  'osh.TASK_ID as ID','osh.HISTORY_ID','osh.STATE','osh.START_TIME','osh.END_TIME',
                                     'oh.SPOOLER_ID','oh.JOB_CHAIN','oh.ORDER_ID' ))
                .$sql->From(array('SCHEDULER_ORDER_STEP_HISTORY osh'))
                .$sql->LeftJoin('SCHEDULER_ORDER_HISTORY oh',array('osh.HISTORY_ID','oh.HISTORY_ID'))
                .' where osh.TASK_ID>'.$max_id
                .$sql->OrderBy(array('osh.TASK_ID','osh.HISTORY_ID','oh.SPOOLER_ID','oh.JOB_CHAIN','oh.ORDER_ID','osh.STATE'));
        $timer = microtime(true);
        $res = $dbscheduler->sql->query( $qry );
        $max_loop = 1000; // par lot de 1000
        $Task = array();     
        $HistoryID = array();   
        $NewRuns = array();
        while ( ($line = $dbscheduler->sql->get_next($res)) and ($max_loop>0) ) {
            $spooler_id = $line['SPOOLER_ID'];
            $id = $spooler_id.'/'.$line['JOB_CHAIN'].'/'.$line['ORDER_ID'].'/'.$line['STATE'];
            // si le job n'est pas en cours, on supprime
            if (!isset($NodeID[$id])) continue;
            $end_time = $line['END_TIME'];
            if ($end_time=='') continue;
            $Task[$id] = $line['ID'];
            $HistoryID[$id] = $line['HISTORY_ID']; 
            $duration = strtotime($line['END_TIME'])-strtotime($line['START_TIME']);
            if (isset($NewRuns[$id])) {
                $NewRuns[$id]++;
                $NewRuntimes[$id] += $duration;
            }
            else { 
                $NewRuns[$id]=1;
                $NewRuntimes[$id] = $duration;
            }
            $max_loop--;
        }
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        // On peut construire les requêtes
        $qry=0; $n=0;
        $Queries = array();
        foreach ($Task as $id => $task_id) {  
            if (isset($NodeID[$id])) {
                if (isset($StatusID[$id])) {
                    $this->PrintMessage( "STATUS:".$StatusID[$id] );
                    $runs = $OldRuns[$id]+$NewRuns[$id];
                    $run_time = ($OldRuntimes[$id]*$OldRuns[$id]+$NewRuntimes[$id])/$runs;
                    $diff = $OldRuntimes[$id]-($NewRuntimes[$id]/$NewRuns[$id]);
                    $update = 'TASK_ID='.$task_id.',HISTORY_ID='.$HistoryID[$id].',ORDER_ID='.$OrderID[$id].',JOB_CHAIN_NODE_ID='.$NodeID[$id].',RUN_TIME='.$run_time.',RUNS='.$runs;
                    $crc = hash('crc32',$update);
                    if ($NodeCRC[$id] != $crc ) {
                        $qry = 'update FOCUS_ORDER_STEP_RUNTIMES set '.$update.',DIFF='.$diff.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$StatusID[$id];
                        array_push($Queries, $qry);
                    }
                }
                else {
                    $runs = $NewRuns[$id];
                    $run_time = $NewRuntimes[$id]/$runs;
                    print $HistoryID[$id];
                    $update = 'TASK_ID='.$task_id.',HISTORY_ID='.$HistoryID[$id].',ORDER_ID='.$OrderID[$id].',JOB_CHAIN_NODE_ID='.$NodeID[$id].',RUN_TIME='.$run_time.',RUNS='.$runs;
                    $crc = hash('crc32',$update);
                    $qry = 'insert into FOCUS_ORDER_STEP_RUNTIMES (JOB_CHAIN_NODE_ID,ORDER_ID,TASK_ID,HISTORY_ID,RUN_TIME,RUNS,DIFF,UPDATED,CRC)'
                            . ' values ( '.$NodeID[$id].','.$OrderID[$id].','.$task_id.','.$HistoryID[$id].','.$run_time.','.$runs.',0,'.$timestamp.',"'.$crc.'" )';
                    array_push($Queries, $qry);
                }
            }
            else {
                if (isset($StatusID[$id])) {
                    $qry = 'delete from FOCUS_ORDER_STEP_RUNTIMES where id='.$StatusID[$id];
                    array_push($Queries, $qry);
                }
            }
            $n++;
        }
        if (empty($Queries)) exit();
        print_r($Queries);
        $qry = implode(';', $Queries);
        $res = $data->sql->multi_query( $qry );
        exit();
    }

    public function node_statusAction() { // Si plusieurs base de données, $db permet de retrouver la connexion
        set_time_limit(600);
        $arii = $this->container->get('arii_core.db');
        $data = $arii->Connector('data');
        $sql = $this->container->get('arii_core.sql');        
        
        // On fait une image des nodes
        $qry = $sql->Select(array(  'fo.ID as ORDER_ID','fo.PATH as ORDER_NAME',
                                    'fos.ID as STEP_ID','fos.STEP', 'fos.ID','fos.START_TIME','fos.END_TIME','fos.CRC','fos.TASK_ID','fos.HISTORY_ID',                                    
                                    'fs.NAME as SPOOLER_ID'))
                .$sql->From(array('FOCUS_ORDERS fo'))
                .$sql->LeftJoin('FOCUS_ORDER_STEP_STATUS fos',array('fo.id','fos.order_id'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fo.spooler_id','fs.id'));

        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $StatusID = $OrderID = array();
        $NodeCRC = array();
        $c_node=0;
        $max_id = 0;
        while ( $line = $data->sql->get_next($res) ) {
                $spooler = $line['SPOOLER_ID'];
                $order_name = $line['ORDER_NAME'];
                $step = $line['STEP'];
                $id = $spooler.$order_name;
                $OrderID[$id] = $line['ORDER_ID']; 
                $SpoolerID[$spooler] = $line['SPOOLER_ID'];
                
                //  on cherche le max_id 
                if ($line['TASK_ID']>$max_id)
                    $max_id = $line['TASK_ID'];
                
                if ($step == '') continue; 
                $id .= "/$step";
                $StatusID[$id] = $line['STEP_ID'];
                $NodeCRC[$id] = $line['CRC'];
                
                $c_node++;
        }
//        print_r($OrderID);
        print_r($StatusID);
        print "<pre>";
        print "<h1>NodeId</h1>";
 
        $this->PrintMessage("Nodes: $c_node");
        $this->PrintMessage("History ID: $max_id");        
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $timestamp = time();

        // On recupere les temps de la base du scheduler
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $dbscheduler = $dhtmlx->Connector('data');
        $qry = $sql->Select(array('sosh.TASK_ID','sosh.HISTORY_ID','sosh.STEP','sosh.STATE','sosh.START_TIME','sosh.END_TIME','sosh.ERROR','sosh.ERROR_CODE','sosh.ERROR_TEXT',
                                   'soh.ORDER_ID as ORDER_NAME','soh.SPOOLER_ID','soh.JOB_CHAIN'))
                .$sql->From(array('SCHEDULER_ORDER_STEP_HISTORY sosh'))
                .$sql->LeftJoin('SCHEDULER_ORDER_HISTORY soh',array('sosh.HISTORY_ID','soh.HISTORY_ID'))
// inutile d'aller en dessous de ce qui a été traité
                .' where TASK_ID>'.$max_id
                .$sql->OrderBy(array('TASK_ID desc','SPOOLER_ID','JOB_CHAIN','ORDER_ID'));

        $timer = microtime(true);
        $res = $dbscheduler->sql->query( $qry );
        $max_loop = 10000; // par lot de 10000
        $Task = array();        
        while ( ($line = $dbscheduler->sql->get_next($res)) and ($max_loop>0) ) {
            $id = $line['SPOOLER_ID'].'/'.$line['JOB_CHAIN'].','.$line['ORDER_NAME'];
            // L'ordre n'est pas référencé on oublie
            if (!isset($OrderID[$id])) {
                continue;
            }
            $line['ORDER_ID'] = $OrderID[$id];
            
            $id .= '/'.$line['STEP'];
            // si le job est deja traite on passe au suivant
            if (isset($Task[$id])) {
                continue;
            }
            $Task[$id] = $line['TASK_ID'];
            $Info[$id] = $line;
            $max_loop--;
        }
        print "TASK";
print_r($Task);

        $this->PrintMessage("Timer: ".(microtime(true)-$timer));

        // On peut construire les requêtes
        $Queries = array();

        $qry=0; $n=0;
        foreach ($Task as $id => $task_id) {
            $update = 'TASK_ID='.$task_id.',HISTORY_ID='.$Info[$id]['HISTORY_ID'].',ORDER_ID='.$Info[$id]['ORDER_ID'].',STEP='.$Info[$id]['STEP'].',START_TIME="'.$Info[$id]['START_TIME'].'",END_TIME="'.$Info[$id]['END_TIME'].'",STATE="'.$Info[$id]['STATE'].'",ERROR='.$Info[$id]['ERROR'].',ERROR_CODE="'.$Info[$id]['ERROR_CODE'].'",ERROR_TEXT="'.$Info[$id]['ERROR_TEXT'].'"';
            $crc = hash('crc32',$update);
            if (isset($StatusID[$id])) {
                if (isset($NodeCRC[$id]) and ($NodeCRC[$id]!= $crc )) {
                    $qry = 'update FOCUS_ORDER_STEP_STATUS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$StatusID[$id];
                    array_push($Queries, $qry);
                }
            }
            else {
                $qry = 'insert into FOCUS_ORDER_STEP_STATUS (TASK_ID,HISTORY_ID,ORDER_ID,STEP,START_TIME,END_TIME,STATE,ERROR,ERROR_CODE,ERROR_TEXT,UPDATED,CRC)'
                        . ' values ( '.$task_id.','.$Info[$id]['HISTORY_ID'].','.$Info[$id]['ORDER_ID'].','.$Info[$id]['STEP'].',"'.$Info[$id]['START_TIME'].'","'.$Info[$id]['END_TIME'].'","'.$Info[$id]['STATE'].'","'.$Info[$id]['ERROR'].'","'.$Info[$id]['ERROR_CODE'].'","'.$Info[$id]['ERROR_TEXT'].'",'.$timestamp.',"'.$crc.'" )';
                array_push($Queries, $qry);
            }
            $n++;
        }
        if (empty($Queries)) exit();
        print_r($Queries);
        if (true) {
            $qry = implode(';', $Queries);
            $res = $data->sql->multi_query( $qry );
        }
        else {
            foreach ($Queries as $q) {
                $res = $data->sql->query( $q );
            }
        }
        exit();
    }
    
    function PrintMessage($message,$level=0) {
	if ($this->mode == 'BATCH') {
		print str_repeat("\t",$level);
		print "$message\n";
	}
	elseif ($this->mode == 'WEB') {
		print str_repeat("&nbsp;",$level*5);
		print "$message<br/>";
	}
        return;
        print "<pre>";
        print_r( $Result);
        print "</pre>";

    }

    private function CorrectDatetime($date) {
        return str_replace(array('T','Z'),array(' ',' '),$date);
    }

}