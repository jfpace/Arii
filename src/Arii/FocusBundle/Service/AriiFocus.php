<?php
// src/Sdz/BlogBundle/Service/AriiSQL.php
 
namespace Arii\FocusBundle\Service;

class AriiFocus
{
    protected $debug;
    protected $mode;
    protected $tools;
    protected $db;
    
    public function __construct( \Arii\CoreBundle\Service\AriiTools $tools,  \Arii\CoreBundle\Service\AriiDB $db ) {
        $this->tools = $tools;
        $this->db = $db;
    }

    public function get($spooler='localhost',$port='4444',$what='orders,job_chain_orders,job_orders,jobs,job_chains,schedules,remote_schedulers,payload,job_params',$mode="WEB",$debug=1) {
        set_time_limit ( 900 );
        $f= @fopen("http://$spooler:$port/%3Cshow_state%20what=%22$what%22/%3E","r");
        if (!$f) {
            print "$spooler:$port !";
            exit();
        }
        $data = '';
        while(!feof($f)) {
            $data .= fread($f,10240);
        }
        fclose($f);
        return $this->cache($data,$mode,$debug);
    }

    public function cache($data, $mode='',$debug) {   
        $this->PrintMessage(2,time());
        $this->debug = $debug;
        $this->mode = $mode;
        $maxtime = 900;
        set_time_limit ( $maxtime );
        $Result = $this->tools->xml2array( $data , 1, 'attributes');
        if (!isset($Result['spooler'])) {
            print "spooler inconnu ?!";
            exit();
        }
        
        $timer = microtime(true);
        $timestamp = time();
        $Queries = array();

        // on utilise le timer pour sortir au bout de 30 s
        // on commence donc par le plus prioritaire
        $data = $this->db->Connector('data');

        // Reponse du scheduler
        if (!isset($Result['spooler']['answer']['state'])) {
            print "ANSWER ?!";
            exit();
        } 
        $Scheduler = $Result['spooler']['answer']['state']['attr'];
        $host = $Scheduler['host'];
        $spooler = $Scheduler['id'];
        $this->PrintMessage(2,"SPOOLER: $spooler");
        $time = $Scheduler['time'];
        $spooler_running_since = $Scheduler['spooler_running_since'];
        $state = $Scheduler['state'];
        $log_file = $Scheduler['log_file'];
        $version = $Scheduler['version'];
        $pid = $Scheduler['pid'];
        $config_file = $Scheduler['config_file'];
        $host = $Scheduler['host'];
        if (isset($Scheduler['ip_address'])) 
            { $ip_address = $Scheduler['ip_address']; }
            else 
            { $ip_address = $host; }
        $tcp_port = $Scheduler['tcp_port'];
        $udp_port = $Scheduler['udp_port'];
        $db = $Scheduler['db'];
        $waits = $Scheduler['waits'];
        $loops = $Scheduler['loop'];
        if (isset($Scheduler['wait_until'])) { $wait_until = $Scheduler['wait_until']; }
            else { $wait_until = 'null'; }
        if (isset($Scheduler['need_db']) and ($Scheduler['need_db']=='yes')) { $need_db = 1; }
            else { $need_db=0; };

        // On recupere l'id du spooler par rapport a l'adresse ip et au port
        // On a besoin des spoolers de la base pour connecter les remotes
        $qry = 'select fs.ID,fs.IP_ADDRESS,fs.HOST,fs.TCP_PORT,fs.SPOOLER_ID as SPOOLER_ID,fs.UPDATED,c.INTERFACE,c.PORT,c.ENTERPRISE_ID '
                . 'from FOCUS_SPOOLERS fs '
                . 'left join ARII_SPOOLER s '
                . 'on fs.spooler_id=s.id '
                . 'left join ARII_CONNECTION c '
                . 'on s.connection_id=c.id ';
        //      . 'where fs.IP_ADDRESS="'.$ip_address.'" and fs.TCP_PORT='.$tcp_port; 
        $id = "$ip_address#$tcp_port";
        $this->PrintMessage(1,"ID: $id"); 
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $spooler_id = $line['ID'];
            $spooler_ip = $line['IP_ADDRESS'];
            $spooler_port = $line['TCP_PORT'];
            $sid = "$spooler_ip#$spooler_port";
            // spooler en cours
            if ($id==$sid) {
                $this->PrintMessage(2,"SID: $sid => $spooler_id");
                $updated = time()- (int) $line['UPDATED'];
                if ($updated<5) {
                    print "Last update $updated s";
                    return 1;
                }
                elseif ($updated>600) {
                    $this->PrintMessage(1,"Last update: $updated s !!!");                
                }
            }
            $SpoolerID[$sid] = $line['ID'];   
            $spooler_host = $line['HOST'];
            $SpoolerHostID["$spooler_host#$spooler_port"] = $line['ID'];    
            
            $arii_ip = $line['INTERFACE'];
            $arii_port = $line['PORT'];
            $arii_spooler = $line['SPOOLER_ID'];
            $enterprise_id = $line['ENTERPRISE_ID'];
            $aid = "$arii_ip#$arii_port";
            $AriiID[$aid] = $line['SPOOLER_ID'];
            if ($aid==$id) {
                $this->PrintMessage(2,"AID: $aid => ".$AriiID[$aid]);
                // $arii_spooler = $line['SPOOLER_ID'];            
                $this->PrintMessage(2,"Spooler ID: $spooler_id");
                $this->PrintMessage(2,"  Interface: $arii_ip");
                $this->PrintMessage(2,"  Port: $arii_port");
                $this->PrintMessage(2,"  Arii spooler: $arii_spooler");
                $this->PrintMessage(2,"  Enterprise: $enterprise_id");
            }
            
        }
        
        if (isset($AriiID[$id])) {
            $arii_spooler = $Arii[$id];
        }
        else {
            $arii_spooler = 'null';
        }
        
        // Si pas de spooler, on l'ajoute 
        if (isset($SpoolerID[$id])) {
            $spooler_id = $SpoolerID[$id];
            // si on a un spooler_id, on ajoute l'update dans le tableau des queries
            $qry = 'update FOCUS_SPOOLERS set TIME="'.$this->CorrectDatetime($time).'",'
                    . 'NAME="'.$spooler.'",'
                    . 'SPOOLER_RUNNING_SINCE="'.$this->CorrectDatetime($spooler_running_since).'",'
                    . 'STATE="'.$state.'",'
                    . 'VERSION="'.$version.'",'
                    . 'PID='.$pid.','
                    . 'TCP_PORT='.$tcp_port.',UDP_PORT='.$tcp_port.','
                    . 'HOST="'.$host.'",IP_ADDRESS="'.$ip_address.'",'
                    . 'NEED_DB='.$need_db.',DB="'.$db.'",'
                    . 'LOOPS='.$loops.','
                    . 'WAITS="'.$waits.'",WAIT_UNTIL="'.$this->CorrectDatetime($wait_until).'",'
                    . 'SPOOLER_ID='.$arii_spooler.','
                    . 'UPDATED='.$timestamp
                    . ' where id='.$spooler_id;
            array_push($Queries,$qry);
        }        
        else {
            $spooler_id=-1;
            // on insert un nouveau 
            // $qry = 'insert into FOCUS_SPOOLERS (NAME,HOST,IP_ADDRESS,TCP_PORT,CONNECTION,SPOOLER_RUNNING_SINCE,STATE,VERSION,UPDATED,LOG_FILE,PID,NEED_DB,UDP_PORT,CONFIG_FILE,DB,TIME,WAITS,WAIT_UNTIL,LOOPS) values( "'.$spooler.'","'.$host.'","'.$ip_address.'","'.$tcp_port.'","","","","","","","","","","","","","","","")';
            $qry = 'insert into FOCUS_SPOOLERS (NAME,HOST,IP_ADDRESS,TCP_PORT) values( "'.$spooler.'","'.$host.'","'.$ip_address.'","'.$tcp_port.'")';
            $res = $data->sql->query( $qry );
            // on recuperer a nouveau l'id
            $qry = 'select ID from FOCUS_SPOOLERS where IP_ADDRESS="'.$ip_address.'" and TCP_PORT="'.$tcp_port.'"'; 
            $res = $data->sql->query( $qry );
            $line = $data->sql->get_next($res);
            if ($line) {
                $spooler_id = $line['ID'];
                // $arii_spooler = $line['SPOOLER_ID'];            
                $this->PrintMessage(2,"Spooler ID: $spooler_id");
            }
        }     
/* A VOIR        
        // si le spooler arii n'est pas defini ou si on est en mode supervisor, 
        // on prend les connexions
        // on reste dans l'entreprise (A VOIR AVEC l'AUTHENTIFICATION)
        $arii_spooler='null';
//        if($enterprise_id!='') {
            if (isset($Result['spooler']['answer']['state']['remote_schedulers']['remote_scheduler'])) {
                $qry = 'select s.ID,c.INTERFACE,c.PORT from ARII_CONNECTION c '
                        . 'left join ARII_SPOOLER s '
                        . 'on c.id=s.connection_id '
                        . 'where c.NETWORK_ID=2';
                // and c.enterprise_id='.$enterprise_id; 
                $res = $data->sql->query( $qry );
                while ($line = $data->sql->get_next($res)) {
                    $id = $line['ID'];
                    $ip = $line['INTERFACE']; 
                    $port = $line['PORT'];
                    $SpoolerID["$ip#$port"] = $id;
                }
                if (isset($SpoolerID["$ip_address#$tcp_port"])) {
                    $arii_spooler = $SpoolerID["$ip_address#$tcp_port"];
                }
                $this->PrintMessage(2,"Arii Spooler: $arii_spooler");
            }
            elseif ($arii_spooler=='null') {
                $qry = 'select s.ID from ARII_CONNECTION c '
                        . 'left join ARII_SPOOLER s '
                        . 'on c.id=s.connection_id '
                        . 'where c.NETWORK_ID=2 and c.INTERFACE="'.$ip_address.'" and c.PORT="'.$tcp_port.'"';
                //  and c.ENTERPRISE_ID='.$enterprise_id; 
                $res = $data->sql->query( $qry );
                $line = $data->sql->get_next($res);
                if ($line) {
                    $arii_spooler = $line['ID'];
                    $this->PrintMessage(2,"Arii Spooler: $arii_spooler");
                }
            }
//        }
*/        

        // On fait une image des jobs
        $qry = 'select ID,PATH,CRC from FOCUS_JOBS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_job=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $job =  $line['PATH'];
                $JobID[$job] = $id;
                $JobCRC[$job] = $line['CRC'];
                $JobDel[$job] = $id;
                $JobName[$id] = $job;
                $c_job++;
        }
        $this->PrintMessage(2,"Jobs: $c_job");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));

        // on fait une image des taches
        $qry = 'select ID,TASK,CRC from FOCUS_TASKS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_task=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $task =  $line['TASK'];
                $TaskID[$task] = $id;
                $TaskCRC[$task] = $line['CRC'];
                $TaskDel[$task] = $id;
                $c_task++;
        }
        $this->PrintMessage(2,"Tasks: $c_task");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));
       
        // image des verrous
        $qry = 'select ID,PATH,CRC from FOCUS_LOCKS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_lock=0;
        while ( $line = $data->sql->get_next($res) ) {
                $lock  =  $line['PATH'];
                $id  =  $line['ID'];
                $LockID[$lock]=$id;
                $LockCRC[$lock]=$line['CRC'];
                $LockDel[$lock]= $id;                
                $c_lock++;
        }
        $this->PrintMessage(2,"Locks: $c_lock");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));        

        // on fait une image des utilisations de verrous
        $qry = 'select ID,NAME,LOCK_ID,JOB_ID,CRC from FOCUS_LOCKS_USE where spooler_id='.$spooler_id;
        $res = $data->sql->query( $qry );
        $c_lockuse=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $lock =  $line['NAME'];
                $job_id =  $line['JOB_ID'];
                $lock_id =  $line['LOCK_ID'];
                if ($lock_id=='') { $lock_id='null'; };
                $lockjob = $lock_id.'#'.$job_id;
                $LockUseID[$lockjob] = $id;
                $LockUseCRC[$lockjob] =$line['CRC'];
                $LockUseDel[$lockjob] = $id;
                $c_lockuse++;
        }
        $this->PrintMessage(2,"Locks use: $c_lockuse");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));

        // image des schedules
        $qry = 'select ID,PATH,CRC from FOCUS_SCHEDULES where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_schedule=0;
        while ( $line = $data->sql->get_next($res) ) {
                $lock  =  $line['PATH'];
                $id  =  $line['ID'];
                $ScheduleID[$lock]=$id;
                $ScheduleCRC[$lock]=$line['CRC'];
                $ScheduleDel[$lock]= $id;                
                $c_schedule++;
        }
        $this->PrintMessage(2,"Schedules: $c_schedule");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));        

        // image des periodes
        $qry = 'select ID,SCHEDULE_ID,DATE,MONTHS,DAYS,START_TIME,CRC from FOCUS_PERIODS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_period=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $period  =  $line['SCHEDULE_ID'].'|'.$line['DATE'].'|'.$line['MONTHS'].'|'.$line['DAYS'].'|'.$line['START_TIME'];
                $PeriodID[$period]=$id;
                $PeriodCRC[$period]=$line['CRC'];
                $PeriodDel[$period]= $id;                
                $c_period++;
        }
        $this->PrintMessage(2,"Periods: $c_period");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));        

        // Image des job_chains
        $qry = 'select ID,PATH,CRC from FOCUS_JOB_CHAINS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_jobchain=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $name =  $line['PATH'];
                $JobChainID[$name] = $id;
                $JobChainCRC[$name] = $line['CRC'];
                $JobChainDel[$name] = $id;
                $JobChainName[$id] = $name;
                $c_jobchain++;
        }
        $this->PrintMessage(2,"Job chains: $c_jobchain");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));

        // Puis les job_chain_nodes 
        $qry = 'select ID,JOB_CHAIN_ID,STATE,CRC from FOCUS_JOB_CHAIN_NODES where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_jobchainnode=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $job_chain_id = $line['JOB_CHAIN_ID'];
                // le job chain est present
                if ($job_chain_id!='') {
                    $node =  $job_chain_id.'/'.$line['STATE'];
                    $NodeID[$node] = $id;
                    $NodeCRC[$node] = $line['CRC'];
                    $NodeDel[$node] = $id;
                    $c_jobchainnode++;
                }
        }
        $this->PrintMessage(2,"Job chain nodes: $c_jobchainnode");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));

        // et enfin les ordres
        $qry = 'select ID,PATH,CRC from FOCUS_ORDERS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_order=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $order =  $line['PATH'];
                $OrderID[$order] = $id;
                $OrderCRC[$order] = $line['CRC'];
                $OrderDel[$order] = $id;
                $c_order++;
        }
        $this->PrintMessage(2,"Orders: $c_order");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));

        // image des parametres
        $qry = 'select ID,ORDER_ID,NAME,CRC from FOCUS_PAYLOADS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_payload=0;
        while ( $line = $data->sql->get_next($res) ) {
                $payload  =  $line['ORDER_ID'].'/'.$line['NAME'];
                $id  =  $line['ID'];
                $PayloadID[$payload]=$id;
                $PayloadCRC[$payload]=$line['CRC'];
                $PayloadDel[$payload]= $id;                
                $c_payload++;
        }
        $this->PrintMessage(2,"Payloads: $c_payload");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));        

        // image des parametres
        $qry = 'select ID,JOB_ID,NAME,CRC from FOCUS_JOB_PARAMS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_params=0;
        while ( $line = $data->sql->get_next($res) ) {
                $params =  $line['JOB_ID'].'/'.$line['NAME'];
                $id  =  $line['ID'];
                $ParamsID[$params]=$id;
                $ParamsCRC[$params]=$line['CRC'];
                $ParamsDel[$params]= $id;                
                $c_params++;
        }
        $this->PrintMessage(2,"Params: $c_params");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));        

        // et file order source
        $qry = 'select ID,DIRECTORY,REGEX,CRC from FOCUS_FILE_ORDERS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_file=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $file =  str_replace('\\','\\\\',$line['DIRECTORY'].'/'.$line['REGEX']);
                $FileID[$file] = $id;
                $FileCRC[$file] = $line['CRC'];
                $FileDel[$file] = $id;
                $c_file++;
        }
        $this->PrintMessage(2,"File orders: $c_file");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));
 
        
        // images des process class
        $qry = 'select ID,PATH,CRC from FOCUS_PROCESS_CLASSES where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_process=0;
        while ( $line = $data->sql->get_next($res) ) {
                $process  =  $line['PATH'];
                $id  =  $line['ID'];
                $ProcessClassID[$process]=$id;
                $ProcessClassCRC[$process]=$line['CRC'];
                $ProcessClassDel[$process]=$id;
                $c_process++;
        }
        $this->PrintMessage(2,"Process classes: $c_process");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));

        // images des connections
        $qry = 'select ID,HOST_IP,PORT,CRC from FOCUS_CONNECTIONS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_connect=0;
        $ConnectionID=array();
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $host  =  $line['HOST_IP'];
                $port  =  $line['PORT'];
                $connect = "$host#$port";
                $ConnectionID[$connect]=$id;
                $ConnectionCRC[$connect]=$line['CRC'];
                $ConnectionDel[$connect]=$id;
                $c_connect++;
        }
        // print_r($ConnectionID);
        $this->PrintMessage(2,"Connections: $c_connect");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));

        // images des order_id_space
/* Ca sert ?
     if (isset($Result['spooler']['answer']['state']['order_id_spaces']['order_id_space'])) {
        $qry = 'select ID,NAME,JOB_CHAIN_ID,CRC from FOCUS_ORDER_ID_SPACES where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_space=0;
        $SpaceID=array();
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $name  =  $line['NAME'].'#'.$line['JOB_CHAIN_ID'];
                $SpaceID[$name]=$id;
                $SpaceCRC[$name]=$line['CRC'];
                $SpaceDel[$name]=$id;
                $c_space++;
        }
        $this->PrintMessage(2,"Spaces: $c_space");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));
    }
*/    
    if (isset($Result['spooler']['answer']['state']['remote_schedulers']['remote_scheduler'])) {
        // remote schedulers 
        $qry = 'select ID,IP,TCP_PORT,CRC from FOCUS_REMOTE_SCHEDULERS where spooler_id='.$spooler_id; 
        $res = $data->sql->query( $qry );
        $c_remote=0;
        while ( $line = $data->sql->get_next($res) ) {
                $id  =  $line['ID'];
                $connect = $line['IP'].'#'.$line['TCP_PORT'];
                $RemoteID[$connect]=$id;
                $RemoteCRC[$connect]=$line['CRC'];
                $RemoteDel[$connect]=$id;
                $c_remote++;
        }
       // print_r($RemoteID);
        $this->PrintMessage(2,"Remote schedulers: $c_remote");
        $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));
    }


        //=JOBS/LOCKS USE================================================================================================
    $hourglass = microtime(true)-$timer;
    if ($hourglass<$maxtime) {
        $n = 0;
        if (isset( $Result['spooler']['answer']['state']['jobs']['job'] )) {
            $Jobs = $Result['spooler']['answer']['state']['jobs']['job'];
            // Cas ou il n'y a qu'un seul job 
            if (isset($Result['spooler']['answer']['state']['jobs']['job']['attr'])) {
                    $Jobs[$n] = $Result['spooler']['answer']['state']['jobs']['job'];
            }
            while (isset($Jobs[$n])) {
                    $job_name = $Jobs[$n]['attr']['name'];
                    $path  = $Jobs[$n]['attr']['path'];
                    $state = $Jobs[$n]['attr']['state'];
                    $all_steps = ( isset($Jobs[$n]['attr']['all_steps']) ? $Jobs[$n]['attr']['all_steps'] : 0);	
                    $all_tasks = ( isset( $Jobs[$n]['attr']['all_tasks']) ? $Jobs[$n]['attr']['all_tasks'] : 0 );	
                    if (isset($Jobs[$n]['attr']['order']) and ($Jobs[$n]['attr']['order'] == 'yes' )) {
                         $ordered = 1;
                    }
                    else {
                         $ordered = 0;
                    }
                    $job_chain_priority = ( isset($Jobs[$n]['attr']['job_chain_priority']) ? $Jobs[$n]['attr']['job_chain_priority'] : -1 );	
                    # BUG Jobscheduler
                    if ($job_chain_priority>=0) 
                        $ordered = 1;
                    $tasks = ( isset($Jobs[$n]['attr']['tasks']) ? $Jobs[$n]['attr']['tasks'] : 0 );	
                    if (isset($Jobs[$n]['attr']['in_period']) and ($Jobs[$n]['attr']['in_period']=='yes')) { $in_period = 1; }
                        else { $in_period = 0; }
                    if (isset($Jobs[$n]['attr']['enabled']) and ($Jobs[$n]['attr']['enabled']=='yes')) { $enabled = 1; }
                        else { $enabled = 0; }
                    if (isset($Jobs[$n]['attr']['has_description']) and ($Jobs[$n]['attr']['has_description']=='yes')) { $has_description = 1; }
                        else { $has_description = 0; }
                    if (isset($Jobs[$n]['attr']['waiting_for_process']) and ($Jobs[$n]['attr']['waiting_for_process']=='yes')) { $waiting_for_process = 1; }
                        else { $waiting_for_process = 0; }
                    $next_start_time = ( isset($Jobs[$n]['attr']['next_start_time']) ? '"'.$this->CorrectDatetime($Jobs[$n]['attr']['next_start_time']).'"' : 'null');	
                    $process_class = ( isset($Jobs[$n]['attr']['process_class']) ? $Jobs[$n]['attr']['process_class'] : '');	
                    if (isset($ProcessClassID[$process_class])) 
                        $process_class_id = $ProcessClassID[$process_class];
                    else 
                        $process_class_id = 'null';
                    $schedule = ( isset($Jobs[$n]['attr']['active_schedule']) ? $Jobs[$n]['attr']['active_schedule'] : '');	
                    if (isset($ScheduleID[$schedule])) 
                        $schedule_id = $ScheduleID[$schedule];
                    else 
                        $schedule_id = 'null';
                    $title =  $this->TextProtect( isset($Jobs[$n]['attr']['title'])  ? $Jobs[$n]['attr']['title'] : '' );	
                    $last_write_time = '';
                    if (isset($Jobs[$n]['file_based']['attr']['last_write_time']))
                        { $last_write_time = $this->CorrectDatetime($Jobs[$n]['file_based']['attr']['last_write_time']);

                        }
                    $last_info = '';
                    if (isset($Jobs[$n]['log']['attr']['last_info'])) {
                            $last_info = $this->TextProtect($Jobs[$n]['log']['attr']['last_info']);	
                    }
                    $last_warning = '';
                    if (isset($Jobs[$n]['log']['attr']['last_warning'])) {
                            $last_warning = $this->TextProtect($Jobs[$n]['log']['attr']['last_warning']);	
                    }
                    $last_error = '';
                    if (isset($Jobs[$n]['log']['attr']['last_error'])) {
                            $last_error = $this->TextProtect($Jobs[$n]['log']['attr']['last_error']);	
                    }
                    $level =  ( isset($Jobs[$n]['log']['attr']['level'])  ? $Jobs[$n]['log']['attr']['level'] : '' );	
                    $highest_level =  ( isset($Jobs[$n]['log']['attr']['highest_level'])  ? $Jobs[$n]['log']['attr']['highest_level'] : '' );	
                    # Gestion des erreurs
                    $error = 0;
                    $error_code=$error_text='';
                    if (isset($Jobs[$n]['file_based']['ERROR'])) {
                            $error = 1;
                            $p = strpos($Jobs[$n]['file_based']['ERROR']['attr']['text'],' ');
                            $error_text = $this->TextProtect(substr($Jobs[$n]['file_based']['ERROR']['attr']['text'],$p+1));
                            $error_code = substr($Jobs[$n]['file_based']['ERROR']['attr']['text'],0,$p);
                    }

                    # ID COMPLET
                    // Si l'ID existe, on update
                    // Sinon on insert
                    if (isset($JobID[$path])) { $job_id = $JobID[$path]; }
                        else { $job_id = 'null'; }
                    $update = 'SPOOLER_NAME="'.$spooler.'",NAME="'.$job_name.'",PATH="'.$path.'",STATE="'.$state.'",ALL_STEPS='.$all_steps.',ALL_TASKS='.$all_tasks.',ORDERED='.$ordered.',TASKS='.$tasks.',IN_PERIOD='.$in_period.',ENABLED='.$enabled.',LAST_WRITE_TIME="'.$last_write_time.'",LAST_INFO="'.$last_info.'",LAST_WARNING="'.$last_warning.'",LAST_ERROR="'.$last_error.'",HAS_DESCRIPTION='.$has_description.',TITLE="'.$title.'",NEXT_START_TIME='.$next_start_time.',PROCESS_CLASS_ID='.$process_class_id.',PROCESS_CLASS_NAME="'.$process_class.'",SCHEDULE_ID='.$schedule_id.',SCHEDULE_NAME="'.$schedule.'",WAITING_FOR_PROCESS='.$waiting_for_process.',ERROR="'.$error.'",ERROR_CODE="'.$error_code.'",ERROR_TEXT="'.$error_text.'",LEVEL="'.$level.'",HIGHEST_LEVEL="'.$highest_level.'",JOB_CHAIN_PRIORITY='.$job_chain_priority;
                    $crc = hash('crc32',$update);
                    
                    $job_params = -1;
                    if (isset($JobID[$path])) {
                            $JobDel[$path]=-1;
                            $job_params = $JobID[$path];
                            if ($JobCRC[$path] != $crc ) {
                                $qry = 'update FOCUS_JOBS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$JobID[$path];
                                array_push($Queries, $qry);
                            }
                    }
                    else {
                            $qry = 'insert into FOCUS_JOBS (SPOOLER_ID,SPOOLER_NAME,NAME,PATH,STATE,ALL_STEPS,ALL_TASKS,ORDERED,TASKS,IN_PERIOD,ENABLED,LAST_WRITE_TIME,LAST_INFO,LAST_WARNING,LAST_ERROR,HAS_DESCRIPTION,TITLE,NEXT_START_TIME,PROCESS_CLASS_ID,PROCESS_CLASS_NAME,SCHEDULE_ID,SCHEDULE_NAME,WAITING_FOR_PROCESS,ERROR,ERROR_CODE,ERROR_TEXT,LEVEL,HIGHEST_LEVEL,JOB_CHAIN_PRIORITY,UPDATED,CRC)'
                                    . ' values ( '.$spooler_id.', "'.$spooler.'", "'.$job_name.'", "'.$path.'","'.$state.'", '.$all_steps.', '.$all_tasks.', '.$ordered.', '.$tasks.', '.$in_period.', '.$enabled.', "'.$last_write_time.'", "'.$last_info.'", "'.$last_warning.'", "'.$last_error.'", "'.$has_description.'", "'.$title.'", '.$next_start_time.', '.$process_class_id.', "'.$process_class.'", '.$schedule_id.', "'.$schedule.'",'.$waiting_for_process.', '.$error.', "'.$error_code.'", "'.$error_text.'", "'.$level.'", "'.$highest_level.'",'.$job_chain_priority.','.$timestamp.',"'.$crc.'" )';
                            array_push($Queries, $qry);
                    }
                    if (($job_params>=0) and (isset($Jobs[$n]['params']))) {
                        $JobParams =$Jobs[$n]['params'];                        
                         // si  ce n'est pas un tableau, on le transforme
                         // on doit avoir le nombre
                         $params_count = $JobParams['attr']['count'];
                         for($i=1;$i<=$params_count;$i++) {
                             $np = $i-1;
                             if (isset($JobParams['param']['attr']))
                                 $P = $JobParams['param']['attr'];
                             else 
                                 $P = $JobParams['param'][$np]['attr'];
                             $name = $P['name'];
                             $value = $this->TextProtect($P['value']);
                             $param = $job_params.'/'.$name;
                             $update = 'SPOOLER_ID='.$spooler_id.',JOB_ID='.$job_params.',NAME="'.$name.'",VALUE="'.$value.'"';
                             $crc = hash('crc32',$update);
                             if (isset($ParamsID[$param])) {
                                     $ParamsDel[$param]=-1;
                                     if ($ParamsCRC[$param]!=$crc) {
                                         $qry = 'update FOCUS_JOB_PARAMS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$job_params;
                                         array_push($Queries, $qry);
                                     }
                             }
                             else {
                                     $qry = 'insert into FOCUS_JOB_PARAMS (SPOOLER_ID,JOB_ID,NAME,VALUE,UPDATED,CRC) values ( '.$spooler_id.','.$job_params.',"'.$name.'","'.$value.'",'.$timestamp.',"'.$crc.'" )';
                                     array_push($Queries, $qry);
                            }                                                            
                         }
                    }
                    
                    // Sauvegarde des taches
                    if (isset($Jobs[$n]['tasks']['task'])) {
                        $Tasks = $Jobs[$n]['tasks']['task'];
                            $nt = 0;
                            if (isset($Tasks['attr'])) {
                                    $Tasks[$nt] = $Jobs[$n]['tasks']['task'];
                            }
                            while (isset($Tasks[$nt])) {
                                    if (isset($Tasks[$nt]['attr'])) {
                                            $task_id = $Tasks[$nt]['attr']['id'];
                                            $state = $Tasks[$nt]['attr']['state'];
                                            $name = $Tasks[$nt]['attr']['name'];
                                            $running_since = $this->CorrectDatetime($Tasks[$nt]['attr']['running_since']);
                                            $enqueued = ( isset( $Tasks[$nt]['attr']['enqueued'] ) ? '"'.$this->CorrectDatetime($Tasks[$nt]['attr']['enqueued']).'"' : 'null' );
                                            $start_at = $this->CorrectDatetime($Tasks[$nt]['attr']['start_at']);
                                            $cause = $Tasks[$nt]['attr']['cause'];
                                            $steps = $Tasks[$nt]['attr']['steps'];
                                            $log_file = $Tasks[$nt]['attr']['log_file'];
                                            if (isset( $Tasks[$nt]['attr']['pid'])) 
                                                $pid = $Tasks[$nt]['attr']['pid'];
                                            else 
                                                $pid = 0;
                                            if (isset($Tasks[$nt]['attr']['priority']))
                                                $priority = $Tasks[$nt]['attr']['priority'];
                                            else 
                                                $priority = 0;
                                            $force_start = ( $Tasks[$nt]['attr']['force_start'] == 'yes' ? 1 : 0 );

                                            // identifiant
                                            $task = $task_id; $update = 'TASK='.$task_id.',STATE="'.$state.'",NAME="'.$name.'",JOB_ID='.$job_id.', RUNNING_SINCE="'.$running_since.'",ENQUEUED='.$enqueued.',START_AT="'.$start_at.'",CAUSE="'.$cause.'",STEPS='.$steps.',PID='.$pid.',PRIORITY="'.$priority.'",FORCE_START='.$force_start;
                                            $crc = hash('crc32',$update);

                                            if (isset($TaskID[$task])) {
                                                    $TaskDel[$task]=-1;
                                                    if ($TaskCRC[$task]!=$crc) {
                                                        $qry = 'update FOCUS_TASKS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$TaskID[$task];
                                                        array_push($Queries, $qry);
                                                    }
                                            }
                                            else {
                                                    $qry = 'insert into FOCUS_TASKS (TASK,SPOOLER_ID,JOB_ID,STATE,NAME,RUNNING_SINCE,ENQUEUED,START_AT,CAUSE,STEPS,PID,PRIORITY,FORCE_START,UPDATED,CRC) values ( '.$task_id.', '.$spooler_id.', '.$job_id.', "'.$state.'", "'.$name.'", "'.$running_since.'", '.$enqueued.', "'.$start_at.'", "'.$cause.'", "'.$steps.'", "'.$pid.'", "'.$priority.'", "'.$force_start.'",'.$timestamp.',"'.$crc.'" )';
                                                    array_push($Queries, $qry);
                                            }
                                            //	print $qry;
                                            //$res = $data->sql->query( $qry );
                                    }
                                    $nt++;
                            }
                    }

                    // Utilisation des verrous
                    if (isset($Jobs[$n]['lock.requestor'])) {
                            $Locks = $Jobs[$n]['lock.requestor'];
                            if (isset($Locks['lock.use'])) {
                                    if (isset($Locks['lock.use']['attr'])) {
                                            $Locks['lock.use'][0]['attr']['lock']      = $Locks['lock.use']['attr']['lock'];
                                            $Locks['lock.use'][0]['attr']['exclusive'] = $Locks['lock.use']['attr']['exclusive'];
                                            if (isset($Locks['lock.use']['attr']['is_missing'])) 
                                                $Locks['lock.use'][0]['attr']['is_missing'] = $Locks['lock.use']['attr']['is_missing'];
                                            if (isset($Locks['lock.use']['attr']['is_available'])) 
                                                $Locks['lock.use'][0]['attr']['is_available'] = $Locks['lock.use']['attr']['is_available'];
                                    }
                                    $nl = 0;
                                    // print_r($Locks['lock.use']);

                                    while (isset($Locks['lock.use'][$nl]['attr'])) {
                                            $lock = $Locks['lock.use'][$nl]['attr']['lock'];
                                            if (isset($LockID[$lock])) {
                                                $lock_id = $LockID[$lock];
                                            }
                                            else {
                                                $lock_id = 'null';
                                            }
                                            //$path = str_replace('\\','/',dirname($lock));
                                            //$name = basename($lock);
                                            if ( isset($Locks['lock.use'][$nl]['attr']['exclusive'] ) and ( $Locks['lock.use'][$nl]['attr']['exclusive'] == 'yes' ) )
                                                $exclusive = 1;
                                            else
                                                $exclusive = 0;
                                            if ( isset($Locks['lock.use'][$nl]['attr']['is_available'] ) and ( $Locks['lock.use'][$nl]['attr']['is_available'] == 'no' ) )
                                                $is_available = 0;
                                            else
                                                $is_available = 1;
                                            if ( isset($Locks['lock.use'][$nl]['attr']['is_missing'] ) and ( $Locks['lock.use'][$nl]['attr']['is_missing'] == 'yes' ) )
                                                $is_missing = 1;
                                            else
                                                $is_missing = 0;
                                            $lockjob = $lock_id.'#'.$job_id;
                                            $update = 'EXCLUSIVE='.$exclusive.',IS_AVAILABLE='.$is_available.',IS_MISSING='.$is_missing;
                                            // 

                                            $crc = hash('crc32',$update);
                                            if (isset($LockUseID[$lockjob])) {
                                                    $LockUseDel[$lockjob] = -1;
                                                    if ($LockUseCRC[$lockjob]!=$crc) {
                                                        $qry = 'update FOCUS_LOCKS_USE set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$LockUseID[$lockjob];
                                                        array_push($Queries, $qry);
                                                    }
                                            }
                                            else {
                                                    $qry = 'insert into FOCUS_LOCKS_USE (SPOOLER_ID,NAME,LOCK_ID,JOB_ID,EXCLUSIVE,IS_AVAILABLE,IS_MISSING,UPDATED,CRC) values ('.$spooler_id.', "'.$lock.'", '.$lock_id.', '.$job_id.', '.$exclusive.','.$is_available.','.$is_missing.','.$timestamp.',"'.$crc.'" )';
                                                    array_push($Queries, $qry);
                                            }
                                            $nl++;
                                    }
                            }
                    }
                    $n++;
            }
        }
        $this->PrintMessage(1,"Jobs: $n");    
    }
    else {
        $this->PrintMessage(1,"Jobs: too late !");
    }
    $this->PrintMessage(2,"Timer: ".$hourglass);

    $hourglass = microtime(true)-$timer;
    if ($hourglass<$maxtime) {
            //=JOB CHAINS/NODES/ORDERS================================================================================================
            if (isset($Result['spooler']['answer']['state']['job_chains']['job_chain'])) {
                $Jobs = $Result['spooler']['answer']['state']['job_chains']['job_chain'];

                // print_r($Jobs);
                $n = 0;
                if (isset($Jobs['attr'])) {
                        $Jobs[$n] = $Jobs;
                }
                while (isset($Jobs[$n])) {
                        $name = $Jobs[$n]['attr']['name']; // nom de la job chain
                        $path  = $Jobs[$n]['attr']['path']; // nom complet
                        $state = $Jobs[$n]['attr']['state'];
                        $orders = ( isset($Jobs[$n]['attr']['orders']) ? $Jobs[$n]['attr']['orders'] : 0);	
                        $running_orders = ( isset($Jobs[$n]['attr']['running_orders']) ? $Jobs[$n]['attr']['running_orders'] : 0);	
                        $max_orders = ( isset($Jobs[$n]['attr']['max_orders']) ? $Jobs[$n]['attr']['max_orders'] : 'null');	
                        if ( isset($Jobs[$n]['attr']['order']) and ($Jobs[$n]['attr']['order'] == 'yes' )) $orders_recoverable = 1; 
                            else  $orders_recoverable =  0; 
                        $title = ( isset($Jobs[$n]['attr']['title']) ? $this->TextProtect($Jobs[$n]['attr']['title']) : '' );
                        $order_id_space = ( isset($Jobs[$n]['attr']['order_id_space']) ? '"'.$Jobs[$n]['attr']['order_id_space'].'"' : '' );
                        if ($order_id_space == '') $order_id_space='null';
                        $last_write_time = '';
                        if (isset($Jobs[$n]['file_based']['attr']['last_write_time'])) {
                                $last_write_time = $this->CorrectDatetime($Jobs[$n]['file_based']['attr']['last_write_time']);
                        }
                        $job_chain=$path;      

                        // Si l'ID existe, on update
                        // Sinon on insert
                        $update = 'NAME="'.$name.'",PATH="'.$path.'",STATE="'.$state.'",TITLE="'.$title.'",ORDER_ID_SPACE='.$order_id_space.',ORDERS_RECOVERABLE='.$orders_recoverable.',ORDERS='.$orders.',RUNNING_ORDERS='.$running_orders.',MAX_ORDERS='.$max_orders.',LAST_WRITE_TIME="'.$last_write_time.'"';
                        $crc = hash('crc32',$update);
                        if (isset($JobChainID[$job_chain])) {
                            $job_chain_id=$JobChainID[$job_chain];
                            $JobChainDel[$job_chain]= -1;
                            if ($JobChainCRC[$job_chain]!=$crc) {
                                $qry = 'update FOCUS_JOB_CHAINS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$job_chain_id;
                                array_push($Queries, $qry);
                            }
                        }
                        else {
                                $job_chain_id= 'null';
                                $qry = 'insert into FOCUS_JOB_CHAINS (SPOOLER_ID,NAME,PATH,TITLE,ORDER_ID_SPACE,STATE,ORDERS_RECOVERABLE,ORDERS,RUNNING_ORDERS,MAX_ORDERS,LAST_WRITE_TIME,UPDATED,CRC) values ( '.$spooler_id.', "'.$name.'", "'.$path.'", "'.$title.'", '.$order_id_space.', "'.$state.'", '.$orders_recoverable.', '.$orders.', '.$running_orders.', '.$max_orders.', "'.$last_write_time.'",'.$timestamp.',"'.$crc.'" )';
                                array_push($Queries, $qry);
                        }

                        // Est ce qu'il y a un file order ?
                        if (isset(  $Jobs[$n]['file_order_source'] ) ) {
                            $Files = $Jobs[$n]['file_order_source'];
                            $nf=0;
                            if (isset($Files['attr'])) { $Files[$nf] = $Files; }
                            while (isset($Files[$nf])) {
                                    $directory = str_replace('\\','\\\\',$Files[$nf]['attr']['directory']);
                                    $regex = str_replace('\\','\\\\',$Files[$nf]['attr']['regex']);
                                    $missing = $Files[$nf]['attr']['alert_when_directory_missing'];
                                    $file = "$directory/$regex";
                                    $delay = $Files[$nf]['attr']['delay_after_error'];
                                    $repeat = $Files[$nf]['attr']['repeat'];
                                    $next_state = $Files[$nf]['attr']['next_state']; 

                                    $update = 'JOB_CHAIN_ID='.$job_chain_id.' ,DIRECTORY="'.$directory.'" ,REGEX="'.$regex.'" ,DELAY_AFTER_ERROR='.$delay.' ,RETRY='.$repeat;
                                    $crc = hash('crc32', $update);

                                    if (isset($FileID[$file])) {
                                            $FileDel[$file]= -1;
                                            $file_id = $FileID[$file];
                                            if ($FileCRC[$file]!=$crc) {
                                                $qry = 'update FOCUS_FILE_ORDERS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'",ALERT_WHEN_DIRECTORY_MISSING='.$missing.' where id='.$file_id;
                                                array_push($Queries, $qry);
                                            }
                                    }
                                    else {
                                        // inutile si la chaine n'est pas encore dans la base de donnees
                                            $file_id = 'null';
                                        if ($job_chain_id>0) {
                                            $qry = 'insert into FOCUS_FILE_ORDERS (SPOOLER_ID,JOB_CHAIN_ID,DIRECTORY,REGEX,DELAY_AFTER_ERROR,RETRY,ALERT_WHEN_DIRECTORY_MISSING,UPDATED,CRC) values ( '.$spooler_id.', '.$job_chain_id.', "'.$directory.'", "'.$regex.'", '.$delay.', '.$repeat.', '.$missing.', '.$timestamp.',"'.$crc.'" )';
                                            array_push($Queries, $qry);
                                        }
                                    }
                                    $nf++;
                            }
                        // exit();                        
                        }

                        // On passe au job_chain_nodes 
                        // Ordre des steps
                        if (isset(  $Jobs[$n]['job_chain_node'] ) ) {
                                $Nodes = $Jobs[$n]['job_chain_node'];
                                $nn=0;
    //                            print_r($Jobs[$n]['job_chain_node']);
                                $ordering = 0;
                                $master_id = $job_chain_id;
                                while (isset($Nodes[$nn])) {
                                        $state = $Nodes[$nn]['attr']['state'];
                                        $node = "$job_chain_id/$state";
                                        $next_state = (isset($Nodes[$nn]['attr']['next_state']) ?  '"'.$Nodes[$nn]['attr']['next_state'].'"' : 'null');
                                        $error_state = (isset($Nodes[$nn]['attr']['error_state']) ? '"'.$Nodes[$nn]['attr']['error_state'].'"' : 'null');
                                        $action = (isset($Nodes[$nn]['attr']['action']) ? '"'.$Nodes[$nn]['attr']['action'].'"' : 'null');
                                        $jobcmd = ( isset($Nodes[$nn]['attr']['job']) ? $Nodes[$nn]['attr']['job'] : '' );
                                        if (($jobcmd!='') and (isset($JobID[$jobcmd]))) { $job_id = $JobID[$jobcmd]; }
                                            else { $job_id = 'null'; }
                                        $chaincmd = ( isset($Nodes[$nn]['attr']['job_chain']) ? $Nodes[$nn]['attr']['job_chain'] : '' );
                                        if (($chaincmd!='') and (isset($JobChainID[$chaincmd]))) { $chain_id = $JobChainID[$chaincmd]; }
                                            else { $chain_id = 'null'; }

                                        $update = 'JOB_CHAIN_ID='.$job_chain_id.' ,JOB_ID='.$job_id.' ,CHAIN_ID='.$chain_id.' , STATE="'.$state.'",TITLE="'.$title.'",NEXT_STATE='.$next_state.',ERROR_STATE='.$error_state.',ACTION='.$action.',ORDERING='.$ordering;
                                        $crc = hash('crc32', $update);

                                        if (isset($NodeID[$node])) {
                                                $NodeDel[$node]= -1;
                                                $node_id = $NodeID[$node];
                                                if ($NodeCRC[$node]!=$crc) {
                                                    $qry = 'update FOCUS_JOB_CHAIN_NODES set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$NodeID[$node];
                                                    array_push($Queries, $qry);
                                                }
                                        }
                                        else {
                                            // inutile si la chaine n'est pas encore dans la base de donnees
                                                $node_id = 'null';
                                            if ($job_chain_id>0) {
                                                $qry = 'insert into FOCUS_JOB_CHAIN_NODES (SPOOLER_ID,JOB_CHAIN_ID,JOB_ID,CHAIN_ID,STATE,TITLE,NEXT_STATE,ERROR_STATE,ACTION,ORDERING,UPDATED,CRC) values ( '.$spooler_id.', '.$job_chain_id.', '.$job_id.', '.$chain_id.', "'.$state.'", "'.$title.'", '.$next_state.', '.$error_state.', '.$action.', '.$ordering.','.$timestamp.',"'.$crc.'" )';
                                                array_push($Queries, $qry);
                                            }
                                        }
                                        $ordering++;

                                        # A l'interieur du noeud, on regarde si il y a un ordre
                                        // Le path est le repertoire de la job chain
                                        // Il est vide si l'order est à la volée
                                        $default_path = dirname($job_chain);
                                        if (isset($Nodes[$nn]['order_queue']['order'])) {
                                                $Orders = $Nodes[$nn]['order_queue']['order'];
                                                // print_r($Orders);
                                                $no = 0;
                                                // si  ce n'est pas un tableau, on le transforme
                                                if (isset($Orders['attr'])) {
                                                        $Orders[$no] = $Orders;
                                                }
                                                while (isset($Orders[$no])) {
                                                        // print_r($Orders[$no]);
                                                        $name= (isset($Orders[$no]['attr']['name']) ? str_replace('\\','\\\\', $Orders[$no]['attr']['name']) : '');
                                                        // cas de la blacklist
                                                        if ($name=='') {
                                                            $name= (isset($Orders[$no]['attr']['order']) ? str_replace('\\','\\\\', $Orders[$no]['attr']['order']) : '');
                                                        }
                                                        $state= $Orders[$no]['attr']['state'];
                                                        // Obligatoirement le path de la chaine
                                                        $path = $Orders[$no]['attr']['path'];

                                                        if ($path=='/') {
                                                            $path = $job_chain.','.$name;
                                                        }                                                 
                                                        $order_id = (isset($Orders[$no]['attr']['id']) ? $Orders[$no]['attr']['id']:'');
                                                        $state= $Orders[$no]['attr']['state'];
                                                        $title= (isset($Orders[$no]['attr']['title']) ? $this->TextProtect($Orders[$no]['attr']['title']) : '');
                                                        $state_text=  (isset($Orders[$no]['attr']['state_text']) ?  $this->TextProtect($Orders[$no]['attr']['state_text']) : '');
                                                        $next_start_time= (isset($Orders[$no]['attr']['next_start_time']) ? '"'.$this->CorrectDatetime($Orders[$no]['attr']['next_start_time']).'"' : 'null');
                                                        $start_time= (isset($Orders[$no]['attr']['start_time']) ? '"'.$this->CorrectDatetime($Orders[$no]['attr']['start_time']).'"' : 'null');
                                                        $setback= (isset($Orders[$no]['attr']['setback']) ? '"'.$this->CorrectDatetime($Orders[$no]['attr']['setback']).'"' : 'null');
                                                        $setback_count= (isset($Orders[$no]['attr']['setback_count']) ? $Orders[$no]['attr']['setback_count'] : 'null');
                                                        $end_time= (isset($Orders[$no]['attr']['end_time']) ? '"'.$this->CorrectDatetime($Orders[$no]['attr']['end_time']).'"' : 'null');
                                                        if (isset($Orders[$no]['attr']['history_id'])) { $history_id =  $Orders[$no]['attr']['history_id']; }
                                                            else { $history_id = 'null'; }
                                                        $task_id= (isset($Orders[$no]['attr']['task']) ? $Orders[$no]['attr']['task'] : 'null');
                                                        $in_process_since = (isset($Orders[$no]['attr']['in_process_since']) ? '"'.$this->CorrectDatetime($Orders[$no]['attr']['in_process_since']).'"' : 'null');
                                                        if (isset($Orders[$no]['attr']['touched']) and ($Orders[$no]['attr']['touched']=='yes')) { $touched=1; }
                                                            else {$touched =0; }
                                                        $created= (isset($Orders[$no]['attr']['created']) ? $this->CorrectDatetime($Orders[$no]['attr']['created']) : '');
                                                        $priority = (isset($Orders[$no]['attr']['priority']) ? $Orders[$no]['attr']['priority'] : 0);
                                                        $last_write_time= (isset($Orders[$no]['file_based']['attr']['last_write_time']) ? $this->CorrectDatetime($Orders[$no]['file_based']['attr']['last_write_time']) : '');
                                                        $initial_state = $Orders[$no]['attr']['initial_state'];
                                                        $end_state = (isset($Orders[$no]['attr']['end_state']) ? $Orders[$no]['attr']['end_state'] : '' );
                                                        $schedule = ( isset($Orders[$no]['attr']['active_schedule']) ? $Orders[$no]['attr']['active_schedule'] : '');	
                                                        if (isset($ScheduleID[$schedule])) 
                                                            $schedule_id = $ScheduleID[$schedule];
                                                        else 
                                                            $schedule_id = 'null';
                                                        if (isset($Orders[$no]['attr']['suspended']) and ($Orders[$no]['attr']['suspended']=='yes')) { $suspended=1; }
                                                            else { $suspended=0; }
                                                        if (isset($Orders[$no]['attr']['on_blacklist']) and ($Orders[$no]['attr']['on_blacklist']=='yes')) { $on_blacklist=1; }
                                                            else { $on_blacklist=0; }
                                                        $order = $job_chain.','.$order_id;
                                                        // $order = $path; // attention aux ordres a la volee !
                                                        // lien vers les taches
                                                        $update = 'SPOOLER_ID='.$spooler_id.',NAME="'.$name.'",PATH="'.$path.'", JOB_CHAIN_NODE_ID='.$node_id.', JOB_CHAIN_ID='.$job_chain_id.', START_TIME='.$start_time.', END_TIME='.$end_time.', NEXT_START_TIME='.$next_start_time.', TITLE="'.$title.'", STATE_TEXT="'.$state_text.'", SUSPENDED='.$suspended.', ON_BLACKLIST='.$on_blacklist.', IN_PROCESS_SINCE='.$in_process_since.', TOUCHED='.$touched.', LAST_WRITE_TIME="'.$last_write_time.'", STATE="'.$state.'", INITIAL_STATE="'.$initial_state.'", END_STATE="'.$end_state.'", SETBACK='.$setback.', SETBACK_COUNT='.$setback_count.', CREATED="'.$created.'", PRIORITY='.$priority.', SCHEDULE_ID='.$schedule_id;
                                                        if ($history_id!='null')
                                                            $update .= ',HISTORY_ID='.$history_id;
                                                        if ($task_id!='null')
                                                            $update .= ',TASK_ID='.$task_id;
                                                        $crc = hash('crc32',$update.'1');
                                                        $order_payload = -1;
                                                        if (isset($OrderID[$order])) {
                                                                $OrderDel[$order]=-1;
                                                                if ($OrderCRC[$order]!=$crc) {
                                                                    $qry = 'update FOCUS_ORDERS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$OrderID[$order];
                                                                    array_push($Queries, $qry);
                                                                    $order_payload = $OrderID[$order];
                                                                }
                                                       }
                                                        else {
                                                                $qry = 'insert into FOCUS_ORDERS (SPOOLER_ID,NAME,PATH,JOB_CHAIN_NODE_ID,JOB_CHAIN_ID,START_TIME,END_TIME,NEXT_START_TIME,SETBACK,SETBACK_COUNT,TITLE,SUSPENDED,ON_BLACKLIST,HISTORY_ID,IN_PROCESS_SINCE,TOUCHED,LAST_WRITE_TIME,STATE,STATE_TEXT,INITIAL_STATE,END_STATE,CREATED,PRIORITY,TASk_ID,SCHEDULE_ID,UPDATED,CRC) values ( '.$spooler_id.', "'.$order_id.'", "'.$path.'", '.$node_id.', '.$job_chain_id.', '.$start_time.', '.$end_time.', '.$next_start_time.','.$setback.','.$setback_count.',"'.$title.'",'.$suspended.', '.$on_blacklist.', '.$history_id.', '.$in_process_since.', '.$touched.', "'.$last_write_time.'", "'.$state.'", "'.$state_text.'", "'.$initial_state.'", "'.$end_state.'","'.$created.'",'.$priority.','.$task_id.','.$schedule_id.','.$timestamp.',"'.$crc.'" )';
                                                                array_push($Queries, $qry);
                                                       }
                                                       

                                                       // Payload
                                                       if (($order_payload>=0) and (isset($Orders[$no]['payload']['params']))) {
                                                           $Payloads = $Orders[$no]['payload']['params'];
                                                            // si  ce n'est pas un tableau, on le transforme
                                                            // on doit avoir le nombre
                                                            $params_count = $Payloads['attr']['count'];
                                                            if ($params_count>0)
                                                                $Params = $Payloads['param'];
                                                            for($i=1;$i<=$params_count;$i++) {
                                                                $np = $i-1;
                                                                if (isset($Params['attr']))
                                                                    $P = $Params['attr'];
                                                                else 
                                                                    $P = $Params[$np]['attr'];
                                                                $name = $P['name']; 
                                                                if (isset($P['value'])) 
                                                                    $value = $this->TextProtect($P['value']);
                                                                else 
                                                                    $value = '!?';
                                                                $payload = $order_payload.'/'.$name;
                                                                $update = 'SPOOLER_ID='.$spooler_id.',ORDER_ID='.$order_payload.',NAME="'.$name.'",VALUE="'.$value.'"';
                                                                $crc = hash('crc32',$update);
                                                                if (isset($PayloadID[$payload])) {
                                                                        $PayloadDel[$payload]=-1;
                                                                        if ($PayloadCRC[$payload]!=$crc) {
                                                                            $qry = 'update FOCUS_PAYLOADS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$PayloadID[$payload];
                                                                            array_push($Queries, $qry);
                                                                        }
                                                               }
                                                                else {
                                                                        $qry = 'insert into FOCUS_PAYLOADS (SPOOLER_ID,ORDER_ID,NAME,VALUE,UPDATED,CRC) values ( '.$spooler_id.','.$order_payload.',"'.$name.'","'.$value.'",'.$timestamp.',"'.$crc.'" )';
                                                                        array_push($Queries, $qry);
                                                               }     
                                                            }

                                                    }
                                                    $no++;
                                             }
                                        }
                                       $nn++;
                                }
                        }
                        $n++;
                }
        }
        $this->PrintMessage(1,"Job chain: $n");
    }
    else {
        $this->PrintMessage(1,"Jobs: too late !");
    }
    $this->PrintMessage(2,"Timer: ".$hourglass);

    $hourglass = microtime(true)-$timer;
    if ($hourglass<$maxtime) {
        //=LOCKS================================================================================================
        $n = $nu = $ni = 0;
        if (isset($Result['spooler']['answer']['state']['locks']['lock'])) {
                $Locks = $Result['spooler']['answer']['state']['locks']['lock'];
                if (isset($Locks['attr'])) { $Locks[$n] = $Locks; }
                while (isset($Locks[$n])) {
                        $path = $Locks[$n]['attr']['path'];
                        $name = $Locks[$n]['attr']['name'];
                        if (isset($Locks[$n]['attr']['is_free']) and ($Locks[$n]['attr']['is_free']=='yes')) { $is_free = 1; }
                            else { $is_free = 0; };
                        if (isset($Locks[$n]['attr']['max_non_exclusive'])) { $max_non_exclusive = $Locks[$n]['attr']['max_non_exclusive']; }
                            else { $max_non_exclusive = 'null'; }
                        $state = $Locks[$n]['file_based']['attr']['state'];
                        $lock = $path;
                        $update = 'NAME="'.$name.'",PATH="'.$path.'",IS_FREE='.$is_free.', MAX_NON_EXCLUSIVE='.$max_non_exclusive.', state="'.$state.'"';
                        $crc = hash('crc32',$update);
                        if (isset($LockID[$lock])) {
                            $LockDel[$lock]=-1;
                            if ($LockCRC[$lock]!=$crc) {
                                $qry = 'update FOCUS_LOCKS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$LockID[$lock];
                                array_push($Queries, $qry);
                            }
                        }
                        else {
                                $qry = 'insert into FOCUS_LOCKS (NAME,PATH,SPOOLER_ID,IS_FREE,STATE,UPDATED,CRC) values ( "'.$name.'", "'.$path.'",'.$spooler_id.','.$is_free.', "'.$state.'",'.$timestamp.',"'.$crc.'" )';		
                                array_push($Queries, $qry);
                        }
                        $n++;
                }
        }
        $this->PrintMessage(1,"Locks: $n");    
    }
    else {
        $this->PrintMessage(1,"Locks: too late !");
    }
    $this->PrintMessage(2,"Timer: ".$hourglass);

    $hourglass = microtime(true)-$timer;
    if ($hourglass<$maxtime) {
        //=SCHEDULES================================================================================================
        $n = $nu = $ni = 0;
        if (isset($Result['spooler']['answer']['state']['schedules']['schedule'])) {
                $Schedules = $Result['spooler']['answer']['state']['schedules']['schedule'];
                if (isset($Schedules['attr'])) { $Schedules[$n] = $Schedules; }
                while (isset($Schedules[$n])) {
                        $path = $Schedules[$n]['attr']['path'];
                        $name = $Schedules[$n]['attr']['name'];
                        $title = (isset($Schedules[$n]['attr']['title'])?$this->TextProtect($Schedules[$n]['attr']['title']):'');
                        $state = $Schedules[$n]['file_based']['attr']['state'];
                        if (isset($Schedules[$n]['attr']['active']) and ($Schedules[$n]['attr']['active']=='yes')) { $active = 1; }
                            else { $active = 0; };
                        $substitute_id = 'null';
                        if (isset($Schedules[$n]['attr']['substitute'])) {
                            $substitute = $Schedules[$n]['attr']['substitute'];
                            if (isset($ScheduleID[$substitute])) {
                                $substitute_id = $ScheduleID[$substitute];
                            }
                        }
                        if (isset($Schedules[$n]['attr']['valid_from']))
                            $valid_from = '"'.$Schedules[$n]['attr']['valid_from'].'"';
                        else 
                            $valid_from = 'null';
                        if (isset($Schedules[$n]['attr']['valid_to']))
                            $valid_to = '"'.$Schedules[$n]['attr']['valid_to'].'"';
                        else 
                            $valid_to = 'null';
                        $schedule = $path;
                        $update = 'NAME="'.$name.'",PATH="'.$path.'",TITLE="'.$title.'", state="'.$state.'", valid_from='.$valid_from.', valid_to='.$valid_to.',SUBSTITUTE_ID='.$substitute_id.',ACTIVE='.$active;
                        $crc = hash('crc32',$update);
                        if (isset($ScheduleID[$schedule])) {
                            $ScheduleDel[$schedule]=-1;
                            if ($ScheduleCRC[$schedule]!=$crc) {
                                $qry = 'update FOCUS_SCHEDULES set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$ScheduleID[$schedule];
                                array_push($Queries, $qry);
                            }                        
                        }
                        else {
                                $qry = 'insert into FOCUS_SCHEDULES (NAME,PATH,TITLE,SPOOLER_ID,ACTIVE,STATE,VALID_TO,VALID_FROM,SUBSTITUTE_ID,UPDATED,CRC) values ( "'.$name.'", "'.$path.'", "'.$title.'",'.$spooler_id.','.$active.', "'.$state.'",'.$substitute_id.','.$valid_from.','.$valid_to.','.$timestamp.',"'.$crc.'" )';		
                                array_push($Queries, $qry);
                        }

                        if (isset($ScheduleID[$schedule])) { 
                            $schedule_id = $ScheduleID[$schedule];
                            $days = $months = $start_time = '';
                            $day_type = 'everyday';
                            $date = '0000-00-00';
                            // Liste de date 
                            if (isset($Schedules[$n]['date'])) {
                                $Period = $Schedules[$n]['date']; 
                                $date = (isset($Period['attr']['date'])?$Period['attr']['date']:'0000-00-00');
                            }  
                            else {  
                                if (isset($Schedules[$n]['month'])) {
                                    $Day = $Schedules[$n]['month'];
                                    $months = str_replace(
                                            array('january','february','march','april','may','june','july','august','september','october','november','december'),
                                            array(1,2,3,4,5,6,17,8,9,10,11,12),
                                            $Day['attr']['month']);                                
                                }
                                else {
                                    $Day = $Schedules[$n];      
                                    $months = '';
                                }
                                if (isset($Day['weekdays'])) $day_type = 'weekdays';
                                elseif (isset($Day['monthdays'])) $day_type = 'monthdays';
                                elseif (isset($Day['ultimos'])) $day_type = 'ultimos';
                                else  $day_type= 'everyday';

                                if ($day_type == 'everyday') {
                                    if (isset($Day['day'])) {
                                        $days = $Day['day']['attr']['day'];
                                        $Period = $Day['day'];
                                    }
                                    else {
                                        $days = '';
                                        $Period = $Day;
                                    }
                                }
                                elseif (isset($Day[$day_type]['day']['attr']['day'])) {
                                    $days = $Day[$day_type]['day']['attr']['day'];
                                    $Period = $Day[$day_type]['day'];
                                }
                                else 
                                    $days = '';
                            }
                            if (isset($Period['period']['attr'])) $Period[0]['period']['attr'] = $Period['period']['attr'];
                            $np = 0;
                            while (isset($Period[$np]['period']['attr'])) {   
                                $P = $Period[$np]['period']['attr'];
                                $begin = (isset($P['begin'])?'"'.$P['begin'].'"':'null');
                                $end = (isset($P['end'])?'"'.$P['end'].'"':'null');
                                $when_holiday = (isset($P['when_holiday'])?'"'.$P['when_holiday'].'"':'null');
                                if (isset($P['single_start'])) {
                                    $start_type = '"single_start"';
                                    $start_time = $P['single_start'];
                                }
                                elseif (isset($P['repeat'])) {
                                    $start_type = '"repeat"';
                                    $start_time = $P['repeat'];
                                }
                                elseif (isset($P['absolute_repeat'])) {
                                    $start_type = '"absolute_repeat"';
                                    $start_time = $P['absolute_repeat'];
                                }
                                else {
                                    $start_type = 'unknown';
                                    $start_time = 'null';
                                }

                                if (strlen($start_time)<=5) $start_time .= ':00'; 
                                $period =  $schedule_id.'|'.$date.'|'.$months.'|'.$days.'|'.$start_time; 

                                $update = 'DATE="'.$date.'",MONTHS="'.$months.'",DAYS="'.$days.'",DAY_TYPE="'.$day_type.'",BEGIN='.$begin.',END='.$end.',START_TIME="'.$start_time.'",START_TYPE='.$start_type.',WHEN_HOLIDAY='.$when_holiday;
                                $crc = hash('crc32',$update);
                                if (isset($PeriodID[$period])) {
                                    $PeriodDel[$period]=-1;
                                    if ($PeriodCRC[$period]!=$crc) {
                                        $qry = 'update FOCUS_PERIODS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$PeriodID[$period];
                                        array_push($Queries, $qry);      
                                    }
                                }
                                else { 
                                    $qry = 'insert into FOCUS_PERIODS (SPOOLER_ID,SCHEDULE_ID,DATE,MONTHS,DAYS,DAY_TYPE,BEGIN,END,START_TIME,START_TYPE,WHEN_HOLIDAY,UPDATED,CRC) values ( '.$spooler_id.','.$schedule_id.',"'.$date.'","'.$months.'","'.$days.'","'.$day_type.'",'.$begin.','.$end.',"'.$start_time.'",'.$start_type.','.$when_holiday.','.$timestamp.',"'.$crc.'" )';		
                                    array_push($Queries, $qry);
                                }
                                $np++;
                            }
                        }
                        $n++;
                }
        }
        $this->PrintMessage(1,"Schedules: $n");    
    }
    else {
        $this->PrintMessage(1,"Schedules: too late !");
    }
    $this->PrintMessage(2,"Timer: ".$hourglass);

    $hourglass = microtime(true)-$timer;
    if ($hourglass<$maxtime) {
        //=PROCESS CLASSES================================================================================================
        $n = $nu = $ni = 0;
        if (isset($Result['spooler']['answer']['state']['process_classes']['process_class'])) {
            $Classes = $Result['spooler']['answer']['state']['process_classes']['process_class'];
            if (isset($Classes['attr'])) { $Classes[$n] = $Classes; }
            while (isset($Classes[$n])) {
                    $path = $Classes[$n]['attr']['path'];
                    if (isset($Classes[$n]['attr']['name'])) { $name = $Classes[$n]['attr']['name']; }
                        else { $name = ''; } 
                    $max_processes = $Classes[$n]['attr']['max_processes'];
                    $processes = $Classes[$n]['attr']['processes'];
                    if (isset($Classes[$n]['attr']['remote_scheduler'])) { $remote_scheduler = $Classes[$n]['attr']['remote_scheduler']; }
                        else { $remote_scheduler = ''; }
                    $state = $Classes[$n]['file_based']['attr']['state'];
                    if (isset($Classes[$n]['file_based']['attr']['last_write_time']))
                            $last_write_time = $this->CorrectDatetime($Classes[$n]['file_based']['attr']['last_write_time']);
                    else
                            $last_write_time = '0-0-0';
                    // est ce qu'on est en erreur ?
                    if (isset($Classes[$n]['file_based']['ERROR']['attr']['text'])) { $error = '"'.$Classes[$n]['file_based']['ERROR']['attr']['text'].'"'; }
                        else { $error = 'null'; };

                    $process = $path;
                    $update = 'NAME="'.$name.'", PATH="'.$path.'",REMOTE_SCHEDULER="'.$remote_scheduler.'", MAX_PROCESSES='.$max_processes.', PROCESSES='.$processes.', state="'.$state.'", LAST_WRITE_TIME="'.$last_write_time.'",ERROR='.$error;

                    $crc = hash('crc32',$update);
                    if (isset($ProcessClassID[$process])) {
                        $ProcessClassDel[$process]=-1;                    
                        if ($ProcessClassCRC[$process]!=$crc) {
                            $qry = 'update FOCUS_PROCESS_CLASSES set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$ProcessClassID[$process];
                            array_push($Queries, $qry);
                            $nu++;
                        }
                    }
                    else {
                            $qry = 'insert into FOCUS_PROCESS_CLASSES (NAME,PATH,SPOOLER_ID,REMOTE_SCHEDULER,MAX_PROCESSES,PROCESSES,STATE,LAST_WRITE_TIME,UPDATED,CRC,ERROR) values ( "'.$name.'", "'.$path.'", '.$spooler_id.', "'.$remote_scheduler.'", "'.$max_processes.'", "'.$processes.'", "'.$state.'", "'.$last_write_time.'",'.$timestamp.',"'.$crc.'",'.$error.' )';		
                            array_push($Queries, $qry);
                            $ni++;
                    }
                    $n++;
            }
        }
        $this->PrintMessage(1,"Process classes: $n");
    }
    else {
        $this->PrintMessage(1,"Process classes: too late !");
    }
    $this->PrintMessage(2,"Timer: ".$hourglass);

    $hourglass = microtime(true)-$timer;
    if ($hourglass<$maxtime) {
        //=CONNECTIONS================================================================================================
        $n = $nu = $ni = 0;
        if (isset($Result['spooler']['answer']['state']['connections']['connection'])) {
            $Connections = $Result['spooler']['answer']['state']['connections']['connection'];
            if (isset($Connections['attr'])) { $Connections[$n] = $Connections; }
            while (isset($Connections[$n])) {
                    $host_ip = $Connections[$n]['peer']['attr']['host_ip'];
                    $port = $Connections[$n]['peer']['attr']['port'];
                    if (isset($Connections[$n]['attr']['operation_type']))
                        $operation_type = $Connections[$n]['attr']['operation_type'];
                    else 
                        $operation_type = '?!';
                    $received_bytes = $Connections[$n]['attr']['received_bytes'];
                    $responses = $Connections[$n]['attr']['responses'];
                    $sent_bytes = $Connections[$n]['attr']['sent_bytes'];
                    $state = $Connections[$n]['attr']['state'];
                    $connect = "$host_ip#$port";
                    $update = 'SPOOLER_ID='.$spooler_id.',OPERATION_TYPE="'.$operation_type.'", RECEIVED_BYTES='.$received_bytes.',RESPONSES='.$responses.', SEND_BYTES='.$sent_bytes.', STATE="'.$state.'"';
                    $crc = hash('crc32',$update);
                    if (isset($ConnectionID[$connect])) {
                        $ConnectionDel[$connect]=-1;
                        if ($ConnectionCRC[$connect]!=$crc) {                     
                            $qry = 'update FOCUS_CONNECTIONS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$ConnectionID[$connect];
                            array_push($Queries, $qry);
                        }
                    }
                    else {
                            $qry = 'insert into FOCUS_CONNECTIONS (SPOOLER_ID,OPERATION_TYPE,RECEIVED_BYTES,RESPONSES,SEND_BYTES,STATE,HOST_IP,PORT,UPDATED,CRC) values ( '.$spooler_id.',"'.$operation_type.'", '.$received_bytes.', '.$responses.', '.$sent_bytes.', "'.$state.'", "'.$host_ip.'", '.$port.','.$timestamp.',"'.$crc.'" )';		
                            array_push($Queries, $qry);
                    }
                    $n++;
            }
        }
        $this->PrintMessage(1,"Connections: $n");    
    }
    else {
        $this->PrintMessage(1,"Connections: too late !");
    }
    $this->PrintMessage(2,"Timer: ".$hourglass);

    $hourglass = microtime(true)-$timer;
    if ($hourglass<$maxtime) {
    //=ORDER_ID_SPACES================================================================================================
/*
    $n = 0;
    if (isset($Result['spooler']['answer']['state']['order_id_spaces']['order_id_space'])) {
        $Spaces = $Result['spooler']['answer']['state']['order_id_spaces']['order_id_space'];
        if (isset($Spaces['attr'])) { $Spaces[$n] = $Spaces; }
        while (isset($Spaces[$n])) {
            $name = $Spaces[$n]['attr']['name'];
            $nc = 0 ;
            while (isset($Spaces[$n]['job_chain'][$nc]['attr']['job_chain'])) {
                $chain = $Spaces[$n]['job_chain'][$nc]['attr']['job_chain'];
                if (isset($JobChainID[$chain])) {
                    $jc = $JobChainID[$chain];
                    $idspace = "$name#$jc";
                }
                else {
                    $jc = 'null';
                    $idspace = "$name#";
                }
                $update = 'SPOOLER_ID='.$spooler_id.',NAME="'.$name.'",JOB_CHAIN_ID='.$jc;
                $crc = hash('crc32',$update);
                if (isset($SpaceID[$idspace])) {
                    $SpaceDel[$idspace]=-1;
                    if ($SpaceCRC[$idspace]!=$crc) {
                        $qry = 'update FOCUS_ORDER_ID_SPACES set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$SpaceID[$idspace];
                        array_push($Queries, $qry);
                    }
                }
                else {
                        $qry = 'insert into FOCUS_ORDER_ID_SPACES (SPOOLER_ID,NAME,JOB_CHAIN_ID,UPDATED,CRC) values ( '.$spooler_id.',"'.$name.'", '.$jc.','.$timestamp.',"'.$crc.'" )';		
                        array_push($Queries, $qry);
                }
                $nc++;
            }
            $n++;
        }
    }
    $this->PrintMessage(2,"Spaces: $n");    
    $this->PrintMessage(2,"Timer: ".(microtime(true)-$timer));
*/
    }
    else {
        $this->PrintMessage(1,"Spaces: too late !");
    }
    $this->PrintMessage(2,"Timer: ".$hourglass);

    $hourglass = microtime(true)-$timer;
    if ($hourglass<$maxtime) {
        //=REMOTE SCHEDULERS================================================================================================
        if (isset($Result['spooler']['answer']['state']['remote_schedulers']['remote_scheduler'])) {
            $Remotes = $Result['spooler']['answer']['state']['remote_schedulers']['remote_scheduler'];
            $n = 0;
            if (isset($Remotes['attr'])) { $Remotes[$n] = $Remotes; }
            while (isset($Remotes[$n])) {
                    $ip = $Remotes[$n]['attr']['ip'];
                    if (isset($Remotes[$n]['attr']['tcp_port']))
                        $port = $Remotes[$n]['attr']['tcp_port'];
                    elseif (isset($Remotes[$n]['attr']['udp_port']))
                        $port = $Remotes[$n]['attr']['udp_port'];
                    else $port = 0;
                    if (isset($Remotes[$n]['attr']['configuration_changed_at'])) { $configuration_changed_at = '"'.$this->CorrectDatetime($Remotes[$n]['attr']['configuration_changed_at']).'"'; }
                        else { $configuration_changed_at='null'; }
                    if (isset($Remotes[$n]['attr']['configuration_transfered_at'])) { $configuration_transfered_at = '"'.$this->CorrectDatetime($Remotes[$n]['attr']['configuration_transfered_at']).'"'; }
                        else { $configuration_transfered_at='null'; }
                    $connected = ($Remotes[$n]['attr']['connected_at']=='yes'? 1 : 0);                
                    $connected_at = '"'.$this->CorrectDatetime($Remotes[$n]['attr']['connected_at']).'"';                
                    if (isset($Remotes[$n]['attr']['disconnected_at'])) { $disconnected_at = '"'.$this->CorrectDatetime($Remotes[$n]['attr']['disconnected_at']).'"'; }
                        else { $disconnected_at = 'null'; }
                    $hostname = $Remotes[$n]['attr']['hostname'];                
                    $name = $Remotes[$n]['attr']['scheduler_id'];
                    $version = $Remotes[$n]['attr']['version'];                
                    if (isset($Remotes[$n]['attr']['error_code'])) {$error = $Remotes[$n]['attr']['error_code']; }
                        else { $error = 'null'; }
                    if (isset($Remotes[$n]['attr']['error_at'])) { $error_at = '"'.$this->CorrectDatetime($Remotes[$n]['attr']['error_at']).'"'; }               
                        else { $error_at = 'null'; }
                    $remote = "$ip#$port";
                    if (isset($SpoolerID[$remote])) { 
                        $remote_id = $SpoolerID[$remote];                         
                    }
                    elseif (isset($SpoolerHostID["$hostname#$port"])) {
                        $remote_id = $SpoolerHostID["$hostname#$port"];
                    } 
                    else { $remote_id = 'null'; }
                    $update = 'REMOTE_ID='.$remote_id.',HOSTNAME="'.$hostname.'", NAME="'.$name.'",CONFIGURATION_CHANGED_AT='.$configuration_changed_at.', CONFIGURATION_TRANSFERED_AT="'.'", CONNECTED="'.$connected.'",CONNECTED_AT='.$connected_at.',DISCONNECTED_AT='.$disconnected_at.',VERSION="'.$version.'",ERROR="'.$error.'",ERROR_AT='.$error_at;
                    $crc = hash('crc32',$update);

                    if (isset($RemoteID[$remote])) {
                        if ($RemoteCRC[$remote]!=$crc) {
                            $RemoteDel[$remote]=-1;
                            $qry = 'update FOCUS_REMOTE_SCHEDULERS set '.$update.',UPDATED='.$timestamp.',CRC="'.$crc.'" where id='.$RemoteID[$remote];
                            array_push($Queries, $qry);
                        }
                    }
                    else {                                                                                                                                                                                                                                              
                            $qry = 'insert into FOCUS_REMOTE_SCHEDULERS (SPOOLER_ID,REMOTE_ID,IP,TCP_PORT,HOSTNAME,NAME,CONFIGURATION_CHANGED_AT,CONFIGURATION_TRANSFERED_AT,CONNECTED,CONNECTED_AT,DISCONNECTED_AT,VERSION,ERROR,ERROR_AT,UPDATED,CRC)'
                                    . ' values ( '.$spooler_id.','.$remote_id.',"'.$ip.'", '.$port.', "'.$hostname.'", "'.$name.'",'.$configuration_changed_at.', '.$configuration_transfered_at.', "'.$connected.'", '.$connected_at.','.$disconnected_at.',"'.$version.'","'.$error.'",'.$error_at.','.$timestamp.',"'.$crc.'" )';		
                            array_push($Queries, $qry);
                     }
                    $n++;
            }
            $this->PrintMessage(1,"Remote schedulers: $n");    
        }
    }
    else {
        $this->PrintMessage(1,"Remote schedulers: too late !");
    }
    $this->PrintMessage(2,"Timer: ".$hourglass);

//===========================================================================
// '','','','','','','REMOTE_SCHEDULERS','CONNECTIONS'
// Nettoyage 
// Suppression des job_runtimes qui n'ont plus de jobs
if (isset($ParamsDel))  { array_push($Queries, $this->QueryDel('JOB_PARAMS', $ParamsDel)); }
if (isset($JobDel))  {  
    $qry = 'delete from FOCUS_JOB_RUNTIMES where job_id in ('.implode(',',$JobDel).')'; 
    $res = $data->sql->query( $qry );
    $qry = 'delete from FOCUS_JOB_STATUS where job_id in ('.implode(',',$JobDel).')'; 
    $res = $data->sql->query( $qry );
}
if (isset($OrderDel))  {  
    $qry = 'delete from FOCUS_ORDER_STATUS where order_id in ('.implode(',',$OrderDel).')'; 
    $res = $data->sql->query( $qry );
    $qry = 'delete from FOCUS_ORDER_STEP_STATUS where order_id in ('.implode(',',$OrderDel).')'; 
    $res = $data->sql->query( $qry );
    $qry = 'delete from FOCUS_ORDER_STEP_RUNTIMES where order_id in ('.implode(',',$OrderDel).')'; 
    $res = $data->sql->query( $qry );
    $qry = 'delete from FOCUS_ORDER_RUNTIMES where order_id in ('.implode(',',$OrderDel).')'; 
    $res = $data->sql->query( $qry );
    $qry = 'delete from FOCUS_PAYLOADS where order_id in ('.implode(',',$OrderDel).')'; 
    $res = $data->sql->query( $qry );
}

if (isset($PayloadDel))       { array_push($Queries, $this->QueryDel('PAYLOADS', $PayloadDel)); }   
if (isset($TaskDel))        { array_push($Queries, $this->QueryDel('TASKS', $TaskDel)); }
if (isset($FileDel))       { array_push($Queries, $this->QueryDel('FILE_ORDERS', $FileDel)); }
if (isset($LockUseDel))     { array_push($Queries, $this->QueryDel('LOCKS_USE', $LockUseDel)); }
if (isset($NodeDel)) { 
    array_push($Queries, $this->QueryDel('ORDER_STEP_RUNTIMES', $NodeDel, 'JOB_CHAIN_NODE_ID')); 
    array_push($Queries, $this->QueryDel('JOB_CHAIN_NODES', $JobChainDel, 'CHAIN_ID')); 
    array_push($Queries, $this->QueryDel('JOB_CHAIN_NODES', $NodeDel));  
}
if (isset($OrderDel)) 
{ 
    $qry = $this->QueryDel('ORDERS', $OrderDel);
    $res = $data->sql->query( $qry );
}
if (isset($JobDel))         { array_push($Queries, $this->QueryDel('JOBS', $JobDel)); }
if (isset($SpaceDel))  { array_push($Queries, $this->QueryDel('ORDER_ID_SPACES', $SpaceDel)); }
if (isset($Result['spooler']['answer']['state']['job_chains']['job_chain'])) {
    if (isset($JobChainDel))    { 
        array_push($Queries, $this->QueryDel('JOB_CHAINS', $JobChainDel));       
    }
}

if (isset($LockDel))        { array_push($Queries, $this->QueryDel('LOCKS', $LockDel)); }
if (isset($ProcessClassDel)) { array_push($Queries, $this->QueryDel('PROCESS_CLASSES', $ProcessClassDel)); }
if (isset($ConnectionDel))  { array_push($Queries, $this->QueryDel('CONNECTIONS', $ConnectionDel)); }
if (isset($RemoteDel))  { array_push($Queries, $this->QueryDel('REMOTE_SCHEDULERS', $RemoteDel)); }
if (isset($PeriodDel))  { array_push($Queries, $this->QueryDel('PERIODS', $PeriodDel)); }
if (isset($ScheduleDel))  { array_push($Queries, $this->QueryDel('SCHEDULES', $ScheduleDel)); }

$mode='nodebug';
$this->PrintMessage(2,"MODE ".$mode);      
if ($mode=='pack') { 
    $qry = '';
    $nb = 0; $np = 1;
    foreach ($Queries as $q) {
        if ($q!='#') {
            $qry .= "$q;\n";
            $nb++;
        }
        if ($nb >100) {
            $res = $data->sql->multi_query( $qry );
            $this->PrintMessage(2,"PACK $np: $nb ".$res); 
            $qry = ''; $nb=0; $np++;
        }
    }
    if ($qry != '') {
        $res = $data->sql->multi_query( $qry );
        $this->PrintMessage(2,"PACK $np: $nb ".$res); 
    }
}
elseif ($mode=='debug') {
    foreach ($Queries as $q) {
        if ($q!='#') {
            $res = $data->sql->query( $q );            
            $this->PrintMessage(2,"$q ($res)");
        }
    }
}
else {
    $qry = implode(';', $Queries);
    $qry = str_replace (';#','',$qry);
    $res = $data->sql->multi_query( $qry );
}
    $this->PrintMessage(2,"FIN");        
    $this->PrintMessage(1,"Execution: ".(microtime(true)-$timer).'s');
    return 0;
    }

    function PrintMessage($level=0,$message) {
        if ($level>$this->debug) return;
	if ($this->mode == 'BATCH') {
		print str_repeat("\t",$level);
		print "$message\n";
	}
	elseif ($this->mode == 'WEB') {
		print str_repeat("&nbsp;",$level*5);
		print "$message<br/>";
	}
        return;
    }
    
    private function TextProtect($str) {
        $str = str_replace('"','\"',$str);
        return $str;
    }
    
    private function QueryDel($table, $Tableau, $id = 'ID') {
        $Del = array();
        foreach ($Tableau as $k=>$v) {
            if ($v>=0) {
                array_push($Del,$v);
            }
        }
        if (empty($Del)) {
            return '#';
            array_push($Del,-1);
        }
        return 'delete from FOCUS_'.$table.' where '.$id.' in ('.implode(',',$Del).')';
    }

    private function CorrectDatetime($date) {
        return str_replace(array('T','Z'),array(' ',' '),substr($date,0,19));
    }

}
