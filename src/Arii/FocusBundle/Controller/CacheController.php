<?php

namespace Arii\FocusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class CacheController extends Controller
{
    protected $mode;
    
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

    public function getAction($spooler='localhost',$port='4444',$what='job_chain_orders,job_orders,jobs,job_chains,remote_schedulers') {
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
        
        return $this->cache($data);
    }
    
    private function cache($data,$mode = 'debug') {   
        $tools = $this->container->get('arii_core.tools');
        $Result = $tools->xml2array( $data , 1, 'attributes');
        if (!isset($Result['spooler'])) {
            print "spooler inconnu ?!";
            exit();
        }

        $update_time = new \DateTime();
        $host = $Result['spooler']['answer']['state']['attr']['host'];
        // Adresse IP ?
        if (isset($Result['spooler']['answer']['state']['attr']['ip_address'])) {
           $ip = $Result['spooler']['answer']['state']['attr']['ip_address'];
           $host = gethostbyaddr($ip);
        }
        else {
           // $ip = gethostbyname($host);
          $ip = 'localhost';
        }
        $SpoolerAttr = $Result['spooler']['answer']['state']['attr']; 
        $port = $SpoolerAttr['tcp_port'];
           
        $connection = "$ip#$port";
        $em = $this->getDoctrine()->getManager();

        $spooler_found = $em->getRepository("AriiFocusBundle:Spoolers")->findOneBy(array('connection' => $connection)); 
        if ($spooler_found) {
            $spoolers = $spooler_found;
        }
        else {
            $spoolers = new \Arii\FocusBundle\Entity\Spoolers();
            $spoolers->setConnection($connection);
        }
        
        // On recherche le spooler dans la base des connections
        $arii_connection = $em->getRepository("AriiCoreBundle:Connection")->findOneBy(array( 'interface'=> $ip, 'port' => $port, 'network' => 2 )); 
        $timezone = '';
        if ($arii_connection) {
            $id = $arii_connection->getId();
            print '[Connection: '.$id.']';
            $enterprise = $arii_connection->getEnterprise();
            $spoolers->setEnterprise($enterprise);
            // print "Connection Id: $id";
            $arii_spooler = $em->getRepository("AriiCoreBundle:Spooler")->findOneBy(array( 'connection'=> $id )); 
            if ($arii_spooler) {
                // print "Spooler Id: ".$arii_spooler->getId();
                $spoolers->setSpooler($arii_spooler);
            }
        }        
        if ($timezone=='') {
            $version = $Result['spooler']['answer']['state']['attr']['version'];
            if (substr($version,0,3)>='1.5') {
                $timezone ='GMT';                
            }
            else {
                $timezone = date_default_timezone_get(); 
            }
        }

        $spoolers->setHost($host);
        $spoolers->setIpAddress($ip);
        $spoolers->setName($Result['spooler']['answer']['state']['attr']['spooler_id']);
        $spoolers->setTime(new \DateTime($Result['spooler']['answer']['state']['attr']['time']));
        $spoolers->setSpoolerRunningSince(new \DateTime($Result['spooler']['answer']['state']['attr']['spooler_running_since']));
        $spoolers->setState($SpoolerAttr['state']);
        $spoolers->setLogFile($SpoolerAttr['log_file']);
        $spoolers->setVersion($SpoolerAttr['version']);
        $spoolers->setPid($SpoolerAttr['pid']);
        $spoolers->setConfigFile($SpoolerAttr['config_file']);
        $spoolers->setNeedDb($SpoolerAttr['need_db']=='yes'?true:false);
        $spoolers->setTcpPort($SpoolerAttr['tcp_port']);
        $spoolers->setUdpPort($SpoolerAttr['udp_port']);
        $spoolers->setDb($SpoolerAttr['db']);
        $spoolers->setLoops($SpoolerAttr['loop']);
        
        if (isset($SpoolerAttr['cpu_time'])) {
            $spoolers->setCpuTime($SpoolerAttr['cpu_time']);
        }
        
        $spoolers->setTime(new \DateTime($SpoolerAttr['time']));
        $spoolers->setWaits($SpoolerAttr['waits']);
        if (isset($SpoolerAttr['wait_until'])) {
            $spoolers->setWaitUntil($this->DateManager($SpoolerAttr['wait_until']),'timezone');
        }    
        $spoolers->setUpdated($update_time);
        $em->persist($spoolers);
        $em->flush();
        $spooler_id = $spoolers->getId();
        
        //===============================================================
        // Process classes
        //===============================================================
        $PCAttr = $Result['spooler']['answer']['state']['process_classes'];
        // verifier le cas ou il y a un seul job
        $n = 0;
        while (isset($PCAttr['process_class'][$n])) {
            $PCInfo = $PCAttr['process_class'][$n];
            
            // Le process_class existe ?
            $path = $PCInfo['attr']['path'];
            if (isset($PCEntity[$path])) {
                $pc_found=$PCEntity[$path];
            }
            else {
                $pc_found = $em->getRepository("AriiFocusBundle:ProcessClasses")->findOneBy(array('spooler'=>$spooler_id, 'path' => $path)); 
            }
            if ($pc_found) {
                // on conserve l'entite
                $PCEntity[$path] = $pc_found;
                $process_classes = $pc_found;
            }
            else {
                $process_classes = new \Arii\FocusBundle\Entity\ProcessClasses();
                $process_classes->setSpooler($spoolers);
                $process_classes->setPath($path);
            }
            $process_classes->setName(isset($PCInfo['attr']['name'])?$PCInfo['attr']['name']:'');
            $process_classes->setMaxProcesses($PCInfo['attr']['max_processes']);
            $process_classes->setRemoteScheduler(isset($PCInfo['attr']['remote_scheduler'])?$PCInfo['attr']['remote_scheduler']:NULL);
            $process_classes->setProcesses($PCInfo['attr']['processes']);
            $process_classes->setState(isset($PCInfo['attr']['state'])?$PCInfo['attr']['state']:NULL);
            $process_classes->setUpdated($update_time);
            $em->persist($process_classes);
            $n++;
        }
        $em->flush();

        //===============================================================
        // Locks
        //===============================================================
        if (isset($Result['spooler']['answer']['state']['locks'])) {
            $LockAttr = $Result['spooler']['answer']['state']['locks'];
            // verifier le cas ou il y a un seul job
            $n = 0;
            while (isset($LockAttr['lock'][$n])) {
                $LockInfo = $LockAttr['lock'][$n];

                // Le process_class existe ?
                $path = $LockInfo['attr']['path'];
                if (isset($LockEntity[$path])) {
                    $lock_found=$LockEntity[$path];
                }
                else {
                    $lock_found = $em->getRepository("AriiFocusBundle:Locks")->findOneBy(array('spooler'=>$spooler_id, 'path' => $path)); 
                    $LockEntity[$path] = $lock_found;
                }
                if ($lock_found) {
                    // on conserve l'entite
                    $locks = $lock_found;
                }
                else {
                    $locks = new \Arii\FocusBundle\Entity\Locks();
                    $locks->setSpooler($spoolers);
                    $locks->setPath($path);
                }
                $locks->setName(isset($LockInfo['attr']['name'])?$LockInfo['attr']['name']:NULL);
                $locks->setMaxNonExclusive(isset($LockInfo['attr']['max_non_exclusive'])?$LockInfo['attr']['max_non_exclusive']:NULL);
                $locks->setIsFree(isset($LockInfo['attr']['is_fee']) and ($LockInfo['attr']['is_fee']=="yes")? TRUE:FALSE);
                $locks->setFile($LockInfo['file_based']['attr']['file']);
                $locks->setState($LockInfo['file_based']['attr']['state']);
                $locks->setLastWriteTime(isset($LockInfo['file_based']['attr']['last_write_time'])?new \DateTime($LockInfo['file_based']['attr']['last_write_time']):NULL);
                $locks->setUpdated($update_time);
                $em->persist($locks);
                $n++;
            }
            $em->flush();        
        }
        
        //===============================================================
        // Jobs
        //===============================================================
        $JobsAttr = $Result['spooler']['answer']['state']['jobs'];
        // verifier le cas ou il y a un seul job
        $n = 0;
        while (isset($JobsAttr['job'][$n])) {
            $JobInfo = $JobsAttr['job'][$n];
            // Le job existe ?
            $path = $JobInfo['attr']['path'];
            $job_found = $em->getRepository("AriiFocusBundle:Jobs")->findOneBy(array('spooler'=>$spooler_id, 'path' => $path)); 
            if ($job_found) {
                $jobs = $job_found;
            }
            else {
                $jobs = new \Arii\FocusBundle\Entity\Jobs();
                $jobs->setSpooler($spoolers);
                $jobs->setPath($path);
            }
            $JOBEntity[$path] = $jobs;
            $jobs->setState($JobInfo['attr']['state']);
            $jobs->setStateText(isset($JobInfo['attr']['state_text']) ? $JobInfo['attr']['state_text']: '');
            $jobs->setJob($JobInfo['attr']['job']);
            $jobs->setAllSteps(isset($JobInfo['attr']['all_steps']) ? $JobInfo['attr']['all_steps'] : 0);
            $jobs->setAllTasks(isset( $JobInfo['attr']['all_tasks']) ? $JobInfo['attr']['all_tasks'] : 0);
            $jobs->setEnabled(isset($JobInfo['attr']['enabled'] ) and ( $JobInfo['attr']['enabled'] == 'yes' ) ? TRUE : FALSE);
            $jobs->setOrdered(isset($JobInfo['attr']['order']) and ($JobInfo['attr']['order'] == 'yes' ) ? TRUE : FALSE);
            $jobs->setTasks(isset($JobInfo['attr']['tasks']) ? $JobInfo['attr']['tasks'] : 0 );	
            $jobs->setInPeriod(isset($JobInfo['attr']['in_period'] ) and ( $JobInfo['attr']['in_period'] == 'yes' ) ? TRUE : FALSE);	
            $jobs->setHasDescription(isset($JobInfo['attr']['has_description'] ) and ( $JobInfo['attr']['has_description'] == 'yes' ) ? TRUE : FALSE);	
            $jobs->setNextStartTime(isset($JobInfo['attr']['next_start_time']) ? $this->DateManager($JobInfo['attr']['next_start_time']) : NULL);	
            $jobs->setLastWriteTime(isset($JobInfo['attr']['last_write_time']) ? new \DateTime($JobInfo['attr']['last_write_time']) : NULL);	
            $jobs->setLastInfo(isset($JobInfo['attr']['last_info']) ? $JobInfo['attr']['last_info'] : '');	
            $jobs->setLastWarning(isset($JobInfo['attr']['last_warning']) ? $JobInfo['attr']['last_warning'] : '');	
            $jobs->setTitle (isset($JobInfo['attr']['title'])  ? $JobInfo['attr']['title'] : '' );	
            $jobs->setWaitingForProcess (isset($JobInfo['attr']['waiting_for_process']) and ($JobInfo['attr']['waiting_for_process'] == 'yes' ) ? TRUE : FALSE );	
            $jobs->setTasks(isset($JobInfo['tasks']['attr']['count'])?$JobInfo['tasks']['attr']['count']:0);

            $jobs->setErrorCode(isset($JobInfo['file_based']['ERROR']['attr']['code'])?$JobInfo['file_based']['ERROR']['attr']['code']:NULL);
            $jobs->setErrorText(isset($JobInfo['file_based']['ERROR']['attr']['text'])?$JobInfo['file_based']['ERROR']['attr']['text']:NULL);
            
            if (isset($JobInfo['log'])) {
                $jobs->setHighestLevel($JobInfo['log']['attr']['highest_level']);
                $jobs->setLastInfo(isset($JobInfo['log']['attr']['last_info'])?$JobInfo['log']['attr']['last_info']:NULL);
                $jobs->setLastInfo(isset($JobInfo['log']['attr']['last_warning'])?$JobInfo['log']['attr']['last_warning']:NULL);
                $jobs->setLastInfo(isset($JobInfo['log']['attr']['last_error'])?$JobInfo['log']['attr']['last_error']:NULL);
                $jobs->setLevel($JobInfo['log']['attr']['level']);
            }
            $jobs->setUpdated($update_time);

            if (isset($JobInfo['attr']['process_class'])) {
                $pc = $JobInfo['attr']['process_class'];
                if (isset($PCEntity[$pc])) {
                    $jobs->setProcessClass( $PCEntity[$pc] );	                
                }
                else {
                    $jobs->setProcessClass( NULL );	
                }
            }            
            $em->persist($jobs);
            $em->flush();
            $job_id = $jobs->getId();

            // Taches en cours ?
            if (isset($JobInfo['tasks']['attr']['count']) and ($JobInfo['tasks']['attr']['count']>0)) {
                $nt = 0;
                // cas ou il n'y en a qu'un
                if (!isset($JobInfo['tasks']['task'][$nt])) {
                    $JobInfo['tasks']['task'][$nt] = $JobInfo['tasks']['task'];
                }
                while (isset($JobInfo['tasks']['task'][$nt])) {
                    $TaskInfo = $JobInfo['tasks']['task'][$nt];

                    $task = $TaskInfo['attr']['task'];
                    $task_found = $em->getRepository("AriiFocusBundle:Tasks")->findOneBy(array('job'=>$job_id, 'task' => $task)); 
                    if ($task_found) {
                        $tasks = $task_found;
                    }
                    else {
                        $tasks = new \Arii\FocusBundle\Entity\Tasks();
                        $tasks->setJob($jobs);
                        $tasks->setTask($task);
                    }
                    $tasks->setRun($TaskInfo['attr']['id']);
                    $tasks->setSpooler($spoolers);
                    $tasks->setState($TaskInfo['attr']['state']);
                    $tasks->setName($TaskInfo['attr']['name']);
                    $tasks->setRunningSince( new \DateTime($TaskInfo['attr']['running_since']));
                    $tasks->setEnqueued(isset($TaskInfo['attr']['enqueued'])? new \DateTime($TaskInfo['attr']['enqueued']):NULL);
                    $tasks->setStartAt( new \DateTime($TaskInfo['attr']['start_at']));
                    $tasks->setCause($TaskInfo['attr']['cause']);
                    $tasks->setSteps($TaskInfo['attr']['steps']);
                    $tasks->setLogFile($TaskInfo['attr']['log_file']);
                    $tasks->setPid($TaskInfo['attr']['pid']);
                    $tasks->setPriority($TaskInfo['attr']['priority']);
                    $tasks->setForceStart($TaskInfo['attr']['force_start']);
                    
                    // Log
                    if (isset($TaskInfo['log'])) {
                        $tasks->setHighestLevel($TaskInfo['log']['attr']['highest_level']);
                        $tasks->setLastInfo(isset($TaskInfo['log']['attr']['last_info'])?$TaskInfo['log']['attr']['last_info']:NULL);
                        $tasks->setLastInfo(isset($TaskInfo['log']['attr']['last_warning'])?$TaskInfo['log']['attr']['last_warning']:NULL);
                        $tasks->setLastInfo(isset($TaskInfo['log']['attr']['last_error'])?$TaskInfo['log']['attr']['last_error']:NULL);
                        $tasks->setLevel($TaskInfo['log']['attr']['level']);
                    }
                    
                    $tasks->setUpdated($update_time);
                    $em->persist($tasks);
                    $nt++;
                }
                $em->flush();
            }
                     
            // Si on a un verrou
            $nl = 0;
            if (isset($JobInfo['lock.requestor'])) {
                // cas ou il n'y en a qu'un
                if (!isset($JobInfo['lock.requestor']['lock.use'][$nl])) {
                    $JobInfo['lock.requestor']['lock.use'][$nl] = $JobInfo['lock.requestor']['lock.use'];
                }
                while (isset($JobInfo['lock.requestor']['lock.use'][$nl])) {
                    $LRInfo = $JobInfo['lock.requestor']['lock.use'][$nl];
                    // On recherche un verrou dans la base
                    $name = $LRInfo['attr']['lock'];
                    $lr_found = $em->getRepository("AriiFocusBundle:LocksUse")->findOneBy(array('job'=>$job_id, 'name' => $name)); 
                    if ($lr_found) {
                        $locks_use = $lr_found;
                    }
                    else {
                        $locks_use = new \Arii\FocusBundle\Entity\LocksUse();
                        $lockuse_found = $em->getRepository("AriiFocusBundle:Locks")->findOneBy(array('spooler'=>$spooler_id, 'path' => $name)); 
                        if ($lockuse_found) {
                            $locks_use->setLock($lockuse_found);
                        }
                        $locks_use->setJob($jobs);
                        $locks_use->setName($name);
                    }
                    $locks_use->setSpooler($spoolers);
                    $locks_use->setExclusive(isset($LRInfo['attr']['is_exclusive']) and ($LRInfo['attr']['is_exclusive']=='yes')?TRUE:FALSE);
                    $locks_use->setIsMissing(isset($LRInfo['attr']['is_missing']) and ($LRInfo['attr']['is_missing']=='yes')?TRUE:FALSE);
                    $locks_use->setUpdated($update_time);
                    
                    // On retrouve le lock
                    if (isset($LockEntity[$name])) {
                        $locks_use->setLock($LockEntity[$name]);
                    }
                    
                    $em->persist($locks_use);
                    $nl++;
                }
            }
            $em->flush();
            $n++;        
        }
        $em->flush();
        
        //===============================================================
        // Job chains
        //===============================================================
        if (isset($Result['spooler']['answer']['state']['job_chains']['job_chain'])) {
            $JCAttr = $Result['spooler']['answer']['state']['job_chains'];
            // verifier le cas ou il y a un seul job chain
            $n = 0;
            if (!isset($JCAttr['job_chain'][$n])) {
                $JCAttr['job_chain'][$n] = $JCAttr['job_chain'];
            }
            while (isset($JCAttr['job_chain'][$n])) {
                $JCInfo = $JCAttr['job_chain'][$n];

                // Le process_class existe ?
                $path = $JCInfo['attr']['path']; 
                $jc_found = $em->getRepository("AriiFocusBundle:JobChains")->findOneBy(array('spooler'=>$spooler_id, 'path' => $path)); 
                if ($jc_found) {
                    $jc = $jc_found;
                }
                else {
                    $jc = new \Arii\FocusBundle\Entity\JobChains();
                    $jc->setPath($path);
                }
                $jc->setSpooler($spoolers);
                $jc->setName(isset($JCInfo['attr']['name'])?$JCInfo['attr']['name']:NULL);
                $jc->setState($JCInfo['attr']['state']);
                $jc->setTitle(isset($JCInfo['attr']['title'])?$JCInfo['attr']['title']:'');
                $jc->setOrdersRecoverable(isset($JCInfo['attr']['orders_recoverable']) and ($JCInfo['attr']['orders_recoverable']=='yes')?TRUE:FALSE );
                $jc->setRunningOrders($JCInfo['attr']['running_orders']);
                $jc->setMaxOrders(isset($JCInfo['attr']['max_orders'])?$JCInfo['attr']['max_orders']:99999);
                
                $jc->setLastWriteTime( new \DateTime($JCInfo['file_based']['attr']['last_write_time']));
                $jc->setUpdated($update_time);
                $em->persist($jc);
                $em->flush();
                
                $job_chain_id = $jc->getId();
                
                // Job chain nodes
                $nn=0;
                // il y en a au moins 1
                // non pas toujours, on a des job_chains sans noeud
                if (!isset($JCAttr['job_chain'][$n]['job_chain_node'][$nn])) {
                    print "(((((".$JCInfo['attr']['name']."))))))";
                    $JCAttr['job_chain'][$n]['job_chain_node'][$nn] = $JCAttr['job_chain'][$n]['job_chain_node'];
                }
                while (isset($JCAttr['job_chain'][$n]['job_chain_node'][$nn])) {
                    $JCNInfo = $JCAttr['job_chain'][$n]['job_chain_node'][$nn];
                    
                    $state = $JCNInfo['attr']['state'];
                    $jcn_found = $em->getRepository("AriiFocusBundle:JobChainNodes")->findOneBy(array('job_chain'=>$job_chain_id, 'state' => $state)); 
                    if ($jcn_found) {
                        $jcn = $jcn_found;
                    }
                    else {
                        $jcn = new \Arii\FocusBundle\Entity\JobChainNodes();
                        $jcn->setState($state);
                    }
                    $jcn->setSpooler($spoolers);
                    $jcn->setTitle(isset($JCNInfo['attr']['title'])?$JCNInfo['attr']['title']:'');
                    $jcn->setNextState(isset($JCNInfo['attr']['next_state'])?$JCNInfo['attr']['next_state']:NULL);
                    $jcn->setErrorState(isset($JCNInfo['attr']['error_state'])?$JCNInfo['attr']['error_state']:NULL);
                    $jcn->setAction(isset($JCNInfo['attr']['action'])?$JCNInfo['attr']['action']:NULL);
                    $jcn->setJobChain($jc);
                    
                    // On retrouve le job
                    if (isset($JCNInfo['attr']['job'])) {
                        $job = $JCNInfo['attr']['job'];
                        if (isset($JOBEntity[$job])) {
                            $jcn->setJob($JOBEntity[$job]);                        
                        }
                    }
                    
                    $jcn->setUpdated($update_time);
                    $em->persist($jcn);
                    
                    $nn++;
                    
                    // Maintenant on recherche les ordres
                    if (isset($JCNInfo['order_queue']['order'])) {
                        // on reprend l'id du job_chain_node
                        $jcn_id = $jcn->getId();
                        
                        $OrderAttr = $JCNInfo['order_queue']['order'];
                        $no = 0;
                        if (!isset($OrderAttr[$no])) {
                            $OrderAttr[$no] = $OrderAttr;
                        }
                        while (isset($OrderAttr[$no])) {
                            $OrderInfo = $OrderAttr[$no];
                            $id = $OrderInfo['attr']['id'];
                            $order_found = $em->getRepository("AriiFocusBundle:Orders")->findOneBy(array('job_chain_node'=>$jcn_id, 'id' => $id)); 
                            if ($order_found) {
                                $order = $order_found;
                            }
                            else {
                                $order = new \Arii\FocusBundle\Entity\Orders();
                                $order->setName($id);
                                $order->setJobChainNode($jcn); 
                            }
                            $order->setJobChain($jc);
                            $order->setSpooler($spoolers); 

//                            $order->setHistoryId($OrderInfo['attr']['history_id']); 
//                            $order->task_id
                            $order->setState($OrderInfo['attr']['state']); 
                            $order->setTitle(isset($OrderInfo['attr']['title'])?$OrderInfo['attr']['title']:''); 
                            $order->setNextStartTime(isset($OrderInfo['attr']['next_start_time'])? $this->DateManager($OrderInfo['attr']['next_start_time']):NULL); 
                            $order->setInitialState($OrderInfo['attr']['initial_state']); 
                            $order->setEndState(isset($OrderInfo['attr']['end_state'])?$OrderInfo['attr']['end_state']:NULL); 
                            $order->setPriority($OrderInfo['attr']['priority']); 
                            $order->setCreated(new \DateTime($OrderInfo['attr']['created'])); 
                            $order->setStartTime(isset($OrderInfo['attr']['start_time'])?new \DateTime($OrderInfo['attr']['start_time']):NULL); 
                            $order->setSuspended(isset($OrderInfo['attr']['suspended']) and ($OrderInfo['attr']['suspended']=='yes')?TRUE:FALSE); 
                            $order->setInProcessSince(isset($OrderInfo['attr']['in_process_since'])?new \DateTime($OrderInfo['attr']['in_process_since']):NULL); 
                            $order->setTouched(isset($OrderInfo['attr']['touched'])?$OrderInfo['attr']['touched']:NULL); 

                            $order->setLastWriteTime(isset($OrderInfo['file_based']['attr']['last_write_time'])?new \DateTime($OrderInfo['file_based']['attr']['last_write_time']):NULL); 
                                    
                            $order->setUpdated($update_time);
                            $em->persist($order);
                            $no++;
                        }
                        $em->flush();
                    }
                    
                }
                $em->flush();
                $n++;
            }
        }
        $em->flush();        
        
        //===============================================================
        // Remote schedulers
        //===============================================================
        if (isset($Result['spooler']['answer']['state']['remote_schedulers'])) {
            $RSAttr = $Result['spooler']['answer']['state']['remote_schedulers'];
            // verifier le cas ou il y a un seul job
            $nrs = 0;

            while (isset($RSAttr['remote_scheduler'][$nrs])) {
                $RSInfo = $RSAttr['remote_scheduler'][$nrs];

                // Le process_class existe ?
                $id = $RSInfo['attr']['scheduler_id'];
                $rs_found = $em->getRepository("AriiFocusBundle:RemoteSchedulers")->findOneBy(array('spooler'=>$spooler_id, 'name' => $id)); 
                if ($rs_found) {
                    // on conserve l'entite
                    $remote_schedulers = $rs_found;
                }
                else {
                    $remote_schedulers = new \Arii\FocusBundle\Entity\RemoteSchedulers();
                    $remote_schedulers->setSpooler($spoolers);            
                    $remote_schedulers->setName($id);
                        
                    // On recherche le spooler ?
                    // $remote_schedulers->setSpooler($spoolers);
                }

                $remote_schedulers->setConnected(isset($RSInfo['attr']['connected']) and ($RSInfo['attr']['connected']=='yes')?TRUE:FALSE);            
                $remote_schedulers->setConnectedAt(isset($RSInfo['attr']['connected_at'])?new \DateTime($RSInfo['attr']['connected_at']):NULL);
                $remote_schedulers->setDisconnectedAt(isset($RSInfo['attr']['disconnected_at'])?new \DateTime($RSInfo['attr']['disconnected_at']):NULL);

                $remote_schedulers->setConfigurationChanged(isset($RSInfo['attr']['configuration_changed']) and ($RSInfo['attr']['configuration_changed']=='yes')?TRUE:FALSE);            
                $remote_schedulers->setConfigurationChangedAt(isset($RSInfo['attr']['configuration_changed_at'])?new \DateTime($RSInfo['attr']['configuration_changed_at']):NULL);
                $remote_schedulers->setConfigurationTransferedAt(isset($RSInfo['attr']['configuration_transfered_at'])?new \DateTime($RSInfo['attr']['configuration_transfered_at']):NULL);

                $remote_schedulers->setHostname(isset($RSInfo['attr']['hostname']));
                $remote_schedulers->setIp(isset($RSInfo['attr']['ip']));
                $remote_schedulers->setTcpPort(isset($RSInfo['attr']['tcp_port']));
                $remote_schedulers->setVersion(isset($RSInfo['attr']['version']));

                $remote_schedulers->setErrorCode(isset($RSInfo['ERROR']['attr']['code']));
                $remote_schedulers->setErrorCode(isset($RSInfo['ERROR']['attr']['text']));
                $remote_schedulers->setErrorCode(isset($RSInfo['ERROR']['attr']['time']));

                $remote_schedulers->setUpdated($update_time);
                $em->persist($remote_schedulers);
                $nrs++;
            }
            $em->flush();
        }
        
        //===============================================================
        // Connections
        //===============================================================
        if (isset($Result['spooler']['answer']['state']['connections'])) {
            $ConnAttr = $Result['spooler']['answer']['state']['connections'];

            // verifier le cas ou il y a un seul job
            $nc = 0;

            while (isset($ConnAttr['connection'][$nc])) {
                $ConnInfo = $ConnAttr['connection'][$nc];

                // Connexion en cours
                $ip = $ConnInfo['peer']['attr']['host_ip'];
                $port = $ConnInfo['peer']['attr']['host_ip'];
                $conn_found = $em->getRepository("AriiFocusBundle:Connections")->findOneBy(array('hostIp'=>$ip, 'port' => $port)); 
                if ($conn_found) {
                    // on conserve l'entite
                    $connections = $conn_found;
                }
                else {
                    $connections = new \Arii\FocusBundle\Entity\Connections();
                    $connections->setHostIp($ip);
                    $connections->setPort($port);
                        
                    // On recherche le spooler ?
                    // $remote_schedulers->setSpooler($spoolers);
                }
                $connections->setSpooler($spoolers);            

                $connections->setOperationType($ConnInfo['attr']['operation_type']);            
                $connections->setReceivedBytes($ConnInfo['attr']['received_bytes']);            
                $connections->setSendBytes($ConnInfo['attr']['sent_bytes']);            
                $connections->setResponses($ConnInfo['attr']['responses']); 
                $connections->setState($ConnInfo['attr']['state']); 
                
                $connections->setUpdated($update_time);
                $em->persist($connections);
                $nc++;
            }
            $em->flush();
        }

        // Purge global
        // Attention a n'effacer que les tables mises a jour (donc dans le show state)
        $this->Purge($em,"AriiFocusBundle:Locks",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:LocksUse",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:Tasks",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:ProcessClasses",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:JobChainNodes",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:JobChains",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:Jobs",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:Orders",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:RemoteSchedulers",$spooler_id,$update_time);
        $this->Purge($em,"AriiFocusBundle:Connections",$spooler_id,$update_time);
        $em->flush();

        if ($mode == 'debug') {
            print "<pre>";
            print_r($Result);
            print "</pre>";
        }
        
    exit();
    }
    
    private function DateManager($date) {
        $t = substr($date,10,1);
        if ($t=='T') { 
            return new \DateTime($date);
        }
        elseif ($t==' ') {
           // $date[10] = 'T';
            $date = substr($date,0,19);
            return new \DateTime($date, new \DateTimeZone("Europe/Paris"));
        }
        
        return;
    }
    
    private function Purge($em,$entity,$spooler,$time) {
        $Result = $em->createQuery(
                'SELECT p FROM '.$entity.' p WHERE p.spooler = :spooler and p.updated < :time'
            )
            ->setParameter('spooler', $spooler)
            ->setParameter('time', $time)    
            ->getResult();
        // print_r($Result);
        foreach ($Result as $p) {
            $em->remove($p);
        }
    }

}