<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Arii\CoreBundle\Entity\TeamFilter;

class TeamController extends Controller {
    
    public function formAction()
    {
        $db = $this->container->get('arii_core.db');
        $form = $db->Connector('form');
        $form->render_table("ARII_TEAM","id","id,name,description");
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Team:toolbar.xml.twig", array(), $response);
    }

    public function deleteAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $db = $this->container->get("arii_core.db");
        $data = $db->Connector('data');
        $qry = "DELETE FROM ARII_TEAM_FILTER WHERE team_id=$id";
        $res = $data->sql->query($qry);
        
        $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($id);
        $em = $this->getDoctrine()->getManager();
        $em->remove($team);
        $em->flush();
        
        $qry = "UPDATE ARII_USER SET team_id='' WHERE team_id=$id";
        $res = $data->sql->query($qry);
        
        return new Response("success");
    }
    
    public function saveAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $session = $this->container->get('arii_core.session');
        $enterprise_name = $session->getEnterprise();
        $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->findOneBy(array('enterprise'=>$enterprise_name));
        
        $team = new \Arii\CoreBundle\Entity\Team();
        if ($id!="")
        {
            $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($id);
        }
        $team->setName($request->get('name'));
        $team->setDescription($request->get('description'));
        $team->setEnterprise($enterprise);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($team);
        $em->flush();
        
        return new Response("success");
    }
        

}

?>
