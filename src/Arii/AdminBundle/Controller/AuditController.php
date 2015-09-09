<?php

namespace Arii\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class AuditController extends Controller {

    public function indexAction()
    {
        return $this->render("AriiAdminBundle:Audit:index.html.twig");
    }
        
    public function showAction()
    {
        $db = $this->container->get('arii_core.db');
        $grid = $db->Connector('grid');
        $qry = "select a.id,a.logtime,a.user_id,a.ip,a.module,a.action,a.status,u.username from ARII_AUDIT a left join ARII_USER u on a.user_id=u.id";
        $grid->event->attach("beforeRender",array($this,"changeColor"));
        $grid->render_sql($qry,"id","logtime,username,ip,module,action,status");
    }
    
    function changeColor($row)
    {
        if($row->get_value('status')=="error")
        {   
            $row->set_row_style("background-color: #fbb4ae;");
        } 
        if($row->get_value('status')=="success") {
            $row->set_row_style("background-color: #ccebc5;");    
        }
    }
}

?>
