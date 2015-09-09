<?php

namespace Arii\ConfigBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class EnterprisesController extends Controller {
    
    public function indexAction()
    {
        return $this->render("AriiConfigBundle:Enterprises:index.html.twig", array('id'=>''));
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiConfigBundle:EnterpriseS:toolbar.xml.twig", array(), $response);
    }
    
    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiConfigBundle:Enterprises:menu.xml.twig", array(), $response);
    }
    
    public function gridAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
        $data->render_table("ARII_ENTERPRISE","id","enterprise,modules");
    }
    
    public function editAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('form');
        $data->render_table("ARII_ENTERPRISE","id","id,enterprise,modules");
    }
    
    public function deleteAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $db = $this->container->get("arii_core.db");
        $data = $db->Connector('data');
        
        $qry = "DELETE FROM ARII_USER user WHERE user.enterprise_id='$id'";        
        $res = $data->sql->query($qry);
        
        $qry2 = "DELETE FROM ARII_TEAM team HERE team.enterprise_id='$id'";
        $res2 = $data->sql->query($qry2);
        
        $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->find($id);
        
        $em = $this->getDoctrine()->getManager();
        $em->remove($enterprise);
        $em->flush();
        
        return new Response("success");
    }
    
    public function save_enterpriseAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $enterprise = new \Arii\CoreBundle\Entity\Enterprise();
        if($id!="")
        {
            $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->find($id);
        }
        $enterprise->setEnterprise($request->get('enterprise'));
        $enterprise->setModules($request->get('modules'));
        
        $em = $this->getDoctrine()->getManager();
        
        $em->persist($enterprise);
        $em->flush();
        
        return new Response("success");
    }
}
?>
