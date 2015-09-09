<?php

namespace Arii\FocusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheV3Controller extends Controller
{
    protected $what = 'orders,job_chain_orders,job_orders,jobs,job_chains,schedules,remote_schedulers,payload,job_params';

    public function __construct( ) { }
    
    public function purgeAction($spooler_id) {
        $this->mode="WEB";
        $this->PrintMessage(2,"PURGE");
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        foreach (array('ORDERS','JOB_CHAIN_NODES','TASKS','LOCKS_USE','JOBS','JOB_CHAINS','LOCKS','PROCESS_CLASSES','REMOTE_SCHEDULERS','CONNECTIONS') as $table) {
             $this->PrintMessage(2,$table);
             $qry = 'delete from FOCUS_'.$table.' where spooler_id='.$spooler_id; 
             $this->PrintMessage(2,$qry);
             $res = $data->sql->query( $qry );
             $this->PrintMessage(2,$res);
       }       
       // on efface le spooler ?
       $qry = 'delete from FOCUS_SPOOLERS where id='.$spooler_id; 
       $res = $data->sql->query( $qry );
       exit();
    }
    
    public function fileAction() {
        set_time_limit ( 300 );
        if (!isset($_FILES['file'])) {
            print "No file !!";
            exit();
        }
        $File = $_FILES['file'];
        if ($File['error'] == 1) {
            print_r($File);
            exit();
        }   
        print "Received: ".$File['name']."\n";
        $data = file_get_contents($File['tmp_name']);
        print "Size: ".$File['size']."\n";        
        $focus = $this->container->get('arii_focus.focus');
        print $focus->cache($data,"BATCH",1);
        exit();
    }

    public function postAction() {
        print "POST RECEIVED !!!";
        set_time_limit ( 300 );
        $f = fopen('php://input', 'r');
        $data = '';
        while(!feof($f)) {
            $data .= fread($f,1024);
        }
        fclose($f);
        print "Size: ".strlen($data);
        $focus = $this->container->get('arii_focus.focus');
        print $focus->cache($data,"BATCH",1);
        exit();
    }

    public function refreshAction() {
        $request = Request::createFromGlobals();
        if ($request->query->get( 'spooler_id' )=='') return 'spooler?';
        $spooler_id = $request->query->get( 'spooler_id' );
        
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            '{spooler}'    => 'NAME' );

        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $qry = $sql->Select(array('NAME','IP_ADDRESS','TCP_PORT'))
                .$sql->From(array('FOCUS_SPOOLERS'))
                .$sql->Where(array('id'=>$spooler_id))
                .$sql->OrderBy(array('ID desc'));

        $res = $data->sql->query( $qry );
        $line = $data->sql->get_next($res);
        if (!isset($line['TCP_PORT'])) {
            print 'Scheduler "'.$spooler_id.'"?';
            exit();            
        }
        $port = $line['TCP_PORT'];
        $ip = $line['IP_ADDRESS'];
        $focus = $this->container->get('arii_focus.focus');
        print $focus->get($ip,$port,$this->what,"WEB",1);        
        exit();
    }

    public function getAction($spooler='localhost',$port='4444',$what='orders,job_chain_orders,job_orders,jobs,job_chains,schedules,remote_schedulers,payload,job_params') {
        $focus = $this->container->get('arii_focus.focus');
        print $focus->get($spooler,$port,$what,"WEB",2);
        exit();
    }
    
    public function testAction($file="C:/xampp/Symfony/show_state.xml") {
        $data = file_get_contents($file);
        $focus = $this->container->get('arii_focus.focus');
        return $focus->cache($data,"WEB",2);
    }

    
}