<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RightsController extends Controller{

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
         return $this->render("AriiAdminBundle:Rights:toolbar.xml.twig", array(), $response);
    }
    
    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
         return $this->render("AriiAdminBundle:Rights:menu.xml.twig", array(), $response);
    }

    public function rightsAction()
    {
        $request = Request::createFromGlobals();
        $team_id = $request->get('team_id');
        $db = $this->container->get("arii_core.db");
        $grid = $db->Connector('grid');
        
        $sql = $this->container->get("arii_core.sql");
        $qry = $sql->Select(array("id,team_id,name,description,path,job,job_chain,order_id,spooler,repository,R,W,X"))
                .$sql->From(array('ARII_TEAM_RIGHT'))
                .$sql->Where(array('team_id'=>$team_id));

        $grid->render_sql($qry,'id',"team_id,name,description,path,job,job_chain,order_id,spooler,repository,R,W,X");
    }

    public function formAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('form');
                
        $data->render_table('ARII_TEAM_RIGHT','id','team_id,name,description,path,job,job_chain,order_id,spooler,repository,R,W,X');        
    }

    public function gridAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
   
        $data->render_table('ARII_TEAM_RIGHT','id','team_id,name,description,path,job,job_chain,order_id,spooler,repository,R,W,X');        
    }

}

?>
