<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class StateController extends Controller
{
	public function test() {
		return '<?xml version="1.0" encoding="ISO-8859-1"?>
<spooler><answer time="2013-02-25 11:03:05.139"><state time="2013-02-25 11:03:05" id="scheduler" spooler_id="scheduler" spooler_running_since="2013-02-25 10:02:01" state="running" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/scheduler-2013-02-25-100201.scheduler.log" version="1.3.12.2117-17032" pid="83012" config_file="C:/SOS-PARIS/jobscheduler/scheduler/config/scheduler.xml" host="Azicom-VAIO" tcp_port="4444" udp_port="4444" db="jdbc  -id=spooler -class=com.mysql.jdbc.Driver jdbc:mysql://localhost:3306/scheduler -user=root" cpu_time="8.393" loop="3982" waits="2289" wait_until="2013-02-25 21:53:00.000"><locks><lock path="/test" name="test" is_free="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/test.lock.xml" last_write_time="2012-09-28T17:26:04.000Z"><requisites/></file_based></lock></locks><jobs><job path="/AJAX" name="AJAX" job="AJAX" state="pending" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.AJAX.log" order="no" tasks="1" in_period="yes" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/AJAX.job.xml" last_write_time="2012-10-27T17:24:43.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/job2" name="job2" job="job2" state="pending" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.job2.log" order="yes" tasks="1" cmd="enable" in_period="yes" job_chain_priority="3" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/job2.job.xml" last_write_time="2012-10-11T15:59:57.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><order_queue length="1"/><log level="info" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/dialog" name="dialog" job="dialog" state="stopped" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.dialog.log" order="no" tasks="1" in_period="yes" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/dialog.job.xml" last_write_time="2012-10-29T09:34:34.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/job1" name="job1" job="job1" state="pending" title="TEST" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.job1.log" order="no" tasks="3" next_start_time="2013-02-25 21:53:00.000" in_period="no" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/job1.job.xml" last_write_time="2012-10-29T09:58:25.000Z"><requisites/></file_based><lock.requestor><lock.use lock="/test" exclusive="no"/></lock.requestor><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/Constraints" name="Constraints" job="Constraints" state="stopped" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.Constraints.log" order="yes" tasks="1" cmd="enable" in_period="yes" job_chain_priority="1" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/Constraints.job.xml" last_write_time="2012-10-29T10:55:22.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><order_queue length="3"/><log level="debug9" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/java_test" name="java_test" job="java_test" state="pending" title="test for dynamic compilation of java code" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.java_test.log" order="no" tasks="1" in_period="yes" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/java_test.job.xml" last_write_time="2012-10-27T17:52:46.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/Post" name="Post" job="Post" state="pending" title="Post files via HTTP" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.Post.log" order="no" tasks="1" has_description="yes" in_period="yes" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/Post.job.xml" last_write_time="2013-02-25T10:02:57.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/job3" name="job3" job="job3" state="pending" title="TEST" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.job3.log" order="no" tasks="3" next_start_time="2013-02-25 21:54:00.000" in_period="no" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/job3.job.xml" last_write_time="2012-10-01T04:57:29.000Z"><requisites/></file_based><lock.requestor><lock.use lock="/test" exclusive="no"/></lock.requestor><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/Variables" name="Variables" job="Variables" state="pending" all_steps="1" all_tasks="1" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.Variables.log" order="no" tasks="1" in_period="yes" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/Variables.job.xml" last_write_time="2012-11-08T19:32:47.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><log level="debug2" highest_level="debug9" last_info="SCHEDULER-930  Task 266521 started - cause: queue_at" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/wait" name="wait" job="wait" state="pending" all_steps="1" all_tasks="1" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.wait.log" order="no" tasks="1" in_period="yes" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/wait.job.xml" last_write_time="2012-10-23T18:08:32.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="debug9" last_info="SCHEDULER-930  Task 266520 started - cause: directory" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/BAC/job1" name="job1" job="job1" state="pending" title="TEST" all_steps="0" all_tasks="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.BAC,job1.log" order="no" tasks="3" next_start_time="2013-02-25 21:53:00.000" in_period="no" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/BAC/job1.job.xml" last_write_time="2012-09-28T19:04:47.000Z"><requisites/></file_based><lock.requestor><lock.use lock="/BAC/test" exclusive="no" is_missing="yes"/></lock.requestor><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="info" last_info="SCHEDULER-893  Job is \'active\' now" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/nouveau/CreateDaysSchedule" name="CreateDaysSchedule" job="CreateDaysSchedule" state="pending" title="Creating a DailySchedule depending on actual Runtimes" all_steps="1" all_tasks="1" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.nouveau,CreateDaysSchedule.log" order="no" tasks="1" has_description="yes" next_start_time="2013-02-26 00:00:01.000" in_period="no" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/cache/nouveau/CreateDaysSchedule.job.xml" last_write_time="2012-08-08T10:40:54.000Z"><requisites/></file_based><tasks count="0"/><queued_tasks length="0"/><log level="info" highest_level="debug3" last_info="SCHEDULER-930  Task 266522 started - cause: queue_at" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job><job path="/Show State" name="Show State" job="Show State" state="not_initialized"><file_based state="not_initialized" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/Show State.job.xml" last_write_time="2013-02-25T09:48:43.000Z"><ERROR code="SCHEDULER-173" text="SCHEDULER-173  Missing script"/><requisites/></file_based></job><job path="/Show" name="Show" job="Show" state="running" all_steps="0" all_tasks="1" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/job.Show.log" order="no" tasks="1" in_period="yes" enabled="yes"><file_based state="active" file="C:/SOS-PARIS/jobscheduler/scheduler/config/live/Show.job.xml" last_write_time="2013-02-25T10:02:33.000Z"><requisites/></file_based><tasks count="1"><task job="/Show" id="266527" task="266527" state="running_process" name="" running_since="2013-02-25 11:03:03.532" enqueued="2013-02-25 11:03:03.454" start_at="2013-02-25 11:03:03.454" cause="queue_at" steps="0" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/task.Show.log" pid="106684" priority="normal" force_start="yes"><log level="info" highest_level="info" last_info="&#13;&#10;C:\SOS-PARIS\jobscheduler\scheduler&gt;bin\jobscheduler.cmd command &quot;&lt;show_state/&gt;&quot;  1&gt;state.txt " mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></task></tasks><queued_tasks length="0"/><log level="info" highest_level="info" last_info="SCHEDULER-930  Task 266527 started - cause: queue_at" mail_on_error="yes" mail_on_warning="yes" smtp="localhost" mail_from="eric@localhost" mail_to="eric@localhost"/></job></jobs><process_classes><process_class path="" max_processes="30" processes="1"><file_based state="active"><requisites/></file_based><processes><process job="Show" task="266527" task_id="266527" running_since="now"/></processes></process_class><process_class path="/(temporaries)" name="(temporaries)" max_processes="30" processes="0"><file_based state="active"><requisites/></file_based></process_class><process_class path="/single" name="single" max_processes="10" processes="0"><file_based state="active"><requisites/></file_based></process_class><process_class path="/multi" name="multi" max_processes="10" processes="0"><file_based state="active"><requisites/></file_based></process_class><process_class path="/agent" name="agent" max_processes="10" remote_scheduler="127.0.0.1/localhost:4443" processes="0"><file_based state="active"><requisites/></file_based></process_class></process_classes><job_chains count="2"/><subprocesses/><remote_schedulers count="0" connected="0"/><http_server/><connections><connection state="ready/receiving" responses="546" received_bytes="1856375" sent_bytes="1885208" operation_type="HTTP"><peer host_ip="127.0.0.1" port="55497"/></connection><connection state="ready/receiving" responses="590" received_bytes="2000612" sent_bytes="2288628" operation_type="HTTP"><peer host_ip="127.0.0.1" port="55503"/></connection><connection state="ready/receiving" responses="342" received_bytes="1170904" sent_bytes="535825" operation_type="HTTP"><peer host_ip="127.0.0.1" port="55504"/></connection><connection state="ready/receiving" responses="312" received_bytes="1065168" sent_bytes="243857" operation_type="HTTP"><peer host_ip="127.0.0.1" port="55505"/></connection><connection state="processing/ready" responses="0" received_bytes="13" sent_bytes="0" operation_type="TCP"><peer host_ip="127.0.0.1" port="55713"/><operation><xml_operation async_state_text="Operation on connection TCP socket(1240,127.0.0.1:55713) (ready), Xml_operation &quot;&lt;show_state/&gt;&quot;"/></operation></connection></connections></state></answer></spooler>
';
	}
    public function postAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->query->get( 'id' );
		print "(($id))";
		exit();
	}

    public function ShowAction()
    {
		$cmd = send_command( '<show_state what="jobs,job_params,job_commands,tasks,task_queue,job_chains,orders,remote_schedulers,schedules"/>', $host, $port);
		if ($cmd=='') {

			FocusAlert( $spooler, 1, 'UNREACHABLE', 'Le Serveur ne r�pond plus !' );
			PrintMessage( "Pas de reponse !",1);
			
			// ERROR !
			// on update l'heure pour ne pas qu'il soit pris par un autre script et on le verrouille
			$qry = 'update state_schedulers set UPDATED=now(),LOCK=0,STATE="unreachable" where id='.$spooler_id; 
			$res = $data->sql->query( $qry );

			if ($mode == 'IMAGE') {
				header("Content-type: image/gif");
				$image = "../images/error.png";
				readfile($image);
			}
			exit();
		}	
	}

    public function UpdateAction()
    {
		$cmd= $this->test();
		
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
		
		$Result = $this->xml2array ( $cmd , 1, 'attributes');

		# Premiere chose : on verifie qui envoie la requete
		// on traite le show_state
/*
<spooler><answer time="2013-02-25 11:03:05.139"><state time="2013-02-25 11:03:05" id="scheduler" spooler_id="scheduler" spooler_running_since="2013-02-25 10:02:01" state="running" log_file="C:/SOS-PARIS/jobscheduler/scheduler/logs/scheduler-2013-02-25-100201.scheduler.log" version="1.3.12.2117-17032" pid="83012" config_file="C:/SOS-PARIS/jobscheduler/scheduler/config/scheduler.xml" host="Azicom-VAIO" tcp_port="4444" udp_port="4444" db="jdbc  -id=spooler -class=com.mysql.jdbc.Driver jdbc:mysql://localhost:3306/scheduler -user=root" cpu_time="8.393" loop="3982" waits="2289" wait_until="2013-02-25 21:53:00.000"><locks><lock path="/test" name="test" is_free="yes">
*/		
		$State = $Result['spooler']['answer']['state']['attr']; 
		$spooler = $State['spooler_id'];
		
		/**********************************************************************************************
			JOBS
		***********************************************************************************************/
		$qry = 'select ID,JOB from JOC_JOBS where SPOOLER_ID="'.$spooler.'"'; 
		$res = $data->sql->query( $qry );
		$JobID = array();
		$c_job = $c_job_u = $c_job_i = $c_job_d = 0; 
		while ( $line = $data->sql->get_next($res) ) {
			$id  =  $line['ID'];
			$job =  $line['JOB'];
			$JobID[$job] = $id;
			$JobAction[$job] = 0;
			$c_job++;
		}

		// on fait une image des taches
		$qry = 'select ID,TASK from JOC_TASKS where SPOOLER_ID="'.$spooler.'"'; 
		$TaskID = array();
		$res = $data->sql->query( $qry );
		$c_task = $c_task_i = $c_task_u =  $c_task_d = 0;
		while ( $line = $data->sql->get_next($res) ) {
		print_r($line);
			$id  =  $line['ID'];
			$task =  $line['TASK'];
			$TaskID[$task] = $id;
			$TaskAction[$task] = 0;
			$c_task++;
		}


		/**********************************************************************************************
			LOCKS
		***********************************************************************************************/
		// on fait une image des verrous
		$qry = 'select ID,LOCK,JOB from JOC_LOCK_USE where SPOOLER_ID="'.$spooler.'"'; 
		$LockUseID = array();
		$res = $data->sql->query( $qry );
		$c_lockuse = $c_lockuse_i = $c_lockuse_u =  $c_lockuse_d = 0;
		while ( $line = $data->sql->get_next($res) ) {
			$id  =  $line['ID'];
			$lock =  $line['LOCK'];
			$job =  $line['JOB'];
			$lockjob = $lock.'#'.$job;
			$LockUseID[$lockjob] = $id;
			$LockAction[$lockjob] = 0;
			$c_lockuse++;
		}
	
		/**********************************************************************************************
			JOBS
		***********************************************************************************************/
		// on traite le show_state
		$Jobs = $Result['spooler']['answer']['state']['jobs']['job'];
		// Cas ou il n'y a qu'un seul job 
		$n = 0;
		if (isset($Result['spooler']['answer']['state']['jobs']['job']['attr'])) {
			$Jobs[$n] = $Result['spooler']['answer']['state']['jobs']['job'];
		}
		// print_r($Jobs);
		while (isset($Jobs[$n])) {
			$job_name = $Jobs[$n]['attr']['name'];
			$p  = substr($Jobs[$n]['attr']['path'],1);
			$path  = dirname($p);
			$file = basename($p);
			$state = $Jobs[$n]['attr']['state'];
			$all_steps = ( isset($Jobs[$n]['attr']['all_steps']) ? $Jobs[$n]['attr']['all_steps'] : 0);	
			$all_tasks = ( isset( $Jobs[$n]['attr']['all_tasks']) ? $Jobs[$n]['attr']['all_tasks'] : 0 );	
			$ordered = ( isset($Jobs[$n]['attr']['order']) and ($Jobs[$n]['attr']['order'] == 'yes' ) ? TRUE : FALSE);
			$tasks = ( isset($Jobs[$n]['attr']['tasks']) ? $Jobs[$n]['attr']['tasks'] : 0 );	
			$in_period = ( isset($Jobs[$n]['attr']['in_period'] ) and ( $Jobs[$n]['attr']['in_period'] == 'yes' ) ? TRUE : FALSE);	
			$enabled = ( isset($Jobs[$n]['attr']['enabled'] ) and ( $Jobs[$n]['attr']['enabled'] == 'yes' ) ? TRUE : FALSE);	
			$has_description = ( isset($Jobs[$n]['attr']['has_description'] ) and ( $Jobs[$n]['attr']['has_description'] == 'yes' ) ? TRUE : FALSE);	
			$next_start_time = ( isset($Jobs[$n]['attr']['next_start_time']) ? $Jobs[$n]['attr']['next_start_time'] : FALSE);	
			$process_class = ( isset($Jobs[$n]['attr']['process_class']) ? $spooler.$Jobs[$n]['attr']['process_class'] : '');	
			$title = ( isset($Jobs[$n]['attr']['title'])  ? $Jobs[$n]['attr']['title'] : '' );	
			$last_write_time = '';
			if (isset($Jobs[$n]['file_based']['attr']['last_write_time'])) {
				$last_write_time = $Jobs[$n]['file_based']['attr']['last_write_time'];
			}
			$last_info = '';
			if (isset($Jobs[$n]['log']['attr']['last_info'])) {
				$last_info = $Jobs[$n]['log']['attr']['last_info'];	
				$last_info = preg_replace('/"/','\"',$last_info);
			}
			$last_warning = '';
			if (isset($Jobs[$n]['log']['attr']['last_warning'])) {
				$last_warning = $Jobs[$n]['log']['attr']['last_warning'];	
				$last_warning = preg_replace('/"/','\"',$last_warning);
			}
			if ($path=='.') {
				$path='';
				$job = $job_name;
			}
			else {
				$job = "$path/$job_name";
			}
			# ID COMPLET
			$job = "$spooler/$job";
			$Titre[$job] = $title;
			// Si l'ID existe, on update
			// Sinon on insert
		//	print $job_name;
			if (isset($JobID[$job])) {
				$qry = 'update JOC_JOBS set PATH="'.$path.'",FILE="'.$file.'",JOB="'.$job.'",STATE="'.$state.'",ALL_STEPS="'.$all_steps.'",ALL_TASKS="'.$all_tasks.'",ORDERED="'.$ordered.'",TASKS="'.$tasks.'",IN_PERIOD="'.$in_period.'",ENABLED="'.$enabled.'",LAST_WRITE_TIME="'.$last_write_time.'",LAST_INFO="'.$last_info.'",LAST_WARNING="'.$last_warning.'",HAS_DESCRIPTION="'.$has_description.'",TITLE="'.$title.'",NEXT_START_TIME="'.$next_start_time.'",PROCESS_CLASS="'.$process_class.'" where id='.$JobID[$job];
				$c_job_u++;
			}
			else {
				$qry = 'insert into JOC_JOBS (SPOOLER_ID,PATH,FILE,JOB,STATE,ALL_STEPS,ALL_TASKS,ORDERED,TASKS,IN_PERIOD,ENABLED,LAST_WRITE_TIME,LAST_INFO,LAST_WARNING,HAS_DESCRIPTION,TITLE,NEXT_START_TIME,PROCESS_CLASS) values ( "'.$spooler.'", "'.$path.'", "'.$file.'", "'.$job.'", "'.$state.'", "'.$all_steps.'", "'.$all_tasks.'", "'.$ordered.'", "'.$tasks.'", "'.$in_period.'", "'.$enabled.'", "'.$last_write_time.'", "'.$last_info.'", "'.$last_warning.'", "'.$has_description.'", "'.$title.'", "'.$next_start_time.'", "'.$process_class.'" )';
				$c_job_i++;
			}
		//	print $qry;
			$res = $data->sql->query( $qry );
			// on tag pour supprimer les autres
			$JobAction[$job] = 1;

			// Sauvegarde des taches
			if (isset($Jobs[$n]['tasks']['task'])) {
		/*	print "<pre>";
			print_r($Jobs[$n]['tasks']['task']);
			print "</pre>";
		*/		$Tasks = $Jobs[$n]['tasks']['task'];
				$nt = 0;
				if (isset($Tasks['attr'])) {
					$Tasks[$nt] = $Jobs[$n]['tasks']['task'];
				}
				while (isset($Tasks[$nt])) {
					if (isset($Tasks[$nt]['attr'])) {
						$task_id = $Tasks[$nt]['attr']['id'];
						$state = $Tasks[$nt]['attr']['state'];
						$name = $Tasks[$nt]['attr']['name'];
						$running_since = $Tasks[$nt]['attr']['running_since'];
						$enqueued = ( isset( $Tasks[$nt]['attr']['enqueued'] ) ? $Tasks[$nt]['attr']['enqueued'] : '' );
						$start_at = $Tasks[$nt]['attr']['start_at'];
						$cause = $Tasks[$nt]['attr']['cause'];
						$steps = $Tasks[$nt]['attr']['steps'];
						$log_file = $Tasks[$nt]['attr']['log_file'];
						$pid = $Tasks[$nt]['attr']['pid'];
						$priority = $Tasks[$nt]['attr']['priority'];
						$force_start = ( $Tasks[$nt]['attr']['force_start'] == 'yes' ? 1 : 0 );
						
						// identifiant
						$task = $job."/".$task_id;
						if (isset($TaskID[$task])) {
							$qry = 'update JOC_TASKS set TASK="'.$task.'",STATE="'.$state.'",NAME="'.$name.'",RUNNING_SINCE="'.$running_since.'",ENQUEUED="'.$enqueued.'",START_AT="'.$start_at.'",CAUSE="'.$cause.'",STEPS="'.$steps.'",PID="'.$pid.'",PRIORITY="'.$priority.'",FORCE_START="'.$force_start.'" where id='.$TaskID[$task];
							$c_task_u++;
						}
						else {
							$qry = 'insert into JOC_TASKS (TASK,SPOOLER_ID,JOB,TASK_ID,STATE,NAME,RUNNING_SINCE,ENQUEUED,START_AT,CAUSE,STEPS,PID,PRIORITY,FORCE_START) values ( "'.$task.'", "'.$spooler.'", "'.$job.'", "'.$task_id.'", "'.$state.'", "'.$name.'", "'.$running_since.'", "'.$enqueued.'", "'.$start_at.'", "'.$cause.'", "'.$steps.'", "'.$pid.'", "'.$priority.'", "'.$force_start.'" )';
							$c_task_i++;
						}
						print "<br/>$qry<br/>";
						//	print $qry;
						$res = $data->sql->query( $qry );
					}
					$nt++;
					$TaskAction[$task]=1;
				}
			}
			
			/**********************************************************************************************
				LOCKS USE
			***********************************************************************************************/
			// Utilisation des verrous
			if (isset($Jobs[$n]['lock.requestor'])) {
				$Locks = $Jobs[$n]['lock.requestor'];
				if (isset($Locks['lock.use'])) {
					if (isset($Locks['lock.use']['attr'])) {
						$Locks['lock.use'][0]['attr']['lock']      = $Locks['lock.use']['attr']['lock'];
						$Locks['lock.use'][0]['attr']['exclusive'] = $Locks['lock.use']['attr']['exclusive'];
					}
					$nl = 0;
					while (isset($Locks['lock.use'][$nl]['attr'])) {
						$lock = $Locks['lock.use'][$nl]['attr']['lock'];
						$path = str_replace('\\','/',dirname($lock));
						$name = basename($lock);
						$exclusive = ( isset($Locks['lock.use'][$nl]['attr']['exclusive'] ) and ( $Locks['lock.use'][$nl]['attr']['exclusive'] == 'yes' ) ? TRUE : FALSE);
						$lock = $spooler.$lock;
						$lockjob = $lock.'#'.$job;
						if (isset($LockUseID[$lockjob])) {
							$qry = 'update JOC_LOCK_USE set EXCLUSIVE="'.$exclusive.'" where id='.$LockUseID[$lockjob];
							$c_lockuse_u++;
						}
						else {
							$qry = 'insert into JOC_LOCK_USE (SPOOLER_ID,PATH,NAME,LOCK,JOB,EXCLUSIVE) values ( "'.$spooler.'", "'.$path.'", "'.$name.'", "'.$lock.'", "'.$job.'", "'.$exclusive.'" )';
							$c_lockuse_i++;
						}
						$res = $data->sql->query( $qry );
						$nl++;
						$LockAction[$lockjob] = 1;
					}
				}
			}
			$n++;
		}

		// On nettoie ce qui a �t� supprim�
		foreach ($JobID as $job=>$id) {
			if ($JobAction[$job] == 0) {
				$qry = 'delete from JOC_JOBS where id='.$id;
				$res = $data->sql->query( $qry );
				$c_job_d++;
			}
		}

		PrintMessage( "Job $c_job",1);
		PrintMessage( "+ $c_job_i",2);
		PrintMessage( "= $c_job_u",2);
		PrintMessage( "- $c_job_d",2);

		// On nettoie les taches
		foreach ($TaskID as $task=>$id) {
			if ($TaskAction[$task] == 0 ) {
				$qry = 'delete from JOC_TASKS where id='.$id;
				$c_task_d++;
				$res = $data->sql->query( $qry );
			}
		}
		PrintMessage( "Tasks $c_task",1);
		PrintMessage( "+ $c_task_i",2);
		PrintMessage( "= $c_task_u",2);
		PrintMessage( "- $c_task_d",2);

		// On nettoie les verrous
		foreach ($LockUseID as $lockjob=>$id) {
			if ($LockAction[$lockjob] == 0 ) {
				$qry = 'delete from JOC_LOCK_USE where id='.$id;
				$c_lockuse_d++;
				$res = $data->sql->query( $qry );
			}
		}
		PrintMessage( "Locks use $c_lockuse",1);
		PrintMessage( "+ $c_lockuse_i",2);
		PrintMessage( "= $c_lockuse_u",2);
		PrintMessage( "- $c_lockuse_d",2);

		//=PROCESS CLASSES================================================================================================
		$qry = 'select ID,JOB_CHAIN as JOB from JOC_JOB_CHAINS where SPOOLER_ID="'.$spooler.'"'; 
		$res = $data->sql->query( $qry );
		$JobID = array();
		$c_jobchain = $c_jobchain_i = $c_jobchain_u = $c_jobchain_d = 0;
		while ( $line = $data->sql->get_next($res) ) {
			$id  =  $line['ID'];
			$job =  $line['JOB'];
			$JobID[$job] = $id;
			$JobAction[$job] = 0;
			$c_jobchain++;
		}

		// Puis les job_chain_nodes 
		$qry = 'select ID,NODE from JOC_JOB_CHAIN_NODES where SPOOLER_ID="'.$spooler.'"'; 
		$res = $data->sql->query( $qry );
		$NodeID = array();
		$c_jobchainnode = $c_jobchainnode_i = $c_jobchainnode_u = $c_jobchainnode_d = 0;
		while ( $line = $data->sql->get_next($res) ) {
			$id  =  $line['ID'];
			$node =  $line['NODE'];
			$NodeID[$node] = $id;
			$NodeAction[$node] = 0;
			$c_jobchainnode++;
		}

		// et enfin les ordres
		$qry = 'select ID,ORDER_ID from JOC_ORDERS where SPOOLER_ID="'.$spooler.'"'; 
		$res = $data->sql->query( $qry );
		$OrderID = array();
		$c_order = $c_order_i = $c_order_u = $c_order_d = 0;
		while ( $line = $data->sql->get_next($res) ) {
			$id  =  $line['ID'];
			$order =  $line['ORDER_ID'];
			$OrderID[$order] = $id;
			$OrderAction[$order] = 0;
			$c_order++;
		}

		// on traite le show_state
		if (isset($Result['spooler']['answer']['state']['job_chains']['job_chain'])) {
			$Jobs = $Result['spooler']['answer']['state']['job_chains']['job_chain'];
			// print_r($Jobs);
			$n = 0;
			if (isset($Jobs['attr'])) {
				$Jobs[$n] = $Result['spooler']['answer']['state']['job_chains']['job_chain'];
			}
			while (isset($Jobs[$n])) {
				$job_name = $Jobs[$n]['attr']['name'];
				$p  = substr($Jobs[$n]['attr']['path'],1);
				$path  = dirname($p);
				$file = basename($p);
				$state = $Jobs[$n]['attr']['state'];
				$orders = ( isset($Jobs[$n]['attr']['orders']) ? $Jobs[$n]['attr']['orders'] : 0);	
				$running_orders = ( isset($Jobs[$n]['attr']['running_orders']) ? $Jobs[$n]['attr']['running_orders'] : 0);	
				$orders_recoverable = ( isset($Jobs[$n]['attr']['order']) and ($Jobs[$n]['attr']['order'] == 'yes' ) ? TRUE : FALSE);
				$title = ( isset($Jobs[$n]['attr']['title']) ? $Jobs[$n]['attr']['title'] : '' );
				$last_write_time = '';
				if (isset($Jobs[$n]['file_based']['attr']['last_write_time'])) {
					$last_write_time = $Jobs[$n]['file_based']['attr']['last_write_time'];
				}
				if ($path=='.') {
					$path='';
					$job = $job_name;
				}
				else {
					$job = "$path/$job_name";
				}
				$job="$spooler/$job";
				// Si l'ID existe, on update
				// Sinon on insert
				if (isset($JobID[$job])) {
					$qry = 'update JOC_JOB_CHAINS set PATH="'.$path.'",FILE="'.$file.'",JOB_CHAIN="'.$job.'",STATE="'.$state.'",TITLE="'.$title.'",ORDERS_RECOVERABLE="'.$orders_recoverable.'",ORDERS="'.$orders.'",RUNNING_ORDERS="'.$running_orders.'",LAST_WRITE_TIME="'.$last_write_time.'" where id='.$JobID[$job];
					$c_jobchain_u++;
				}
				else {
					$qry = 'insert into JOC_JOB_CHAINS (SPOOLER_ID,PATH,FILE,JOB_CHAIN,TITLE,STATE,ORDERS_RECOVERABLE,ORDERS,RUNNING_ORDERS,LAST_WRITE_TIME) values ( "'.$spooler.'", "'.$path.'", "'.$file.'", "'.$job.'", "'.$title.'", "'.$state.'", "'.$orders_recoverable.'", "'.$orders.'", "'.$running_orders.'", "'.$last_write_time.'" )';
					$c_jobchain_i++;
				}
				$res = $data->sql->query( $qry );
				
				// On passe au job_chain_nodes 
				if (isset(  $Jobs[$n]['job_chain_node'] ) ) {
					$Nodes = $Jobs[$n]['job_chain_node'];
					$nn=0;
					while (isset($Nodes[$nn])) {
						$state = $Nodes[$nn]['attr']['state'];
						$node = "$job/$state";
						$next_state = (isset($Nodes[$nn]['attr']['next_state']) ? $Nodes[$nn]['attr']['next_state'] : '');
						$error_state = (isset($Nodes[$nn]['attr']['error_state']) ? $Nodes[$nn]['attr']['error_state'] : '');
						$action = (isset($Nodes[$nn]['attr']['action']) ? $Nodes[$nn]['attr']['action'] : '');
						$jobcmd = ( isset($Nodes[$nn]['attr']['job']) ? $spooler.$Nodes[$nn]['attr']['job'] : '' );

						# On ajoute le titre du job
						$title = '';
						if (isset($Titre[$jobcmd])) {
							$title = $Titre[$jobcmd];
						}
						
						if (isset($NodeID[$node])) {
							$qry = 'update JOC_JOB_CHAIN_NODES set NODE="'.$node.'", JOB_CHAIN="'.$job.'",JOB="'.$jobcmd.'",STATE="'.$state.'",TITLE="'.$title.'",NEXT_STATE="'.$next_state.'",ERROR_STATE="'.$error_state.'",ACTION="'.$action.'" where id='.$NodeID[$node];
							$c_jobchainnode_u++;
						}
						else {
							$qry = 'insert into JOC_JOB_CHAIN_NODES (SPOOLER_ID,NODE,JOB_CHAIN,JOB,STATE,TITLE,NEXT_STATE,ERROR_STATE,ACTION) values ( "'.$spooler.'", "'.$node.'", "'.$job.'", "'.$jobcmd.'", "'.$state.'", "'.$title.'", "'.$next_state.'", "'.$error_state.'", "'.$action.'" )';
							$c_jobchainnode_i++;
						}

						$res = $data->sql->query( $qry );
						
						# A l'interieur du noeud, on regarde si il y a un ordre
						if (isset($Nodes[$nn]['order_queue']['order'])) {
							$Orders = $Nodes[$nn]['order_queue']['order'];
							// print_r($Orders);
							$no = 0;
							
							// si  ce n'est pas un tableau, on le transforme
							if (isset($Orders['attr'])) {
								$Orders[$no] = $Orders;
							}
							while (isset($Orders[$no])) {
								$state= $Orders[$no]['attr']['state'];
								$p = $Orders[$no]['attr']['path'];
								$path = substr(dirname($p),1);
								$path = ($path == '\\' ? '/' : $path ); // dirname bizarre sur Windows
								$file = basename($p);
								$order_id = (isset($Orders[$no]['attr']['order']) ? $Orders[$no]['attr']['order']:'');
								$state= $Orders[$no]['attr']['state'];
								$title= (isset($Orders[$no]['attr']['title']) ? $Orders[$no]['attr']['title'] : '');
								$next_start_time= (isset($Orders[$no]['attr']['next_start_time']) ? $Orders[$no]['attr']['next_start_time'] : '');
								$start_time= (isset($Orders[$no]['attr']['start_time']) ? $Orders[$no]['attr']['start_time'] : '');
								$history_id= (isset($Orders[$no]['attr']['history_id']) ? $Orders[$no]['attr']['history_id'] : '');
								$task= (isset($Orders[$no]['attr']['task']) ? $Orders[$no]['attr']['task'] : '');
								$in_process_since = (isset($Orders[$no]['attr']['in_process_since']) ? $Orders[$no]['attr']['in_process_since'] : '');
								$touched = (isset($Orders[$no]['attr']['touched']) ? $Orders[$no]['attr']['touched'] : '');
								$created= (isset($Orders[$no]['attr']['created']) ? $Orders[$no]['attr']['created'] : '');
								$last_write_time= (isset($Orders[$no]['file_based']['attr']['last_write_time']) ? $Orders[$no]['file_based']['attr']['last_write_time'] : '');
								$initial_state = $Orders[$no]['attr']['initial_state'];
								$end_state = (isset($Orders[$no]['attr']['end_state']) ? $Orders[$no]['attr']['end_state'] : '' );
								$suspended = (isset($Orders[$no]['attr']['suspended']) and ($Orders[$no]['attr']['suspended']=='yes') ? TRUE : FALSE);
								$order = $node.'/'.$order_id;
								
								if (isset($OrderID[$order])) {
									$qry = 'update JOC_ORDERS set ORDER_ID="'.$order.'", PATH="'.$path.'", FILE="'.$file.'", NODE="'.$node.'", JOB_CHAIN="'.$job.'", NEXT_START_TIME="'.$next_start_time.'", TITLE="'.$title.'", SUSPENDED="'.$suspended.'", HISTORY_ID="'.$history_id.'", TASK="'.$task.'", IN_PROCESS_SINCE="'.$in_process_since.'", TOUCHED="'.$touched.'", LAST_WRITE_TIME="'.$last_write_time.'", STATE="'.$state.'", INITIAL_STATE="'.$initial_state.'", END_STATE="'.$end_state.'", CREATED="'.$created.'" where id='.$OrderID[$order];
									$c_order_u++;
								}
								else {
									$qry = 'insert into JOC_ORDERS (SPOOLER_ID,ORDER_ID,PATH,FILE,NODE,JOB_CHAIN,NEXT_START_TIME,TITLE,SUSPENDED,HISTORY_ID,TASK,IN_PROCESS_SINCE,TOUCHED,LAST_WRITE_TIME,STATE,INITIAL_STATE,END_STATE,CREATED) values ( "'.$spooler.'", "'.$order.'", "'.$path.'", "'.$file.'", "'.$node.'", "'.$job.'", "'.$next_start_time.'", "'.$title.'", "'.$suspended.'", "'.$history_id.'", "'.$task.'", "'.$in_process_since.'", "'.$touched.'", "'.$last_write_time.'", "'.$state.'", "'.$initial_state.'", "'.$end_state.'", "'.$created.'" )';
									$c_order_u++;
								}
								$res = $data->sql->query( $qry );
								
								// on tag pour supprimer les autres
								$OrderAction[$order] = 1;
								$no++;
							}
							
						}
						
						// on tag pour supprimer les autres
						$NodeAction[$node] = 1;
						$nn++;
						
					}
				}
				
				// on tag pour supprimer les autres
				$JobAction[$job] = 1;
				$n++;

			}
		}
		// On nettoie ce qui a �t� supprim�
		foreach ($JobID as $job=>$id) {
			if ($JobAction[$job] == 0) {
				$qry = 'delete from state_job_chains where id='.$id;
				$res = $data->sql->query( $qry );
				$c_jobchain_d++;
			}
		}

		PrintMessage( "Job_chains $c_jobchain",1);
		PrintMessage( "+ $c_jobchain_i",2);
		PrintMessage( "= $c_jobchain_u",2);
		PrintMessage( "- $c_jobchain_d",2);

		foreach ($OrderID as $order=>$id) {
			if ($OrderAction[$order] == 0) {
				$qry = 'delete from state_orders where id='.$id;
				$res = $data->sql->query( $qry );
				$c_order_d++;
			}
		}

		PrintMessage( "Orders $c_order",1);
		PrintMessage( "+ $c_order_i",2);
		PrintMessage( "= $c_order_u",2);
		PrintMessage( "- $c_order_d",2);

		foreach ($NodeID as $node=>$id) {
			if ($NodeAction[$node] == 0) {
				$qry = 'delete from state_job_chain_nodes where id='.$id;
				$res = $data->sql->query( $qry );
			}
		}

		PrintMessage( "Job_chain_nodes $c_jobchainnode",1);
		PrintMessage( "+ $c_jobchainnode_i",2);
		PrintMessage( "= $c_jobchainnode_u",2);
		PrintMessage( "- $c_jobchainnode_d",2);

		//=PROCESS CLASSES================================================================================================
		$qry = 'select ID,LOCK from JOC_LOCKS where SPOOLER_ID="'.$spooler.'"'; 
		$res = $data->sql->query( $qry );
		$DBLock = array();
		$c_lock = $c_lock_i = $c_lock_u = $c_lock_d = 0;
		while ( $line = $data->sql->get_next($res) ) {
			$lock  =  $line['lock'];
			$id  =  $line['id'];
			$DBLock[$lock]=$id;
			$c_lock++;
		}

		if (isset($Result['spooler']['answer']['state']['locks']['lock'])) {
			$Locks = $Result['spooler']['answer']['state']['locks']['lock'];
			//print_r($Locks);
			$n = 0;
			while (isset($Locks[$n])) {
				$path = $Locks[$n]['attr']['path'];
				$name = $Locks[$n]['attr']['name'];
				$is_free = $Locks[$n]['attr']['is_free'];
				if (isset($Locks[$n]['attr']['max_non_exclusive']))
					$max_non_exclusive = $Locks[$n]['attr']['max_non_exclusive'];
				else 
					$max_non_exclusive = -1;
				$state = $Locks[$n]['file_based']['attr']['state'];
				$lock = $spooler.$path;
				$path = dirname($path);
				$path = str_replace('\\','/',$path);
				
				if (isset($DBLock[$lock])) {
					$qry = 'update JOB_LOCKS set LOCK="'.$lock.'", PATH="'.$path.'", NAME="'.$name.'", IS_FREE="'.$is_free.'", MAX_NON_EXCLUSIVE="'.$max_non_exclusive.'", state="'.$state.'" where id='.$DBLock[$lock];
					$c_lock_u++;
					$DBLock[$lock] = -1;
				}
				else {
					$qry = 'insert into JOB_LOCKS (LOCK,SPOOLER_ID,PATH,NAME,IS_FREE,STATE) values ( "'.$lock.'", "'.$spooler.'", "'.$path.'", "'.$name.'", "'.$is_free.'", "'.$state.'" )';		
					$c_lock_i++;
				}
			//	print $qry;
				$res = $data->sql->query( $qry );
				$n++;
			}
		}

		// On nettoie ce qui a �t� supprim�
		foreach ($DBLock as $lock=>$id) {
			if ($id > -1) {
				$qry = 'delete from JOC_JOBS where id='.$id;
				$c_lock_d++;
				$res = $data->sql->query( $qry );
			}
		}
		PrintMessage( "Locks $c_lock",1);
		PrintMessage( "+ $c_lock_i",2);
		PrintMessage( "= $c_lock_u",2);
		PrintMessage( "- $c_lock_d",2);

		//=PROCESS CLASSES================================================================================================
		$qry = 'select ID,PROCESS_CLASS from JOC_PROCESS_CLASSES where SPOOLER_ID="'.$spooler.'"'; 
		$res = $data->sql->query( $qry );
		$DBProcess = array();
		$c_process = $c_process_i = $c_process_u = $c_process_d = 0; 
		while ( $line = $data->sql->get_next($res) ) {
			$process  =  $line['PROCESS_CLASS'];
			$id  =  $line['ID'];
			$DBProcess[$process]=$id;
			$c_process++;
		}

		$Classes = $Result['spooler']['answer']['state']['process_classes']['process_class'];
		// print_r($Classes);
		$n = 0;
		while (isset($Classes[$n])) {
			$path = $Classes[$n]['attr']['path'];
			if (isset($Classes[$n]['attr']['name'])) {
				$name = $Classes[$n]['attr']['name'];
			}
			else {
				$name = '';
			} 
			$max_processes = $Classes[$n]['attr']['max_processes'];
			$processes = $Classes[$n]['attr']['processes'];
			if (isset($Classes[$n]['attr']['remote_scheduler']))
				$remote_scheduler = $Classes[$n]['attr']['remote_scheduler'];
			else 
				$remote_scheduler = '';
			$state = $Classes[$n]['file_based']['attr']['state'];
			if (isset($Classes[$n]['file_based']['attr']['file']))
				$file = $Classes[$n]['file_based']['attr']['file'];
			else
				$file = '';
			if (isset($Classes[$n]['file_based']['attr']['last_write_time']))
				$last_write_time = $Classes[$n]['file_based']['attr']['last_write_time'];
			else
				$last_write_time = '';
			$process = $spooler.$path;
			$path = dirname($path);
			$path = str_replace('\\','/',$path);
			
			if (isset($DBProcess[$process])) {
				$qry = 'update JOC_PROCESS_CLASSES set PROCESS_CLASS="'.$process.'", PATH="'.$path.'", NAME="'.$name.'", REMOTE_SCHEDULER="'.$remote_scheduler.'", MAX_PROCESSES="'.$max_processes.'", PROCESSES="'.$processes.'", state="'.$state.'", file="'.$file.'", LAST_WRITE_TIME="'.$last_write_time.'" where id='.$DBProcess[$process];
				$c_process_u++;
				$DBProcess[$process] = -1;
			}
			else {
				$qry = 'insert into JOC_PROCESS_CLASSES (PROCESS_CLASS,SPOOLER_ID,PATH,NAME,REMOTE_SCHEDULER,MAX_PROCESSES,PROCESSES,STATE,FILE,LAST_WRITE_TIME) values ( "'.$process.'", "'.$spooler.'", "'.$path.'", "'.$name.'", "'.$remote_scheduler.'", "'.$max_processes.'", "'.$processes.'", "'.$state.'", "'.$file.'", "'.$last_write_time.'" )';		
				$c_process_i++;
			}
			// print $qry;
			$res = $data->sql->query( $qry );
			$n++;
		}
		// On nettoie ce qui a �t� supprim�
		foreach ($DBProcess as $process=>$id) {
			if ($id > -1) {
				$qry = 'delete from JOC_PROCESS_CLASSES where id='.$id;
				$c_process_d++;
				$res = $data->sql->query( $qry );
			}
		}
		PrintMessage( "Process classes $c_process",1);
		PrintMessage( "+ $c_process_i",2);
		PrintMessage( "= $c_process_u",2);
		PrintMessage( "- $c_process_d",2);

		//=PROCESS CLASSES================================================================================================



		// Mise a jour des serveurs
		// On finit par le scheduler
		// on est toujours en update
		$Scheduler = $Result['spooler']['answer']['state']['attr'];
		$Update = array();
		foreach (array('time','spooler_running_since','state','log_file','version','pid','config_file','udp_port','db','cpu_time','waits','wait_until') as $k) {
			array_push($Update,$k.'="'.$Scheduler[$k].'"');
		}
		# cas speciaux
		$need_db = ( $Scheduler['need_db'] == 'yes' ? TRUE : FALSE );
		array_push($Update,'need_db="'.$need_db.'"');
		$qry = 'update JOC_SCHEDULERS set '.implode(',',$Update).',LOCK=0 where id='.$spooler_id;

		$res = $data->sql->query( $qry );

		function PrintMessage($message,$level=0) {
		global $mode;

			if ($mode == 'BATCH') {
				print str_repeat("\t",$level);
				print "$message\n";
			}
			elseif ($mode == 'WEB') {
				print str_repeat("&nbsp;",$level*5);
				print "$message<br/>";
			}
		}

		function SendAlert( $spooler, $type, $alert, $message ) {
		global $data;
			$qry = 'insert into JOC_ALERTS (SPOOLER_ID,TYPE,ALERT,MESSAGE) values ( "'.$spooler.'", "'.$alert.'", "'.$alert.'", "'.$message.'" )';;
			$res = $data->sql->query( $qry );
		}
	}

/**
 * xml2array() will convert the given XML text to an array in the XML structure.
 * Link: http://www.bin-co.com/php/scripts/xml2array/
 * Arguments : $contents - The XML text
 *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
 *                $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
 * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.
 * Examples: $array =  xml2array(file_get_contents('feed.xml'));
 *              $array =  xml2array(file_get_contents('feed.xml', 1, 'attribute'));
 */
function xml2array($contents, $get_attributes=1, $priority = 'tag') {
    if(!$contents) return array();

    if(!function_exists('xml_parser_create')) {
        //print "'xml_parser_create()' function not found!";
        return array();
    }

    //Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);

    if(!$xml_values) return;//Hmm...

    //Initializations
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();

    $current = &$xml_array; //Refference

    //Go through the tags.
    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
    foreach($xml_values as $data) {
        unset($attributes,$value);//Remove existing values, or there will be trouble

        //This command will extract these variables into the foreach scope
        // tag(string), type(string), level(int), attributes(array).
        extract($data);//We could use the array by itself, but this cooler.

        $result = array();
        $attributes_data = array();
        
        if(isset($value)) {
            if($priority == 'tag') $result = $value;
            else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
        }

        //Set the attributes too.
        if(isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
                if($priority == 'tag') $attributes_data[$attr] = $val;
                else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }

        //See tag status and do the needed.
        if($type == "open") {//The starting of the tag '<tag>'
            $parent[$level-1] = &$current;
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                $repeated_tag_index[$tag.'_'.$level] = 1;

                $current = &$current[$tag];

            } else { //There was another element with the same tag name

                if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    $repeated_tag_index[$tag.'_'.$level]++;
                } else {//This section will make the value an array if multiple tags with the same name appear together
                    $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                    $repeated_tag_index[$tag.'_'.$level] = 2;
                    
                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                        $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                        unset($current[$tag.'_attr']);
                    }

                }
                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                $current = &$current[$tag][$last_item_index];
            }

        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
            //See if the key is already taken.
            if(!isset($current[$tag])) { //New Key
                $current[$tag] = $result;
                $repeated_tag_index[$tag.'_'.$level] = 1;
                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

            } else { //If taken, put all things inside a list(array)
                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                    // ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    
                    if($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag.'_'.$level]++;

                } else { //If it is not an array...
                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $get_attributes) {
                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
                        
                        if($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                }
            }

        } elseif($type == 'close') { //End of tag '</tag>'
            $current = &$parent[$level-1];
        }
    }
    
    return($xml_array);
} 

}

