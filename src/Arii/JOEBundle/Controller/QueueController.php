<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Form\JobType;
use Symfony\Component\HttpFoundation\Request;

class QueueController extends Controller
{

    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Queue:index.html.twig');
    }

    public function editAction($mode='new')
    {
        // en cas d'edit on retrouve l'id par le request (demandé par dhtmlx)
        $id = -1;
        if ($mode == 'edit') {
            $request = Request::createFromGlobals();
             if ($request->get('id')) {
                 $id = $request->get('id' );
             }
        }
        return $this->render('AriiJOEBundle:Queue:edit.html.twig', array( 'mode'=> $mode, 'id' => $id ));
    }

    public function newAction($jobtype = '')
    {
      // On crée un objet Job
      $job = new Job;
      $form =  $this->createForm(new JobType, $job);

    // On récupère la requête
        $request = $this->get('request');

        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {
          // On fait le lien Requête <-> Formulaire
          $form->bind($request);

          // On vérifie que les valeurs rentrées sont correctes
          // (Nous verrons la validation des objets en détail plus bas dans ce chapitre)
          if ($form->isValid()) {
            // On l'enregistre notre objet $article dans la base de données
            $em = $this->getDoctrine()->getManager();
            $em->persist($job);
            $em->flush();

            // On redirige vers la page de visualisation de l'article nouvellement créé
            return $this->redirect($this->generateUrl('arii_JOE_job_edit', array('id' => $job->getId())));
          }
        }

      // On passe la méthode createView() du formulaire à la vue afin qu'elle puisse afficher le formulaire toute seule
      return $this->render('AriiJOEBundle:Job:new.html.twig', array(
        'form' => $form->createView(),
      ));
     }
 
    public function toolbarAction()
    {
        return $this->render('AriiJOEBundle:Queue:toolbar.html.twig');
    }

/******************************************************************/
    public function gridAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('grid');

        //write it before '$grid->render_table'
        $data->event->attach("beforeProcessing",  array( $this, "filter_set") );
        $data->render_table('joe_queue');
    }
    function filter_set($action){
        $action->remove_field("queue"); //the named field won't be included in CRUD operations
    }

    public function formAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('form');
        $data->render_table('joe_queue');
    }

    public function selectAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('select');
        $data->render_sql("select ID,QUEUE from joe_queue order by QUEUE","","ID,QUEUE");
    }

    public function comboAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('combo');
        $data->render_sql("select QUEUE from joe_queue order by QUEUE","","QUEUE");
    }

    public function treeAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('treegridgroup');
        $data->render_table('joe_queue');
    }

    public function treegridTESTAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('treegridgroup');
        $data->render_table('joe_queue',"queue", "title", "node", "queue");
    }

    public function treegridAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $qry = "select ID,QUEUE,TITLE,NODE,MAX_PROCESSES from joe_queue order by QUEUE"; 
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
                $jn = $line['QUEUE'];
                $Info[$jn]= $line['ID'].'|'.$line['QUEUE'].'|'.$line['TITLE'].'|'.$line['NODE'].'|'.$line['MAX_PROCESSES'];
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
        print $this->Queue2XML( $tree, '', $Info );
        print "</rows>\n";
        exit();
    }

    function Queue2XML( $leaf, $id = '', $Info ) {
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $i = "$id/$k";
                            if (isset($Info[$i])) {
                                $cell = '';
                                list($dbid, $queue, $title, $node, $max_processes ) = explode('|',$Info[$i]);
                                print '<row id="'.$dbid.'">';
                                $cell .= '<cell image="queue.png"> '.basename($queue).'</cell>';
                                $cell .= '<cell>'.$title.'</cell>';
                                $cell .= '<cell>'.$node.'</cell>';
                                $cell .= '<cell>'.$max_processes.'</cell>';
                                print $cell;
                            }
                            else {
                                    print '<row id="__'.$k.'" open="1">';
                                    print '<cell image="folder.gif">'.basename($i).'</cell>';
                            }
                           $this->Queue2XML( $leaf[$k], $id.'/'.$k, $Info );
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
