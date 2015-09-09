<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SpoolerController extends Controller
{
    public function statusAction()   
    {
        print "TODO!";
        exit();
    }

    public function formAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');      
        $qry = $sql->Select(array('ID','NAME','CONNECTION','UPDATED','SPOOLER_RUNNING_SINCE','STATE','LOG_FILE','VERSION','PID','HOST','IP_ADDRESS','NEED_DB','TCP_PORT','UDP_PORT','CONFIG_FILE','DB','CPU_TIME','TIME','WAITS','WAIT_UNTIL','LOOPS'))
                .$sql->From(array('FOCUS_SPOOLERS'))
                .$sql->Where(array('ID' => $id));

        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('form');
        $data->render_sql($qry,'ID','ID,NAME,CONNECTION,UPDATED,SPOOLER_RUNNING_SINCE,STATE,LOG_FILE,VERSION,PID,HOST,IP_ADDRESS,NEED_DB,TCP_PORT,UDP_PORT,CONFIG_FILE,DB,CPU_TIME,TIME,WAITS,WAIT_UNTIL,LOOPS');
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiJOCBundle:Spoolers:form_toolbar.xml.twig", array(), $response );
    }

    public function deleteAction()
    {
        $request = Request::createFromGlobals();
        $spooler_id  =  $request->get('spooler_id');
        
        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('data');
        
        $qry = "select s.ID,s.NAME as SPOOLER
            from FOCUS_SPOOLERS s where s.ID=".$spooler_id;
        
        $res = $data->sql->query( $qry );
        $line = $data->sql->get_next($res);
        if (isset($line['SPOOLER'])) {
            print "Spooler: ".$line['SPOOLER'];        
        }
        
        // tables jointes
        foreach (array( 'JOB_RUNTIMES|JOB_ID|JOBS',
                        'JOB_STATUS|JOB_ID|JOBS',
                        'LOCKS_USE|JOB_ID|JOBS',
                        'TASKS|JOB_ID|JOBS',
                        'JOB_PARAMS|JOB_ID|JOBS',
                        'ORDER_STEP_RUNTIMES|ORDER_ID|ORDERS',
                        'ORDER_STEP_STATUS|ORDER_ID|ORDERS',
                        'ORDER_RUNTIMES|ORDER_ID|ORDERS',
                        'ORDER_STATUS|ORDER_ID|ORDERS',
                        'JOB_CHAIN_NODES|JOB_ID|JOBS',
                        'JOB_CHAIN_NODES|CHAIN_ID|JOB_CHAINS',
                        'JOB_CHAIN_NODES|JOB_CHAIN_ID|JOB_CHAINS'
                        ) as $k) {
            list($table,$id,$from) = explode('|',$k);
            $qry = 'delete from FOCUS_'.$table.' where '.$id.' in ';
            $qry .= '(select id from FOCUS_'.$from.' where SPOOLER_ID='.$spooler_id.') or isnull('.$id.')';
            print "<br/>$qry";
            $res = $data->sql->query( $qry );
            if (!$res) {
                print $db->errno." ".$db->error."<hr/>";
                exit();
            }
        }
        
        // tables direct
        foreach (array('PAYLOADS','ORDERS','FILE_ORDERS','JOB_CHAIN_NODES','JOB_CHAINS','JOBS','LOCKS','PERIODS','SCHEDULES','CONNECTIONS','PROCESS_CLASSES','REMOTE_SCHEDULERS') as $table) {
            $qry = 'delete from FOCUS_'.$table.' where SPOOLER_ID='.$spooler_id;
            print "<br/>$qry";
            $res = $data->sql->query( $qry );
            if (!$res) {  
                print "$table: ".$db->errno." ".$db->error."<hr/>";
            } 
        }
        
        // SPOOLER A EFFACER EN FONCTION DE L'ID
        $qry = 'delete from FOCUS_SPOOLERS where ID='.$spooler_id;
        $res = $data->sql->query( $qry );
        if (!$res) {  
                print "SPOOLERS: ".$db->errno." ".$db->error."<hr/>";
                exit();
        } 
        print "OK!";
        exit();
    }
    
    public function logAction()   
    {
        
        $file = "C:/arii/enterprises/Laser/jobschedulers/lsjqa/logs/scheduler.log";
        $day = "02";
        $rh = fopen($file, 'r');
        $xml = "<?xml version='1.0' encoding='utf-8' ?><rows>";
        $max= 100000;
        while ((!feof($rh)) and ($max>0)) {            
            $max--;
            $l = fgets($rh, 4096);
            if (substr($l,0,2)==$day) {
                $d = substr($l,0,2);
                $h = substr($l,3,8);
                $n = substr($l,12,5);
                $pid = substr($l,18,12);
                $pid = substr($pid,0,strpos($pid,'.'));
                $l = substr($l,31);
                $type = $task = $status = $style = '';
                if (substr($l,0,11) == "{scheduler}") {
                    $type= "scheduler";
                    $l= substr($l,12);
                    $l = trim($l);
                    // on repere les [
                    if ($l=='') continue;
                    if ($l[0]=='[') {
                        $sub = substr($l,1,4);
                        if ($sub == 'info') {
                            $status = 'I';
                            $style = 'style="background-color: lightblue;"';
                            $l = substr($l,9);
                        }
                        elseif ($sub == 'WARN') {
                            $status = 'W';
                            $style = 'style="background-color: #FFE763;"';
                            $l = substr($l,9);
                        }
                        elseif ($sub == 'ERRO') {
                            $status = 'E';
                            $style = 'style="background-color: #fbb4ae;"';
                            $l = substr($l,9);
                        }
                    }
                    else {
                        $status = "";
                    }
                    if ($l=='') continue;
                    if ($l[0]=='(') {
                        // Est ce que c'est le log du job
                        $sub = substr($l,1,3);
                        // Est ce que c'est le log du job 
                        if ($sub=='Job') {
                            $d = 4;
                            $e = strpos($l,')',$d);
                            $task = substr($l,$d,$e-$d);
                            $type = "job";
                            $l = substr($l,$e+2);
                        }
                        elseif ($sub=='Tas') {
                            $d = 6;
                            $e = strpos($l,')',$d);
                            $task = substr($l,$d,$e-$d);
                            $type = "task";
                            $l = substr($l,$e+2);
                        }
                        // Est ce que c'est le log du job 
                        elseif ($sub=='Ord') {
                            $d = 7;
                            $e = strpos($l,')',$d);
                            $task = substr($l,$d,$e-$d);
                            $type = "order";
                            $l = substr($l,$e+2);
                        }
                        // Est ce que c'est le log du job 
                        elseif ($sub=='Dat') {
                            $type = "database";
                            $l = substr($l,11);
                        }
                    }
                    $xml .= "<row $style>";
                    $xml .= "<cell>$h</cell>";
                    // $xml .= "<cell>$n</cell>";
                    $xml .= "<cell>$pid</cell>";
                    $xml .= "<cell>$type";
                    if ($status != '') {
                        $xml .= " ($status)";
                    } 
                    $xml .= "</cell>";
                    // $xml .= "<cell>$task</cell>";
                    if (substr($l,0,10)=='SCHEDULER-') {
                        $l = '['.substr($l,10,3).']'.substr($l,14);
                    }
                    $l = str_replace('<','&lt;',$l);
                    $xml .= "<cell><![CDATA[".utf8_encode($l)."]]></cell>";
                    $xml .= "</row>";
                }
            }
        }
        $xml .= "</rows>";
        fclose($rh);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent($xml);
        return $response;
    }

  public function SubscribersAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');      
        $qry = $sql->Select(array('rs.ID','rs.NAME as REMOTE','HOSTNAME','IP','s.TCP_PORT','CONFIGURATION_CHANGED_AT','CONNECTED_AT','DISCONNECTED_AT','ERROR_AT','ERROR'))
                .$sql->From(array('FOCUS_REMOTE_SCHEDULERS rs'))
                .$sql->LeftJoin('FOCUS_SPOOLERS s',array('rs.REMOTE_ID','s.ID'))
                .$sql->Where(array('rs.SPOOLER_ID' => $id));

        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('grid');
        $data->render_sql($qry,'ID','REMOTE,HOSTNAME,IP,TCP_PORT,CONFIGURATION_CHANGED_AT','ONNECTED_AT','DISCONNECTED_AT','ERROR_AT','ERROR');
    }
   
 }
