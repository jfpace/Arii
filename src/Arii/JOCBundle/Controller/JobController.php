<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class JobController extends Controller {

    public function formAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');      
        $qry = $sql->Select(array('ID','PATH','NAME','TITLE','STATE','STATE_TEXT','ALL_STEPS','ALL_TASKS','ORDERED','HAS_DESCRIPTION','TASKS','IN_PERIOD','ENABLED','LAST_WRITE_TIME','LAST_INFO','LAST_WARNING','LAST_ERROR','ERROR','NEXT_START_TIME','WAITING_FOR_PROCESS','HIGHEST_LEVEL','LEVEL','ERROR_CODE','ERROR_TEXT','PROCESS_CLASS_NAME','SCHEDULE_NAME','SPOOLER_NAME'))
                .$sql->From(array('FOCUS_JOBS'))
                .$sql->Where(array('ID' => $id));

        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('form');
        $data->event->attach("beforeRender",array($this,"form_render"));
        $data->render_sql($qry,'ID','ID,FOLDER,NAME,TITLE,STATE,STATE_TEXT,ALL_STEPS,ALL_TASKS,ORDERED,HAS_DESCRIPTION,TASKS,IN_PERIOD,ENABLED,LAST_WRITE_TIME,LAST_INFO,LAST_WARNING,LAST_ERROR,ERROR,NEXT_START_TIME,WAITING_FOR_PROCESS,HIGHEST_LEVEL,LEVEL,ERROR_CODE,ERROR_TEXT,PROCESS_CLASS_NAME,SCHEDULE_NAME,SPOOLER_NAME');
    }
    
    function form_render ($data){
        $folder = dirname($data->get_value('PATH'));
        $data->set_value('FOLDER',$folder);
        $l = strlen($folder);
        foreach (array('SCHEDULE_NAME','PROCESS_CLASS_NAME') as $k) {
            $pc = $data->get_value($k);
            if (substr($pc,0,$l)==$folder) {
                $data->set_value($k,substr($pc,$l+1));
            }
        }
    }

    public function executionAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');      
        $qry = $sql->Select(array('ID','HISTORY_ID','START_TIME','END_TIME','ERROR','ERROR_TEXT','CAUSE','EXIT_CODE','PID'))
                .$sql->From(array('FOCUS_JOB_STATUS'))
                .$sql->Where(array('JOB_ID' => $id));

        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('form');
        $data->render_sql($qry,'JOB_ID','ID,HISTORY_ID,START_TIME,END_TIME,ERROR,ERROR_TEXT,CAUSE,EXIT_CODE,PID');
    }

    public function paramsAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');      
        $qry = $sql->Select(array('ID','NAME','VALUE'))
                .$sql->From(array('FOCUS_JOB_PARAMS'))
                .$sql->Where(array('JOB_ID' => $id));

        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('grid');
        $data->render_sql($qry,'ID','NAME,VALUE');
    }
    
    public function detailAction( )
    {
        $request = Request::createFromGlobals();     
        $id = $request->query->get( 'id' );   
//        $this->refresh($id); // pas de verif, on affiche le timestamp

        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
                
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('j.NAME as JOB_NAME','j.PATH','j.TITLE','j.STATE','j.STATE_TEXT','j.LAST_INFO','j.WAITING_FOR_PROCESS','j.UPDATED',
                                    'p.NAME as PROCESS_CLASS'))
               .$sql->From(array("FOCUS_JOBS j"))
               .$sql->LeftJoin("FOCUS_PROCESS_CLASSES p",array('j.process_class_id','p.id'))                
               .$sql->Where(array("j.ID"=>substr($id,2)));
        
        try {
            $res = $data->sql->query( $qry );
        } catch (Exception $exc) {
            exit();
        }
      
        $Infos = $data->sql->get_next($res);
        return $this->render('AriiJOCBundle:Job:detail.html.twig', $Infos);
    }

    public function params_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiJOCBundle:Jobs:params_toolbar.xml.twig", array(), $response );
    }

    public function logAction( )
    {
        $request = Request::createFromGlobals();     
        $id = $request->query->get( 'job_id' );   

        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
                
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('ID','NAME','VALUE'))
               .$sql->From(array("FOCUS_JOB_PARAMS"))
               .$sql->Where(array("JOB_ID"=>substr($id,2)));
        return $data->render_sql($qry,'id','NAME,VALUE');
    }

}