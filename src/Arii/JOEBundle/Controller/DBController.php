<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Arii\JOEBundle\Entity\Job;

class DBController extends Controller
{
/******************************************************************/
    public function jobsAction()
    {
        require_once '../vendor/dhtmlx/dhtmlxConnector/codebase/grid_connector.php';

        $database_host = $this->container->getParameter('database_host'); 
        $database_port = $this->container->getParameter('database_port'); 
        $database_name = $this->container->getParameter('database_name'); 
        $database_user = $this->container->getParameter('database_user'); 
        $database_password = $this->container->getParameter('database_password'); 

        $conn=mysql_connect( $database_host, $database_user,  $database_password );
        mysql_select_db( $database_name );

        $data = new \GridConnector($conn);

        $qry = "select name,title,spooler_id,process_class,visible from joe_job order by name"; 
        $data->event->attach("beforeRender",array( $this,  "render_jobs" ) );
        $data->render_sql($qry,'ID','name,title,spooler_id,process_class,visible');

    }
    
    function render_jobs($row){
	$state = $row->get_value("visible");
	if ($state == 'not_initialized' ) {
		$row->set_row_attribute("class","backgroundfailure");
	}
	elseif ($state == 'stopped' ) {
		$row->set_row_attribute("class","backgroundstopped");
	}
    }

/******************************************************************/
    public function connections_gridAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('grid');

        $qry = "select id,name,title,host,protocol,auth_method,user,proxy_host from joe_connect order by name"; 
        $data->render_sql($qry,'id','name,title,host,protocol,auth_method,user,proxy_host');
    }

    public function connections_formAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('form');
        $data->render_table('joe_connect');
    }
}
