<?php

namespace Arii\DSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class JobController extends Controller
{
    public function formAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');                  
        $qry = $sql->Select(array('ID','SCHEDULER_ID','SCHEDULER_HISTORY_ID','SCHEDULER_ORDER_HISTORY_ID','JOB','JOB_CHAIN','ORDER_ID','SCHEDULE_PLANNED','SCHEDULE_EXECUTED','PERIOD_BEGIN','PERIOD_END','IS_REPEAT','START_START','STATUS','RESULT','CREATED','MODIFIED'))
                .$sql->From(array('DAYS_SCHEDULE'))
                .$sql->Where(array('ID' => $id));
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('form');
        $data->event->attach("beforeRender",array($this,"form_render"));
        // bidouille postgres
        // Attention, bug avec le 'form'
        $session = $this->container->get('arii_core.session');     
        $db = $session->getDatabase();
        if (($db['driver']=='postgres') or ($db['driver']=='postgre') or ($db['driver']=='pdo_pgsql'))
            $data->render_sql($qry,'"ID"','ID,FOLDER,JOB_NAME,SCHEDULER_ID,SCHEDULER_HISTORY_ID,SCHEDULER_ORDER_HISTORY_ID,JOB,JOB_CHAIN,ORDER_ID,SCHEDULE_PLANNED,SCHEDULE_EXECUTED,PERIOD_BEGIN,PERIOD_END,IS_REPEAT,START_START,STATUS,RESULT,CREATED,MODIFIED');
        // fin de bidouille
        
        $data->render_sql($qry,'ID','ID,FOLDER,JOB_NAME,SCHEDULER_ID,SCHEDULER_HISTORY_ID,SCHEDULER_ORDER_HISTORY_ID,JOB,JOB_CHAIN,ORDER_ID,SCHEDULE_PLANNED,SCHEDULE_EXECUTED,PERIOD_BEGIN,PERIOD_END,IS_REPEAT,START_START,STATUS,RESULT,CREATED,MODIFIED');
    }

    public function form_render($row)
    {
        $row->set_value("FOLDER",dirname($row->get_value("JOB")));
        $row->set_value("JOB_NAME",basename($row->get_value("JOB")));
    }
}
