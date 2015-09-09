<?php
// src/Arii/JOEBundle/Controller/XMLController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Arii\JOEBundle\Entity\Job;
use Symfony\Component\HttpFoundation\Request;

class SOSController extends Controller
{

   public function __construct() {
       
    $this->sos_path  = '../vendor/sos-berlin/scheduler';
   }
   
    //load missing classes and returns an object of the class
    public function get_instance($class, $host = 'localhost', $port = 4444, $extension='.inc.php' ) {
        if ( !class_exists($class) ) {
          include( $this->sos_path.'/'.strtolower($class).$extension );
        }
        $object = new $class;
        $object->host=$host; 
        $object->port=$port;

    return $object;   
    }
     
   public function JobCreateAction()
   {
    $scheduler_host = '192.168.0.49';
    $scheduler_port = 4444;
    
    $request = Request::createFromGlobals();
       
    // On récupère le repository
    $repository = $this->getDoctrine()
                       ->getManager()
                       ->getRepository('AriiJOEBundle:Job');

    // On récupère l'entité correspondant à l'id $id
    $id = $request->get('id' );
    $job = $repository->find( $id );

    // Ou null si aucun article n'a été trouvé avec l'id $id
    if($job === null)
    {
      throw $this->createNotFoundException('Job[id='.$id.'] ?!');
    }
    
    $xml = $this->get_instance('SOS_Scheduler_Job',$scheduler_host,$scheduler_port);
     
    //Setting some properties
//    $job_name = $job->getCategory().'/'.$job->getName();
    $job_name = $job->getName();
    $xml->name   = $job_name;
    $xml->title  = $job->getTitle();
     
    //Set the implentation
    $script = $job->getScript();
    $xml->script('shell')->script_source= $this->AriiScript( $script->getCode(),$job->getJobType() );
/*     
    //The job has a runtime
    $job->run_time()->period()->single_start = "18:00";
     
     
 * 
*/
    
  $modify_hot_folder_command = $this->get_instance('SOS_Scheduler_HotFolder_Launcher',$scheduler_host,$scheduler_port);
  if (! $xml=$modify_hot_folder_command->store($xml, $job->getCategory() ))
        { echo 'error occurred adding job: ' . $modify_hot_folder_command->get_error(); exit; 
  } 
  
/*
    $job_command = $this->get_instance('SOS_Scheduler_JobCommand_Launcher');
    if (! $job_command->remove($job_name))  {
        echo 'ERROR: '.$job_command->get_error(); 
        exit;     
    }  
    if (! $job_command->add_jobs($xml))  {
        echo 'ERROR: '.$job_command->get_error(); 
        exit;     
    }  
 */
    header("Content-Type: text/xml");
    print '<?xml version="1.0" encoding="ISO-8859-1"?>
<scopes>
<GET>
</GET>
</scopes>';
    
    // On interroge le scheduler pour verifier la creation
    exit();
    
   }
   
   public function AriiScript($arii, $target)
   {
       if (substr($target,0,6)!='shell_')
               return $arii;
       
       $Script=array();
       foreach (explode("\n",$arii) as $l) {
           $r = ltrim($l);
           if (substr($r,0,1)=="'") {
               if (($p = strpos($r,'='))>0) {
                   $var = trim(substr($r,0,$p));
                   $val = trim(substr($r,$p));
               }
               if ($target=='shell_windows') {
                   $l = 'REM '.$l;
               }
               else {
                   $l = '# '.$l;                   
               }
           }
           array_push($Script, $l);
      }
      return implode("\n",$Script);
   }
}
