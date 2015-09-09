<?php
// src/Arii/JOEBundle/Controller/XMLController.php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SOSController extends Controller
{
   protected $id_why=0;
    
   public function XMLCommandAction( )
   {
        $audit = $this->container->get('arii_core.audit');
        $errorlog = $this->container->get('arii_core.log');
        
        $request = Request::createFromGlobals();
        $xml_command = $request->get( 'command' );
        
        $id =  $request->get( 'id' );
        if ($id == '') { print "ID $id ?!"; exit(); }
        $type = substr($id,0,1);
        $id = substr($id,2);
        
        $Params = array();
        if ($request->get('params') != '')
            $Params = explode('||',urldecode($request->get('params')));
        $at = $request->get('at');

        switch ($type) {
            case 'X':
                return $this->XMLCommandSpooler($xml_command,$id);
                break;
            case 'N':
                return $this->XMLCommandNested($xml_command,$id);
                break;
            case 'G':
                return $this->XMLCommandChain($xml_command,$id);
                break;
            case 'O':
                return $this->XMLCommandOrder($xml_command,$id);
                break;
            case 'S':
                return $this->XMLCommandNode($xml_command,$id);
                break;
            case 'J':
                return $this->XMLCommandJob($xml_command,$id,$at,$Params);
                break;
            default:
                print "Type $type ?!"; 
                exit();
                break;
        }
   }

   private function XMLCommandSpooler($xml_command,$spooler_id) {
        // Recherche les informations de connexion
        $sos = $this->container->get('arii_joc.sos');
       list($spooler,$protocol,$hostname,$port,$path) = $sos->getConnectInfos($spooler_id);          
       switch($xml_command) {
            case 'abort_spooler':
                $cmd = "<modify_spooler cmd='terminate'/>";
                break;
            case 'terminate_spooler':
                $cmd = "<modify_spooler cmd='abort_immediately'/>";
                break;
            case 'restart_spooler':
                $cmd = "<modify_spooler cmd='terminate_and_restart'/>";
                break;
            case 'pause_spooler':
                $cmd = "<modify_spooler cmd='pause'/>";
                break;
            case 'continue_spooler':
                $cmd = "<modify_spooler cmd='continue'/>";
                break;
            default:
                print "XML Command $xml_command ?!";
                exit();
       }
       return $this->ExecCommand($spooler_id,$hostname,$port,$path,$protocol,$cmd);
   }

   private function XMLCommandNode($xml_command,$job_chain_node_id) {
        // Recherche les informations de connexion
        $sos = $this->container->get('arii_joc.sos');
        list($spooler,$protocol,$hostname,$port,$path,$job_chain,$state) = $sos->getJobChainNodeInfos($job_chain_node_id);          
        switch($xml_command) {
            case 'stop_node':
                $cmd = '<job_chain_node.modify job_chain="'.$job_chain.'" state="'.$state.'" action="stop" />';
                break;
            case 'skip_node':
                $cmd = '<job_chain_node.modify job_chain="'.$job_chain.'" state="'.$state.'" action="next_state" />';
                break;
            case 'unstop_node':
            case 'unskip_node':
                $cmd = '<job_chain_node.modify job_chain="'.$job_chain.'" state="'.$state.'" action="process" />';
                break;
            default:
                print "XML Command $xml_command ?!";
                exit();
        }
        return $this->ExecCommand($spooler,$hostname,$port,$path,$protocol,$cmd);
   }

   private function XMLCommandJob($xml_command,$job_id,$at,$Params) {
        // Recherche les informations de connexion
        $sos = $this->container->get('arii_joc.sos');
        switch($xml_command) {
            case 'start_job':
                $status = 'STARTING';
                break;
            default:
                $status = 'UPDATING';
                break;
        }
        list($spooler,$protocol,$hostname,$port,$path,$job) = $sos->getJobInfos($job_id,$status);     
        switch($xml_command) {
            case 'start_job':
                $cmd  = '<start_job job="'.$job.'"';
                if ($at == '') $at = 'now';
                $cmd .= ' at="'.$at.'">';
                //$parameters = $request->request->get( 'parameters' );
                 if (!empty($Params)) {                     
                    $cmd  .= '<params>';
                    foreach ($Params as $params) { 
                       // $val = $request->request->get( $var );
                        $param = explode('=', $params);
                        $cmd  .= '<param name="'.$param[0].'" value="'.$param[1].'"/>';
                    }
                    $cmd  .= '</params>';
                 }
                 $cmd .= '</start_job>';
                break;
             case 'stop_job':
                 $cmd  = '<modify_job job="'.$job.'" cmd="stop"/>';
                 break;
             case 'unstop_job':
                 $cmd  = '<modify_job job="'.$job.'" cmd="unstop"/>';
                 break;
             case 'show_job':
                 $cmd  = '<show_job job="'.$job.'"/>';            
                 $res = $this->ExecCommand($spooler,$hostname,$port,$path,$protocol,$cmd,true);
                 exit();
                 break;
            default:
                print "XML Command $xml_command ?!";
                exit();
        }
        return $this->ExecCommand($spooler,$hostname,$port,$path,$protocol,$cmd);
   }

   private function XMLCommandChain($xml_command,$job_chain_id) {
        // Recherche les informations de connexion
        $sos = $this->container->get('arii_joc.sos');
        list($spooler,$protocol,$hostname,$port,$path,$job_chain) = $sos->getJobChainInfos($job_chain_id);          
        switch($xml_command) {
            case 'add_order': 
                $request = Request::createFromGlobals();
                $order_id = $request->get('order_id');    
                $title = $request->get('title'); 
                $params = $request->get( 'paramsStr' );
                $at = $request->request->get('at');
                $start_state = $request->request->get('start_state');
                $end_state = $request->request->get('end_state');
                $cmd = '<add_order job_chain="'.$job_chain.'" id="'.$order_id.'"';
                if ($title!='')
                    $cmd .= ' title="'.$title.'"';
                if ($start_state!='')
                    $cmd .= ' state="'.$start_state.'"';
                if ($end_state!='' && $end_state !="none")
                    $cmd .= ' end_state="'.$end_state.'"';
                if ($at == '') $at = 'now';
                $cmd .= ' at="'.$at.'">';
                $cmd .= $this->AddParams($params);
                $cmd .= '</add_order>';
                print str_replace('<','&lt;',$cmd);
                break;
             case 'stop_chain':
                 $cmd = '<job_chain.modify job_chain="'.$job_chain.'" state="stopped" />';
                 break;
             case 'unstop_chain':
                 $cmd = '<job_chain.modify job_chain="'.$job_chain.'" state="running" />';
                 break;
            default:
                print "XML Command $xml_command ?!";
                exit();
        }
        return $this->ExecCommand($spooler,$hostname,$port,$path,$protocol,$cmd);
   }

   private function XMLCommandNested($xml_command,$job_chain_node_id) {
        // Recherche les informations de connexion
        $sos = $this->container->get('arii_joc.sos');
        list($spooler,$protocol,$hostname,$port,$path,$job_chain) = $sos->getJobChainNodeInfos($job_chain_node_id);          
        switch($xml_command) {
            case 'add_order': 
                $request = Request::createFromGlobals();             
                $order_id = $request->get('order_id');    
                $title = $request->get('title'); 
                $params = $request->get( 'paramsStr' );
                $at = $request->request->get('at');
                $start_state = $request->request->get('start_state');
                $end_state = $request->request->get('end_state');   
                $cmd = '<add_order job_chain="'.$job_chain.'" id="'.$order_id.'"';
                if ($title!='')
                    $cmd .= ' title="'.$title.'"';
                if ($start_state!='')
                    $cmd .= ' state="'.$start_state.'"';
                if ($end_state!='' && $end_state !="none")
                    $cmd .= ' end_state="'.$end_state.'"';
                if ($at == '') $at = 'now';
                $cmd .= ' at="'.$at.'">';
                $cmd .= $this->AddParams($params);
                $cmd .= '</add_order>';
                break;
            default:
                break;
        }
        return $this->ExecCommand($spooler,$hostname,$port,$path,$protocol,$cmd);
   }

   private function XMLCommandOrder($xml_command,$order_id) {
        // Recherche les informations de connexion
        $sos = $this->container->get('arii_joc.sos');
        list($spooler,$protocol,$hostname,$port,$path,$order,$job_chain,$state,$initial,$end) = $sos->getOrderInfos($order_id);          
        switch($xml_command) {
            case 'start_order':
                $request = Request::createFromGlobals();
                $title = $request->get('title'); 
                $params = $request->get( 'paramsStr' );
                $at = $request->request->get('at');
                $start_state = $request->request->get('start_state');
                $end_state = $request->request->get('end_state');   
                 $cmd = '<modify_order order="'.$order.'" job_chain="'.$job_chain.'"';
                 $at = $request->get('at');
                 if ($at == '') $at = 'now';
                 $cmd .= ' at="'.$at.'">';
                 
                 $params_string = $request->get('params');
                 if ($params_string!='') {
                    $cmd .= '<params>';
                    foreach (explode(',',urldecode($params_string)) as $params) {
                        $param = explode('=', $params);
                        $cmd  .= '<param name="'.$param[0].'" value="'.$param[1].'"/>';
                    }
                    $cmd .= '</params>';
                 }
                 $cmd .= '</modify_order>';
                 print $cmd;
                 break;                                   
            case 'resume_order':
                $cmd = '<modify_order job_chain="'.$job_chain.'" order="'.$order.'" suspended="no" />';
                break;
            case 'reset_order':
                $cmd = '<modify_order job_chain="'.$job_chain.'" order="'.$order.'" action="reset" />';
                break;
            case 'remove_setback':
                $cmd = '<modify_order job_chain="'.$job_chain.'" order="'.$order.'" setback="no" />';
                break;
            case 'suspend_order':
                $cmd = '<modify_order job_chain="'.$job_chain.'" order="'.$order.'" suspended="yes" />';
                break;
            default:
                print "XML Command $xml_command ?!";
                exit();
        }
        return $this->ExecCommand($spooler,$hostname,$port,$path,$protocol,$cmd);
   }

   private function AddParams($params_string) {
        if ($params_string=='') return '<params/>';
        $cmd  = '<params>';
        foreach (explode(',',urldecode($params_string)) as $params) { 
           // $val = $request->request->get( $var );
            $param = explode('=', $params);
            $cmd  .= '<param name="'.$param[0].'" value="'.$param[1].'"/>';
        }
        $cmd  .= '</params>';
   }
   
   private function ExecCommand($spooler_id,$scheduler,$port,$path,$protocol,$cmd,$raw=false) {
        $SOS = $this->container->get('arii_core.sos');
        $result = $SOS->XMLCommand($spooler_id,$scheduler,$port,$path,$protocol,$cmd);

        if ($raw) return $result;
        
        if (isset($result['ERROR'])) {
            if (substr($result['ERROR'],0,7) === 'CONNECT') {
                $error = $this->get('translator')->trans('Connection failed %protocol%://%host%:%port%! Please make sure the JobScheduler have started!', array('%protocol%' => $protocol,'%host%' => $scheduler,'%port%' => $port ));
            }
            else {
                $error = $this->get('translator')->trans('Unknown error !');    
            }
            $t = "<font color='red'>".$this->get('translator')->trans('ERROR !')."</font>";
            $t .= "<p>$error</p>";
            print $t;
            exit();
            return new Response($t);
        }
        if (!isset($result['spooler'])) {
            print "<font color='red'>No answer!</font>";
            exit();
        }
        
        if (!isset($result['spooler']['answer'])) {
            print "<font color='red'>".$result."</font>";
            exit();
        }
        
        // Si c'est pas bon on sort en erreur
        if (!isset($result['spooler']['answer']['ok'])) {
            if (isset($result['spooler']['answer']['ERROR'])) {
                if (isset($result['spooler']['answer']['ERROR_attr'])) {
                    print $result['spooler']['answer']['ERROR_attr']['time'].": ";
                    print "<font color='red'>";
                    print $result['spooler']['answer']['ERROR_attr']['text'];
                    print "</font>";
                    exit();
                }
            }
        }
        
        $date = $this->container->get('arii_core.date');     
        print "<table>";
        if (isset($result['spooler']['answer_attr']['time']))
            print "<tr><th align='right'><font color='green'>".$this->get('translator')->trans('Answer OK')."</font></th><td>".$date->ShortDate($date->Date2Local( $result['spooler']['answer_attr']['time'], $spooler_id ) )."</td></tr>";
       if (isset($result['spooler']['answer']['ok']['order_attr'])){
            foreach (array('job_chain','order','created','state') as $k) {
                if (isset($result['spooler']['answer']['ok']['order_attr'][$k]))
                    print "<tr><th align='right'>".$this->get('translator')->trans($k)."</th><td>".$result['spooler']['answer']['ok']['order_attr'][$k]."</td></tr>";
            }
        }
        if (isset($result['spooler']['answer']['ok']['task_attr'])){
            foreach (array('job','id','start_at','state') as $k) {
                if (isset($result['spooler']['answer']['ok']['task_attr'][$k]))
                    print "<tr><th align='right'>".$this->get('translator')->trans($k)."</th><td>".$result['spooler']['answer']['ok']['task_attr'][$k]."</td></tr>";
            }
        }
        print "</table>";        
        exit();
   }
   
   private function XMLCommandOther($id) {
        print "$id ";
        exit();
        $spooler_id = 'est';
        switch ($xml_command) {            
            case 'why_job':
                // En entrée:
                //   job_id: identifiant du traitement
                $job_id = $request->get('job_id');
                // informations du job
                list($spooler_id,$job) = $this->getJobInfos($job_id);
                // construction de la commande
                $cmd  = '<job.why job="'.$job.'"/>';
                break;
            case 'kill_task': 
                 $job_id = $request->request->get( 'id' );
                 if (($p=strpos($job_id,'#'))>0) {
                     $job_id=substr($job_id,0,$p);
                 }
                 list($spooler_id,$job) = $this->getJobInfos($job_id);

                 $cmd  = '<kill_task job="'.$job.'"';
                 $cmd .= ' id="'.$job_id.'"';
//                 if ($request->request->get( 'immediately' )=='yes')
// http://www.sos-berlin.com/mediawiki/index.php/What_is_the_difference_between_%22end%22_and_%22kill_immediately%22%3F
                 $cmd .= ' immediately="yes"';
                 $cmd .= '/>';
                break;
            /*    
            case 'modify_order_prompt':
                 $order = $request->request->get( 'order' );
                 $job_chain = $request->request->get( 'job_chain' );
                 $state = $request->request->get( 'state' );
                 $cmd  = '<modify_order job_chain="'.$job_chain.'" state="'.$state.'" order="'.$order.'" suspended="no"><params><param name="scheduler_prompt" value="true"/></params></modify_order>';
                break;
            case 'remove_order':
                 $order = $request->request->get( 'order' );
                 $job_chain = $request->request->get( 'job_chain' );
                 $cmd  = '<remove_order job_chain="'.$job_chain.'" order="'.$order.'"></remove_order>';
                break;
             * 
             */
/* A corriger pour postgres ! */
                $state_id = $request->get('id');
                $qry = 'SELECT sjcn.spooler_id,sjcn.job_chain,sjcn.order_state,ac.interface as hostname,ac.port as tcp_port,ac.path,an.protocol
                        FROM SCHEDULER_JOB_CHAIN_NODES sjcn
                        LEFT JOIN ARII_SPOOLER asp
                        ON sjcn.spooler_id=asp.scheduler
                        LEFT JOIN ARII_CONNECTION ac
                        ON asp.connection_id = ac.id
                        LEFT JOIN ARII_NETWORK an
                        ON ac.network_id = an.id
                        WHERE CONCAT(sjcn.spooler_id,"/",sjcn.job_chain,"/",sjcn.order_state) = "'.$state_id.'"';
                $res = $data->sql->query($qry);
                $line = $data->sql->get_next($res);

                $job_chain = $line['job_chain'];
                $state = $line['order_state'];
                switch($xml_command){
                    case "stop_node_job":
                        $cmd = '<job_chain_node.modify job_chain="'.$job_chain.'" state="'.$state.'" action="stop" />';
                        break;
                    case "skip_node_job":
                        $cmd = '<job_chain_node.modify job_chain="'.$job_chain.'" state="'.$state.'" action="next_state" />';
                        break;
                    case "unstop_node_job":
                    case "unskip_node_job":
                        $cmd = '<job_chain_node.modify job_chain="'.$job_chain.'" state="'.$state.'" action="process" />';
                        break;
                    default:
                        break;
                }
                break;
            default:
                $cmd = "Unknown command !!";
                print "XML Command $xml_command ?!";
                exit();
        }
        // Recherche les informations de connexion
        list($protocol,$scheduler,$port,$path) = $this->getConnectInfos($spooler_id);                
        
        if (!isset($cmd)) {
            $errorlog->Error("XML Command undefined",0,__FILE__,__LINE__,__FUNCTION__);
            print "Undefined XML Command";
            exit();
        }
        
        $SOS = $this->container->get('arii_core.sos');
        $result = $SOS->XMLCommand($spooler_id,$scheduler,$port,$path,$protocol,$cmd);

        if (isset($result['ERROR'])) {
            if (substr($result['ERROR'],0,7) === 'CONNECT') {
                $t = $this->get('translator')->trans('Connection failed %protocol%://%host%:%port%! Please make sure the JobScheduler have started!', array('%protocol%' => $protocol,'%host%' => $scheduler,'%port%' => $port ));
                // on modifie le statut dans la base de données
                $dhtmlx = $this->container->get('arii_core.dhtmlx');
                $data = $dhtmlx->Connector('data');
                
                $sql = $this->container->get('arii_core.sql');
                $qry = $sql->Update(array('SCHEDULER_INSTANCES'))
                        .$sql->Set(array('IS_RUNNING' => 0))
                        .$sql->Where(array('SCHEDULER_ID' => $spooler_id));
                $res = $data->sql->query( $qry );
            }
            else {
                $t = $this->get('translator')->trans('Unknown error !');    
            }
            print "<font color='red'>".$this->get('translator')->trans('ERROR !')."</font>";
            print "<p>$t</p>";
            // print_r($result);
            exit();
            return new Response($t);
        }

        if (!isset($result['spooler'])) {
            print "<font color='red'>No answer!</font>";
            exit();
        }
        if (!isset($result['spooler']['answer'])) {
            print "<font color='red'>".$result."</font>";
            exit();
        }
        
        // Si c'est pas bon on sort en erreur
        if (!isset($result['spooler']['answer']['ok'])) {
            if (isset($result['spooler']['answer']['ERROR'])) {
                if (isset($result['spooler']['answer']['ERROR_attr'])) {
                    print $result['spooler']['answer']['ERROR_attr']['time'].": ";
                    print "<font color='red'>";
                    print $result['spooler']['answer']['ERROR_attr']['text'];
                    print "</font>";
                    exit();
                }
            }
        }
        
        switch ($xml_command) {
           case 'why_job':
                $xml = $this->XmlWhy($result['spooler']['answer']);
                header('Content-type: text/xml');
                print "<?xml version='1.0' encoding='utf-8' ?>";
                print "<rows>$xml</rows>";
                break;
           default:
                print "<table>";
                if (isset($result['spooler']['answer_attr']['time']))
                    print "<tr><th align='right'>Executed</th><td>".$result['spooler']['answer_attr']['time']."</td></tr>";
               if (isset($result['spooler']['answer']['ok']['order_attr'])){
                    foreach (array('job_chain','order','created','state') as $k) {
                        if (isset($result['spooler']['answer']['ok']['order_attr'][$k]))
                            print "<tr><th align='right'>$k</th><td>".$result['spooler']['answer']['ok']['order_attr'][$k]."</td></tr>";
                    }
                }
                if (isset($result['spooler']['answer']['ok']['task_attr'])){
                    foreach (array('job','id','start_at','state') as $k) {
                        if (isset($result['spooler']['answer']['ok']['task_attr'][$k]))
                            print "<tr><th align='right'>$k</th><td>".$result['spooler']['answer']['ok']['task_attr'][$k]."</td></tr>";
                    }
                }
                print "</table>";
        }
        exit();
   }

   private function XmlWhy($result,$xml='') {
       foreach ($result as $k=>$v) {
           $img = ' image="bullet_green.png"'; $style = '';
           if (substr($k,-5)!='_attr') {
               if (substr($k,-4)=='.why') {
                    $img = ' image="eye.png"';
                    $k = substr($k,0,strpos($k,'.why'));
                    $style=' style="background-color: lightblue;"'; 
               }
               elseif ($k == 'obstacle') {
                    $k = '';
                    $img = ' image="exclamation.png"';
                    $style=' style="background-color: red;"'; 
               }
            $xml .= '<row id="'.$this->id_why++.'" open="1"'.$style.'>';
            $xml .= '<cell'.$img.'>'.$k.'</cell>';           
           }
            if (is_array($v)) {
                $xml .= $this->XmlWhy($v);
            }
            else {
                // si on a un obstacle, c'est sur les prochains
                $xml .= '<cell>'.$v.'</cell>';
            }
           if (substr($k,-5)!='_attr') {
            $xml .= '</row>';
           }
       }
       return $xml;
   }
}
