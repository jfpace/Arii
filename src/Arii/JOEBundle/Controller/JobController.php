<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Entity\ParamJob;
use Arii\JOEBundle\Entity\Script;

class JobController extends Controller
{

    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Job:index.html.twig');
    }

    // Creation d'un job
    public function createAction() {
        $job = file_get_contents('D:\arii\enterprises\SOS-Paris\scheduler\config\live\everial\Echo.job.xml');
        $tools = $this->container->get('arii_core.tools');
        $JobInfos = $tools->xml2array($job, 1, 'tag');
        $joe = $this->container->get('arii_core.joe');
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $response->setContent($joe->Job($JobInfos));
    }
            
    public function edit_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOEBundle:Job:toolbar.xml.twig',array(), $response );
    }

    public function formAction($mode='new')
    {
        $request = Request::createFromGlobals();
        $type = 'default';
        if ($request->get('type')) {
            $type = $request->get('type' );
        }
        $id = -1;
        if ($mode == 'edit') {
             if ($request->get('id')) {
                 $id = $request->get('id' );
             }
        }
        return $this->render('AriiJOEBundle:Forms:'.$type.'.html.twig', array( 'mode' => 'test', 'type'=> $type, 'id' => $id ));
    }
    
    public function editAction($mode='new')
    {
        $request = Request::createFromGlobals();
        // en cas d'edit on retrouve l'id par le request (demandé par dhtmlx)
        if ($request->get('id')) {
            $id = $request->get('id' );
            
            // On récupère le repository
            $repository = $this->getDoctrine()
                               ->getManager()
                               ->getRepository('AriiJOEBundle:Job');

            // On récupère l'entité correspondant à l'id $id
            $job = $repository->find($id);
        }
        else {
            // Valeur par défaut
            $id = 0;
        }
            $Infos = array(  
                "id" => $id,
                "name" => "job",
                "title" => "New job",
                "path" => "/",
                "type" => "shell"
            );
                    
        return $this->render('AriiJOEBundle:Job:edit.html.twig', $Infos );
    }
    
   public function saveAction()
  {
    // On recupere les informations de dhtmlx
    $request = Request::createFromGlobals();

    // On récupère le repository
    $repository = $this->getDoctrine()
                       ->getManager()
                       ->getRepository('AriiJOEBundle:Job');
    // print_r($_POST);

    // On récupère l'entité correspondant à l'id $id
    $job = $repository->find($request->get('id'));
    if($job === null ) {
        // Création de l'entité
        $job = new Job();
        print "New JOB !<hr/>";
    }

    // On récupére l'EntityManager
    $em = $this->getDoctrine()->getManager();
    $job->setName( $request->get('name') );
    $job->setTitle( $request->get('title') );

    $job->setCategory( $request->get('category' )) ;
    $job->setActivated(true);
    $job->setJobType( $request->get('job_type') );
    $job->setMinTasks( $request->get('min_tasks') );
    $job->setProcessClass( $request->get('process_class') );
    $job->setWarnIfLongerThan( $request->get('warn_if_longer_than') );
    $job->setWarnIfShorterThan( $request->get('warn_if_shorter_than') );

    if ($request->get('tasks')>0) 
        $job->setTasks( $request->get('tasks') );

    $job->setStartWhenDirectoryChanged( ($request->get('file_time') == 'file_watcher') );
    $job->setDirectory( $request->get('directory') );
    $job->setRegex( $request->get('regex') );

    $job->setSendEvent( $request->get('sendevent') );
    $job->setEventClass( $request->get('event_class') );
    $job->setEventId( $request->get('event_id') );
    
    $job->setSpoolerId( '' );

    $em->persist($job);

    // on attache le script
/*
    $script = new Script();
    $script->setLanguage('shell');
    $script->setCode( $request->get('code') );
    $job->setScript($script);
    
    $em->persist($script);

 */
/*    
    // on supprime les anciens parametres
    $Params = $job->GetParamsJob();
    foreach ($Params as $p) {
        $em->remove($p);
    }
    
    // On recupere les parametres de la grille 
    $n=0;
    for($i=1;$i<30;$i++) {
        if ($request->get('var'.$i)!='') {
            $param{$n} = new ParamJob();
            $param{$n}->setName($request->get('var'.$i));
            $param{$n}->setValue($request->get('val'.$i));
            $param{$n}->setJob($job);
            $em->persist($param{$n});
            $n++;
        }
    }
 */   
    $em->flush();

//    print $job->getCategory().'/'.$job->getName();
    print $job->getId();
    exit();
  }

    public function treegridAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $qry = "select ID,CATEGORY,NAME,TITLE,JOB_TYPE,TRIGMODE from joe_job order by NAME"; 
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
                $jn = $line['CATEGORY'].'/'.$line['NAME'];
                if (substr($jn,0,1)=='/')
                        $jn =  substr($jn,1);
                
                $Info[$jn]= $line['ID'].'|'.$line['NAME'].'|'.$line['TITLE'].'|'.$line['TRIGMODE'];
                $key_files[$jn] = $jn;
        }
        
        header("Content-type: text/xml");
        $tree = $this->explodeTree($key_files, "/");
        print '<?xml version="1.0" encoding="UTF-8"?>';
        print "<rows>\n";
        print '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        print $this->Job2XML( $tree, '', $Info );
        print "</rows>\n";
        exit();
    }

    function Job2XML( $leaf, $id = '', $Info ) {
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $i = substr("$id/$k",1);
                            if (isset($Info[$i])) {
                                $cell = '';
                                list($dbid, $name, $title, $trigger ) = explode('|',$Info[$i]);
                                print '<row id="'.$dbid.'">';
                                $cell .= '<cell image="queue.png"> '.$k.'</cell>';
                                $cell .= '<cell>'.$title.'</cell>';
                                $cell .= '<cell>'.$trigger.'</cell>';
                                print $cell;
                            }
                            else {
                                    print '<row id="__'.$k.'" open="1">';
                                    print '<cell image="folder.gif">'.$k.'</cell>';
                            }
                           $this->Job2XML( $leaf[$k], $id.'/'.$k, $Info );
                           print '</row>';
                    }
            }
    }
    
/******************************************************************/
    // http://kevin.vanzonneveld.net/techblog/article/convert_anything_to_tree_structures_in_php/
    function explodeTree($array, $delimiter = '_', $baseval = false)
    {
      if(!is_array($array)) return false;
      $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
      $returnArr = array();
      foreach ($array as $key => $val) {
        // Get parent parts and the current leaf
        $parts  = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);

        // Build parent structure
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
          if (!isset($parentArr[$part])) {
            $parentArr[$part] = array();
          } elseif (!is_array($parentArr[$part])) {
            if ($baseval) {
              $parentArr[$part] = array('__base_val' => $parentArr[$part]);
            } else {
              $parentArr[$part] = array();
            }
          }
          $parentArr = &$parentArr[$part];
        }

        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
          $parentArr[$leafPart] = $val;
        } elseif ($baseval && is_array($parentArr[$leafPart])) {
          $parentArr[$leafPart]['__base_val'] = $val;
        }
      }
      return $returnArr;
    }


}
