<?php
// src/Arii/MFTBundle/Controller/TransfersController.php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DBGraphController extends Controller
{
    public function pie_sendAction()
    {
        require_once '../vendor/dhtmlx/dhtmlxConnector/codebase/chart_connector.php';

        $database_host = $this->container->getParameter('database_host'); 
        $database_host = $this->container->getParameter('database_port'); 
        $database_name = $this->container->getParameter('database_name'); 
        $database_user = $this->container->getParameter('database_user'); 
        $database_password = $this->container->getParameter('database_password'); 

        $conn=mysql_connect( $database_host, $database_user,  $database_password );
        mysql_select_db( $database_name );

        $data = new \ChartConnector($conn);
        $sql = "select f.STATUS, count(*) as FILES 
        from SOSFTP_FILES_HISTORY f 
        group by f.status 
        order by f.status";
        // $data->event->attach("beforeRender", array( $this, "color_rows") );
        $data->render_sql($sql,"STATUS","STATUS,FILES");
    }
/*    function color_rows($row){
            if ($row->get_value("STATUS")=='success') {
                    $row->set_value("COLOR","backgroundsuccess");
            }
            else {
                    $row->set_value("COLOR","backgroundfailure");
            }
    }
*/
}


