<?php
// src/Arii/JOEBundle/Controller/XMLController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Arii\JOEBundle\Entity\Job;
use Symfony\Component\HttpFoundation\Request;

class XMLController extends Controller
{

   public function JobAction($id)
   {
    // On récupère le repository
    $repository = $this->getDoctrine()
                       ->getManager()
                       ->getRepository('AriiJOEBundle:Job', $id);

    // On récupère l'entité correspondant à l'id $id
    $job = $repository->find($id);

    // Ou null si aucun article n'a été trouvé avec l'id $id
    if($job === null)
    {
      throw $this->createNotFoundException('Job[id='.$id.'] inexistant.');
    }
    
    header("content-type: text/xml");
    /* Entete */
    $xml =  '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
    $xml .= '<job name="'.$job->getName().'"';
    if ($job->getTitle()!='')
        $xml .= ' title="'.$job->getTitle().'"';
    $xml .= ">\n";
/*
    <settings >
        <mail_on_warning ><![CDATA[yes]]></mail_on_warning>

        <history ><![CDATA[yes]]></history>

        <history_on_process ><![CDATA[1]]></history_on_process>

        <history_with_log ><![CDATA[yes]]></history_with_log>
    </settings>

    <params />

    
    /* Script */
    if ($job->getJobType()=='shell_windows') {
        $xml .= '   <script  language="shell">'."\n";
        $xml .= '       <![CDATA['."\n";
        $xml .= 'ping -n 1000 localhost'."\n";
        $xml .= ']]>'."\n";
        $xml .= '   </script>'."\n";
    }
    
    /* Monitor */
/*
    if ($job->getJobType()=='shell_windows') {
    <monitor  name="configuration_monitor" ordering="0">
        <script  language="java" java_class_path="" java_class="sos.scheduler.managed.configuration.ConfigurationOrderMonitor"/>
    </monitor>
*/
/*    
    if ($job->getJobType()=='shell_windows') {
        $xml .= '   <script  language="shell">'."\n";
        $xml .= '       <![CDATA['."\n";
        $xml .= 'ping -n 1000 localhost'."\n";
        $xml .= ']]>'."\n";
        $xml .= '   </script>'."\n";
    }
    <start_when_directory_changed  directory="c:\xampp" regex="eric.test"/>

    <run_time >
        <period  single_start="18:30"/>
    </run_time>

    <commands  on_exit_code="error"/>

    <commands  on_exit_code="success">
        <start_job  job="job0">
            <params >
                <param  name="test" value="ok"/>
            </params>
        </start_job>
    </commands>
*/
    
    $xml .= '</job>'."\n";

    $config = $this->container->getParameter('osjs_config'); 
    print $xml;
    file_put_contents($config.'/live/'.$job->getName().'.job.xml',$xml);
    
    exit();
    return $this->render('AriiJOEBundle:Job:edit.html.twig', array(
      'title' => $job->getTitle()
    ));
  }

   public function updateAction($id)
  {
    // On récupère le repository
    $repository = $this->getDoctrine()
                       ->getManager()
                       ->getRepository('AriiJOEBundle:Job', $id);

    // On récupère l'entité correspondant à l'id $id
    $job = $repository->find($id);

    return $this->render('AriiJOEBundle:Job:edit.html.twig', array(
      'title' => $job->getTitle()
    ));
  }

   public function saveAction()
  {
       print "<pre>";
       print_r($_POST);
       print "</pre>";
       print "???";
    // Création de l'entité
    $job = new Job();
    
    // On recupere les informations de dhtmlx
    $request = Request::createFromGlobals();
    
    $job->setName($request->get('name'));
    $job->setTitle($request->get('title'));
    $job->setJobType( $request->get('job_type') );
    $job->setOrdered( $request->get('order') );
    $job->setMinTasks( $request->get('min_tasks') );
    $job->setProcessClass( $request->get('process_class') );
    $job->setWarnIfLongerThan( $request->get('warn_if_longer_than') );
    $job->setWarnIfShorterThan( $request->get('warn_if_shorter_than') );

    if ($request->get('tasks')>0) 
        $job->setTasks( $request->get('tasks') );
    
    $job->setSpoolerId( '' );

    // On peut ne pas définir ni la date ni la publication
    // Car ces attributs sont définis automatiquement dans le constructeur

    // On récupére l'EntityManager
    $em = $this->getDoctrine()->getManager();

    // Etape 1 : On « persiste » l'entité
    $em->persist($job);

    // Etape 2 : On « flush » tout ce qui a été persisté avant
    $em->flush();
    
       exit();
    // Reste de la méthode qu'on avait déjà écrit
    if( $this->get('request')->getMethod() == 'POST' )
    {
      $this->get('session')->setFlash('notice', 'job enregistré');
      return $this->redirect( $this->generateUrl('arii_JOE_job', array('id' => $job->getId())) );
    }

    return $this->render('AriiJOEBundle:Job:edit.html.twig');
  }

   public function QueueAction($id=-1)
   {
       if ($id<0) {
           $request = Request::createFromGlobals();           
           $id = $request->get('id');
       }
        // On récupère le repository
        $repository = $this->getDoctrine()
                           ->getManager()
                           ->getRepository('AriiJOEBundle:Queue', $id);

        // On récupère l'entité correspondant à l'id $id
        $queue = $repository->find($id);

        // Ou null si aucun article n'a été trouvé avec l'id $id
        if($queue === null)
        {
          throw $this->createNotFoundException('Queue[id='.$id.'] inexistant.');
        }

        header("content-type: text/plain");
        header('Content-Disposition: attachment; filename="test.xml"');
        /* Entete */
        
        $xml =  '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n\n";
        $xml .= '<process_class  name="" max_processes="12" remote_scheduler="pavilion:4444"/>'."\n";

        print "$xml";
        
        exit();
   }
}
