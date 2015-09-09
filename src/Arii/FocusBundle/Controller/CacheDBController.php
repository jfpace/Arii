<?php

namespace Arii\FocusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CacheDBController extends Controller
{
    protected $mode = 'WEB';
    
    private function getPost() {
        $f = fopen('php://input', 'r');
        $data = '';
        while(!feof($f)) {
            $data .= fread($f,1024);
        }
        fclose($f);
        return $data;
    }
    
    public function postAction() {
        return $this->cache($this->getPost());
    }
    
    private function cache($data){        
        $tools = $this->container->get('arii_core.tools');
        $Result = $tools->xml2array( $data , 1, 'attributes');
        
        print_r($data);
        print "ID=10";
        exit();
    }

    public function statusAction($debug=true){     
        if ($debug) print "DEBUG MODE !!!";
        $this->mode='BATCH';
        $data = $this->getPost();        
        // $data = file_get_contents('D:\arii\enterprises\SOS-Paris\scheduler\operations_gui\jobs_status.xml');
        $tools = $this->container->get('arii_core.tools');
        $Result = $tools->xml2array( $data , 1, 'attributes');

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
        $qry = $sql->Select(array('fjs.ID','fjs.JOB_ID','fjs.START_TIME','fjs.END_TIME','fjs.PID','fjs.CAUSE','fjs.CRC',
                                    'fj.PATH as JOB_NAME',
                                    'fs.NAME as SPOOLER_ID'))
                .$sql->From(array('FOCUS_JOB_STATUS fjs'))
                .$sql->LeftJoin('FOCUS_JOBS fj',array('fjs.JOB_ID','fj.ID'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fj.SPOOLER_ID','fs.ID'));
                
        $timer = microtime(true);
        $res = $data->sql->query( $qry );
        $c_job=0;
        $StatusID = array();
        $JobCRC = array();
        while ( $line = $data->sql->get_next($res) ) {
                $spooler_id = $line['SPOOLER_ID'];
                $job_id = $line['JOB_ID'];
                $job_name = $line['JOB_NAME'];
                $JobCRC[$spooler_id.$job_name] = $line['CRC'];
                print "===========================\n$spooler_id ".$line['JOB_NAME']." ((".$line['CAUSE'].")) ".$line['CRC']."\n";
                
                $StatusID[$spooler_id.$job_name] = $line['ID'];
                $c_job++;
        }
        $this->PrintMessage("Status: $c_job");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $timestamp = time();
        
        $Rows = $Result['report']['table']['rows']['row'];
        $n = 0;
        $Queries = array();
        while (isset($Rows[$n])) {
            $history_id = $Rows[$n]['id']['value'];
            $spooler = $Rows[$n]['spooler_id']['value'];
            $job_name = $Rows[$n]['job_name']['value'];
            $start_time = $Rows[$n]['start_time']['value'];
            if (isset($Rows[$n]['end_time']['value'])) {
                $end_time = '"'.$Rows[$n]['end_time']['value'].'"';
            }
            else {
                $end_time = 'null';
            }
            $error = $Rows[$n]['error']['value'];
            // $error_text = $Rows[$n]['error_text']['value'];
            $error_text = 'null';
            if (isset($Rows[$n]['cause']['value'])) 
                $cause = $Rows[$n]['cause']['value'];
            else 
                $cause = 'null';
            
            $exit_code = $Rows[$n]['exit_code']['value'];
            if (isset($Rows[$n]['pid']['value'])) 
                $pid = $Rows[$n]['pid']['value'];
            else 
                $pid ='null';
            // identifiant du job
            $id = $spooler.'/'.$job_name;
            // attention, il faut traiter la purge
            
            $update = 'HISTORY_ID='.$history_id.',START_TIME="'.$start_time.'",END_TIME='.$end_time.',PID='.$pid.',ERROR='.$error.',EXIT_CODE='.$exit_code.',ERROR_TEXT="'.$error_text.'",CAUSE="'.$cause.'"';
            if ($debug) print "(($update))\n";
            $crc = hash('crc32',$update);
            if ($debug) print "JOB ID $id\n";
            if (isset($JobID[$id])) {
                if ($debug) print "JobID OK: ".$JobID[$id]."\n";
                if (isset($StatusID[$id])) {
                    if ($debug) print "JobStatus OK\n";
                        print $JobCRC[$id]." $crc\n";
                        if ($JobCRC[$id] != $crc ) {
                            if ($debug) print "CRC KO\n";
                            $qry = 'update FOCUS_JOB_STATUS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where job_id='.$JobID[$id];
                            array_push($Queries, $qry);
                        }
                }
                else {
                        $qry = 'insert into FOCUS_JOB_STATUS (JOB_ID,HISTORY_ID,START_TIME,END_TIME,PID,ERROR,ERROR_TEXT,EXIT_CODE,CAUSE,UPDATED,CRC)'
                                . ' values ( '.$JobID[$id].','.$history_id.',"'.$start_time.'",'.$end_time.', '.$pid.','.$error.',"'.$error_text.'",'.$exit_code.',"'.$cause.'",'.$timestamp.',"'.$crc.'" )';
                        array_push($Queries, $qry);
                }
            }
            else {
                if (isset($StatusID[$id])) {
                    $qry = 'delete from FOCUS_JOBS_STATUS where id='.$StatusID[$id];
                }
            }
            $n++;
        }
        if (empty($Queries)) exit();
        
        if ($debug) {            
            foreach ($Queries as $q) {
                $res = $data->sql->query( $q );
                Print "$q ($res)\n"; 
            }
            exit();
        }
        
        $qry = implode(';', $Queries);
        $res = $data->sql->multi_query( $qry );
        exit();
    }

    public function runtimeAction() {      
        $this->mode='BATCH';
        $data = $this->getPost();
        // $data = file_get_contents('D:\arii\enterprises\SOS-Paris\scheduler\operations_gui\jobs_runtime.xml');
        $tools = $this->container->get('arii_core.tools');
        $Result = $tools->xml2array( $data , 1, 'attributes');

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
        $qry = $sql->Select(array('fjs.ID','fjs.JOB_ID','fjs.RUN_TIME','fjs.CRC',
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
        while ( $line = $data->sql->get_next($res) ) {
                $spooler_id = $line['SPOOLER_ID'];
                $job_id = $line['JOB_ID'];
                $job_name = $line['JOB_NAME'];
                $JobCRC[$spooler_id.$job_name] = $line['CRC'];
                $StatusID[$spooler_id.$job_name] = $line['ID'];
                $c_job++;
        }
        $this->PrintMessage("Status: $c_job");
        $this->PrintMessage("Timer: ".(microtime(true)-$timer));
        $timestamp = time();
        
        $Rows = $Result['report']['table']['rows']['row'];
        $n = 0;
        $Queries = array();
        while (isset($Rows[$n])) {
            $history_id = $Rows[$n]['id']['value'];
            $spooler = $Rows[$n]['spooler_id']['value'];
            $job_name = $Rows[$n]['job_name']['value'];
            $run_time = $Rows[$n]['avg_runtime']['value'];

            // identifiant du job
            $id = $spooler.'/'.$job_name;
            // attention, il faut traiter la purge
            
            $update = 'HISTORY_ID='.$history_id.',RUN_TIME='.$run_time;
            $crc = hash('crc32',$update);
            if (isset($JobID[$id])) {
                if (isset($StatusID[$id])) {
                        if ($JobCRC[$id] != $crc ) {
                            $qry = 'update FOCUS_JOB_RUNTIMES set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where job_id='.$JobID[$id];
                            array_push($Queries, $qry);
                        }
                }
                else {

                        $qry = 'insert into FOCUS_JOB_RUNTIMES (JOB_ID,HISTORY_ID,RUN_TIME,UPDATED,CRC)'
                                . ' values ( '.$JobID[$id].','.$history_id.',"'.$run_time.'",'.$timestamp.',"'.$crc.'" )';
                        array_push($Queries, $qry);
                }
            }
            else {
                if (isset($StatusID[$id])) {
                    $qry = 'delete from FOCUS_JOB_RUNTIMES where id='.$StatusID[$id];
                }
            }
            $n++;
        }
        if (empty($Queries)) exit();
        
        $qry = implode(';', $Queries);
        $res = $data->sql->multi_query( $qry );

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