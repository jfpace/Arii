<?php

namespace Arii\FocusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/* Mauvais controleur, TROP CONSOMMATEUR */
class AggregController extends Controller
{
    protected $mode;

    public function purgeAction($spooler_id) {
        $this->mode="WEB";
        $this->PrintMessage("PURGE");
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        foreach (array('SCHEDULER_HISTORY') as $table) {
             $this->PrintMessage($table);
             $qry = 'delete from FOCUS_'.$table.' where SPOOLER_ID='.$spooler_id; 
             $this->PrintMessage($qry);
             $res = $data->sql->query( $qry );
             $this->PrintMessage($res);
       }
       // on efface le spooler ?
       $qry = 'delete from FOCUS_SPOOLERS where ID='.$spooler_id; 
        $res = $data->sql->query( $qry );
       exit();
    }
    
    public function postAction() {
        print "POST RECEIVED !!!";
        $this->mode="BATCH";
        set_time_limit ( 300 );
        $f = fopen('php://input', 'r');
        $data = '';
        while(!feof($f)) {
            $data .= fread($f,1024);
        }
        fclose($f);
        print "Size: ".strlen($data);
        return $this->cache($data);
    }

    public function webAction($spooler='localhost',$port='4444',$what='job_chain_orders,job_orders,jobs,job_chains,remote_schedulers') {
        set_time_limit ( 900 );
        $f= @fopen("http://$spooler:$port/%3Cshow_state%20what=%22$what%22/%3E","r");
        if (!$f) {
            print "PB!";
            exit();
        }

        $data = '';
        while(!feof($f)) {
            $data .= fread($f,10240);
        }
        fclose($f);
        
        return $this->cache($data,'web');
    }

    public function historyAction($mode='debug') {
        $this->PrintMessage(time());
        $this->mode="WEB";
        set_time_limit ( 900 );
        $timer = microtime(true);
        
        // On fait une image des jobs
        $db = $this->container->get('arii_core.db');
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        // repository en cours
        $repository_id = $dhtmlx->Id();
        
        $dbdata = $db->Connector('data');
        $qry = 'select ID,SPOOLER,JOB_NAME,START_TIME,END_TIME from FOCUS_HISTORY where REPOSITORY_ID='.$repository_id; 
        $res = $dbdata->sql->query( $qry );
        while ( $line = $dbdata->sql->get_next($res) ) {
                $id  =  $line['SPOOLER'].'/'.$line['JOB_NAME'].'/'.$line['START_TIME'];
                $Job[$id] = $line['SPOOLER'].'/'.$line['JOB_NAME'];
                $JobEnd[$id] = $line['END_TIME'];
                $iid = $line['ID'];
                $JobDel[$id] = $iid; 
        }
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));

        $data = $dhtmlx->Connector('data');
        $sql = $this->container->get('arii_core.sql');
        $sub = $sql->Select(array('max(ID) as MAX','avg(END_TIME-START_TIME) as AVG_RUNTIME','count(ID) as NB','max(END_TIME-START_TIME) as MAX_RUNTIME','min(END_TIME-START_TIME) as MIN_RUNTIME'))
                .$sql->From(array('SCHEDULER_HISTORY'))
//                .$sql->Where(array('CAUSE'))
                .$sql->GroupBy(array('SPOOLER_ID','JOB_NAME'));
        $res = $data->sql->query( $sub );
        $Ids = array();
        while ($line = $data->sql->get_next($res)) {
            $id = $line['MAX'];
            $Avg[$id] = $line['AVG_RUNTIME'];
            $Min[$id] = $line['MIN_RUNTIME'];
            $Max[$id] = $line['MAX_RUNTIME'];
            $Nb[$id] = $line['NB'];
            array_push( $Ids, $id);
        }
        $sub = implode(',',$Ids);
        
        $qry = $sql->Select(array('ID','SPOOLER_ID','JOB_NAME','START_TIME','END_TIME','CAUSE','STEPS','EXIT_CODE','ERROR','ERROR_TEXT'))
                .$sql->From(array('SCHEDULER_HISTORY'))
                ." where ID in (".$sub.")"
                .$sql->OrderBy(array('ID'));
        
        $Queries = array();
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            // ID
            $id = $line['SPOOLER_ID'].'/'.$line['JOB_CHAIN'].'/'.$line['ORDER_ID'].'/'.$line['START_TIME'];
            $iid = $line['ID'];
            if (isset($Job[$id])) {
                $JobDel[$id] = -1;
                if ($JobEnd[$id]=='') {
                   // set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$LockUseID[$lockjob];
                    $qry = 'update FOCUS_ORDER_HISTORY set (STATE,STATE_TEXT,END_TIME,AVG_RUNTIME,MIN_RUNTIME,MAX_RUNTIME,RUN_COUNT)'
                            . ' values ( "'.$line['STATE'].'", "'.$line['STATE_TEXT'].'", '.$Avg[$iid].', '.$Min[$iid].', '.$Max[$iid].', '.$Nb[$iid].' )';
                    array_push($Queries, $qry);                                                          
                }
            }
            else {
                // insert 
                $qry = 'insert into FOCUS_ORDER_HISTORY (REPOSITORY_ID,ID_HISTORY,SPOOLER,JOB_CHAIN,ORDER_ID,START_TIME,AVG_RUNTIME,MIN_RUNTIME,MAX_RUNTIME,RUN_COUNT)'
                        . ' values ( "'.$repository_id.'","'.$iid.'","'.$line['SPOOLER_ID'].'", "'.$line['JOB_CHAIN'].'", "'.$line['ORDER_ID'].'", "'.$line['START_TIME'].'", "'.$line['END_TIME'].'", '.$Avg[$iid].', '.$Min[$iid].', '.$Max[$iid].', '.$Nb[$iid].' )';
                array_push($Queries, $qry);                                
            }
        }
        
        if (isset($JobDel)) { array_push($Queries, $this->QueryDel('SCHEDULER_ORDER_HISTORY', $JobDel)); }

        if ($mode!='debug') { 
            $qry = implode(';', $Queries);
            $res = $dbdata->sql->multi_query( $qry );
        }
        else {
        /*        foreach ($Queries as $q) {
                    $res = $data->sql->query( $q );
                }
        */

                $qry = '';
                $nb = 0;
                foreach ($Queries as $q) {
                    $qry .= "$q;\n";
                    $nb++;
                    if ($nb >5000) {
                        $res = $dbdata->sql->multi_query( $qry );
                        print "(($nb $res))";
                        $qry = ''; $nb=0;
                    }
                }
                if ($qry != '') {
                    $res = $dbdata->sql->multi_query( $qry );
                    print_r($res);
                    print "(($nb $res))";
                }
        }        
        // 
        $this->PrintMessage(time());
        exit();
    }

    public function orderHistoryAction($mode='debug') {
        $this->PrintMessage(time());
        $this->mode="WEB";
        set_time_limit ( 900 );
        $timer = microtime(true);
        
        // On fait une image des jobs
        $db = $this->container->get('arii_core.db');
        $sql = $this->container->get('arii_core.sql');
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        // repository en cours
        $repository_id = $dhtmlx->Id();
        if ($repository_id<0)
                $rep_id='(null)';
            else
                $rep_id=$repository_id;
        
        $dbdata = $db->Connector('data');
        $qry = $sql->Select(array('ID','SOURCE','END_TIME'))
                .$sql->From(array('FOCUS_ORDER_HISTORY'))
                .$sql->Where(array('REPOSITORY_ID' => $rep_id));
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        if ($mode=='debug') {
            print "<hr/>$qry<br/>";
        }
        $nb = 0;
        $res = $dbdata->sql->query( $qry );
        $NewOrderId = array();
        while ( $line = $dbdata->sql->get_next($res) ) {
                $source = $line['SOURCE'];               
                $iid = $line['ID'];
                $OrderEnd[$source] = $line['END_TIME'];
                $Order[$source] = $iid;
                $OrderDel[$source] = $iid;
                array_push($NewOrderId, $line['ID']);
                $nb++;
        }
        if ($mode=='debug') {
            print "<b>$nb</b><br/>";
        }

        $data = $dhtmlx->Connector('data');
        $sql = $this->container->get('arii_core.sql');
        $sub = $sql->Select(array('max(HISTORY_ID) as MAX','avg(END_TIME-START_TIME) as AVG_RUNTIME','count(HISTORY_ID) as NB','max(END_TIME-START_TIME) as MAX_RUNTIME','min(END_TIME-START_TIME) as MIN_RUNTIME'))
                .$sql->From(array('SCHEDULER_ORDER_HISTORY'))
                .$sql->GroupBy(array('SPOOLER_ID','JOB_CHAIN','ORDER_ID'));
        $res = $data->sql->query( $sub );
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        if ($mode=='debug') {
            print "<hr/>$sub<br/>";
        }
        $nb = 0;
        $Ids = array();
        while ($line = $data->sql->get_next($res)) {
            $id = $line['MAX'];
            $Avg[$id] = $line['AVG_RUNTIME'];
            $Min[$id] = $line['MIN_RUNTIME'];
            $Max[$id] = $line['MAX_RUNTIME'];
            $Nb[$id] = $line['NB'];
            array_push( $Ids, $id);
            $nb++;
        }
        $sub = implode(',',$Ids);
        if ($mode=='debug') {
            print "<b>$nb</b><br/>";
        }
        
        $qry = $sql->Select(array('HISTORY_ID as ID','SPOOLER_ID','JOB_CHAIN','ORDER_ID','STATE','STATE_TEXT','TITLE','START_TIME','END_TIME'))
                .$sql->From(array('SCHEDULER_ORDER_HISTORY'))
                ." where HISTORY_ID in (".$sub.")"
                .$sql->OrderBy(array('HISTORY_ID'));
        if ($mode=='debug') {
            print "<hr/>$qry<br/>";
        }
        $nb = 0;
        $Queries = array();
        $res2 = $data->sql->query( $qry );        
        while ($line = $data->sql->get_next($res2)) {
            // ID
            $source = $line['ID'];
            $iid = $source;
            if ($line['END_TIME']=='') $endtime='null';
                else $endtime = '"'.$line['END_TIME'].'"';
                
            if (isset($OrderDel[$source])) {
                $OrderDel[$source] = -1;
                if ($OrderEnd[$source]=='') {
                    $qry = 'update FOCUS_ORDER_HISTORY set END_TIME="'.$line['END_TIME'].'",AVG_RUNTIME='.$Avg[$iid].',MIN_RUNTIME='.$Min[$iid].',MAX_RUNTIME='.$Max[$iid].',RUN_COUNT='.$Nb[$iid].' where ID='.$Order[$source];
                    array_push($Queries, $qry);                                                          
                }
            }
            else {
                $qry = 'insert into FOCUS_ORDER_HISTORY (REPOSITORY_ID,SOURCE,SCHEDULER,JOB_CHAIN,ORDER_ID,START_TIME,END_TIME,AVG_RUNTIME,MIN_RUNTIME,MAX_RUNTIME,RUN_COUNT)'
                        . ' values ( '.$rep_id.','.$source.', "'.$line['SPOOLER_ID'].'", "'.$line['JOB_CHAIN'].'", "'.$line['ORDER_ID'].'", "'.$line['START_TIME'].'", '.$endtime.', '.$Avg[$iid].', '.$Min[$iid].', '.$Max[$iid].', '.$Nb[$iid].' )';
                array_push($Queries, $qry);                                
            }
            $nb++;
        }
        if ($mode=='debug') {
            print "<b>$nb</b><br/>";
        }

        // =================================================
        // STEPS
        // =================================================
        // Existant
        if (empty($NewOrderId)) $NewOrderId= array(-1);
            
        $qry = $sql->Select(array('ID','SOURCE','END_TIME','ORDER_ID'))
                .$sql->From(array('FOCUS_ORDER_STEP_HISTORY'))
                ." where ORDER_ID in (".implode(',',$NewOrderId).")";
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        if ($mode=='debug') {
            print "<hr/>$qry<br/>";
        }
        $nb = 0;
        $res = $dbdata->sql->query( $qry );
        while ( $line = $dbdata->sql->get_next($res) ) {
                $source = $line['SOURCE'];               
                $iid = $line['ID'];
                $StepEnd[$source] = $line['END_TIME'];
                $StepOrder[$source] = $line['ORDER_ID'];
                $nb++;
        }
        if ($mode=='debug') {
            print "<b>$nb</b><br/>";
        }
        
        $step = $sql->Select(array('max(soh.HISTORY_ID) as MAX_HISTORY','soh.ORDER_ID','sosh.STEP','sosh.STATE',
                'max(sosh.TASK_ID) as MAX_TASK','avg(sosh.END_TIME-sosh.START_TIME) as AVG_RUNTIME','count(soh.HISTORY_ID) as NB','max(sosh.END_TIME-sosh.START_TIME) as MAX_RUNTIME','min(sosh.END_TIME-sosh.START_TIME) as MIN_RUNTIME'))
                .$sql->From(array('SCHEDULER_ORDER_HISTORY soh'))
                .$sql->LeftJoin('SCHEDULER_ORDER_STEP_HISTORY sosh',array('soh.HISTORY_ID','sosh.HISTORY_ID'))
                ." where soh.HISTORY_ID in (".$sub.")"
                .$sql->GroupBy(array('soh.SPOOLER_ID','soh.JOB_CHAIN','soh.ORDER_ID','sosh.STEP','sosh.STATE'));
        
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        if ($mode=='debug') {
            print "<hr/>$step<br/>";
        }
        $nb = 0;
        $res = $data->sql->query( $step );
        while ($line = $data->sql->get_next($res)) {
            $id = $line['MAX_TASK'];
            $Avg[$id] = $line['AVG_RUNTIME'];
            $Min[$id] = $line['MIN_RUNTIME'];
            $Max[$id] = $line['MAX_RUNTIME'];
            $Nb[$id] = $line['NB'];
            array_push( $Ids, $id);
            $nb++;
        }
        $sub = implode(',',$Ids);
        if ($mode=='debug') {
            print "<b>$nb</b><br/>";
        }

        $qry = $sql->Select(array('sosh.TASK_ID','sosh.HISTORY_ID','sosh.STEP','sosh.STATE','sosh.STATE','sosh.START_TIME','sosh.END_TIME','sosh.ERROR','sosh.ERROR_CODE','sosh.ERROR_TEXT',
                'sh.JOB_NAME','sh.CAUSE','sh.STEPS','sh.EXIT_CODE','sh.PARAMETERS','sh.ITEM_START','sh.ITEM_STOP','sh.PID'))
                .$sql->From(array('SCHEDULER_ORDER_STEP_HISTORY sosh'))
                .$sql->LeftJoin('SCHEDULER_HISTORY sh',array('sosh.TASK_ID','sh.ID'))
                ." where sosh.HISTORY_ID in (".$sub.")"
                .$sql->OrderBy(array('sh.ID'));
        $res2 = $data->sql->query( $qry );
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        if ($mode=='debug') {
            print "<hr/>$qry<br/>";
        }
        $nb = 0;
        while ($line = $data->sql->get_next($res2)) {
            // ID
            $iid = $line['TASK_ID'];
            $source = $iid;
            $history_id = $line['HISTORY_ID'];
            if (isset($Order[$history_id])) {
                $order = $Order[$history_id];
            }
            else {
                $order = 'null';
            }

            if ($line['END_TIME']=='') $endtime='null';
                else $endtime = '"'.$line['END_TIME'].'"';
                
            if (isset($StepEnd[$source])) {
                if (($StepEnd[$iid]=='') or ($StepOrder[$iid]=='')) {
                    $qry = 'update FOCUS_ORDER_STEP_HISTORY set ORDER_ID='.$order.',END_TIME="'.$line['END_TIME'].'",AVG_RUNTIME='.$Avg[$iid].',MIN_RUNTIME='.$Min[$iid].',MAX_RUNTIME='.$Max[$iid].',RUN_COUNT='.$Nb[$iid].' where ID='.$Order[$source];
                    array_push($Queries, $qry);                                                          
                }
            }
            else {
                $qry = 'insert into FOCUS_ORDER_STEP_HISTORY (ORDER_ID,SOURCE,STEP,STATE,ERROR,ERROR_CODE,ERROR_TEXT,START_TIME,END_TIME,AVG_RUNTIME,MIN_RUNTIME,MAX_RUNTIME,RUN_COUNT,JOB_NAME,CAUSE,STEPS,EXIT_CODE,ITEM_START,ITEM_STOP,PID)'
                        . ' values ( '.$order.','.$source.', "'.$line['STEP'].'", "'.$line['STATE'].'", "'.$line['ERROR'].'", "'.$line['ERROR_CODE'].'", "'.$line['ERROR_TEXT'].'", "'.$line['START_TIME'].'", '.$endtime.', '.$Avg[$iid].', '.$Min[$iid].', '.$Max[$iid].', '.$Nb[$iid].', "'.$line['JOB_NAME'].'", "'.$line['CAUSE'].'", "'.$line['STEPS'].'", "'.$line['EXIT_CODE'].'", "'.$line['ITEM_START'].'", "'.$line['ITEM_STOP'].'", "'.$line['PID'].'" )';
                array_push($Queries, $qry);                                
            }
            $nb++;
        }
        if ($mode=='debug') {
            print "<b>$nb</b><br/>";
        }

        if (isset($OrderDel)) { 
            array_push($Queries, $this->QueryDel('ORDER_STEP_HISTORY', $OrderDel, 'ORDER_ID'));
            array_push($Queries, $this->QueryDel('ORDER_HISTORY', $OrderDel)); 
        }

        if ($mode!='debug') { 
            $qry = implode(';', $Queries);
            $res = $dbdata->sql->multi_query( $qry );
        }
        else {
                print_r($Queries);
        /*        foreach ($Queries as $q) {
                    $res = $data->sql->query( $q );
                }
        */

                $qry = '';
                $nb = 0;
                foreach ($Queries as $q) {
                    $qry .= "$q;\n";
                    $nb++;
                    if ($nb >5000) {
                        $res = $dbdata->sql->multi_query( $qry );
                        print "(($nb $res))";
                        $qry = ''; $nb=0;
                    }
                }
                if ($qry != '') {
                    $res = $dbdata->sql->multi_query( $qry );
                    print_r($res);
                    print "(($nb $res))";
                }
        }        
        // 
        $this->PrintMessage(time());
        exit();
    }

    public function testAction($file="C:/xampp/Symfony/show_state.xml") {
        $data = file_get_contents($file);
        $this->mode="WEB";
        return $this->cache($data);
    }

    private function cache($data,$mode = 'debug') {   

        
        $this->PrintMessage("FIN");        
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $this->PrintMessage(time());

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
    
    private function TextProtect($str) {
        $str = str_replace('"','\"',$str);
        return $str;
    }
    
    private function QueryDel($table, $Tableau, $field='ID') {
        $Del = array();
        foreach ($Tableau as $k=>$v) {
            if ($v>=0) {
                array_push($Del,$v);
            }
        }
        if (empty($Del)) {
            array_push($Del,-1);
        }
        return 'delete from FOCUS_'.$table.' where '.$field.' in ('.implode(',',$Del).')';
    }

}