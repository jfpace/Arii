<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class FilterController extends Controller{

    public function indexAction()
    {
        return $this->render('AriiAdminBundle:Filter:index.html.twig');
    }
    
    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Admin:toolbar.xml.twig",array(),$response);
    }
    
    public function deleteAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $qry_team = "DELETE FROM ARII_TEAM_FILTER WHERE filter_id='$id'";
        $res = $data->sql->query($qry_team);
        $qry_user = "DELETE FROM ARII_USER_FILTER WHERE filter_id='$id'";
        $res = $data->sql->query($qry_user);
        
        $filter = $this->getDoctrine()->getRepository("AriiCoreBundle:Filter")->find($id);
        
        $em = $this->getDoctrine()->getManager();
        $em->remove($filter);
        $em->flush();
        
        return new Response("success");
    }
    
    public function editAction()
    {   
        $db = $this->container->get('arii_core.db');
        $form = $db->Connector('form');
        $form->render_table("ARII_FILTER","id","id,filter,title,spooler,job,job_chain,order_id,repository,status");
    }
        
    public function showAction()
    {   
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
                
        $data->render_table('ARII_FILTER',"id","filter,job,job_chain,order_id,spooler,repository");        
    }
    
    public function saveAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('form');
                
        $data->render_table('ARII_TEAM_RIGHT',"id","team_id,name,job,job_chain,order_id,spooler,repository");        
    }
    
}

?>
