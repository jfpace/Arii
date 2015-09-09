<?php

namespace Arii\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RepositoryController extends Controller {
    
    public function editAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        return $this->render('AriiAdminBundle:Repositories:index.html.twig',array('id'=>$id));
    }
    
    public function formAction()
    {
        $dhtmlx = $this->container->get('arii_core.db');
        $form = $dhtmlx->Connector('form');
        
        $form->render_table("ARII_REPOSITORY","id","id,name,timezone,description,db_id");
    }
    
    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Repository:toolbar.xml.twig", array(), $response);
    }

    public function saveAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $session = $this->container->get('arii_core.session');
        $enterprise_name = $session->getEnterprise();
        $Enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->findOneBy(array('enterprise'=>$enterprise_name));
        
        $dababase_id = $request->get('db_id');
        $db = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($dababase_id);
        
        $repository = new \Arii\CoreBundle\Entity\Repository();
        if ($id != "")
        {
            $repository = $this->getDoctrine()->getRepository("AriiCoreBundle:Repository")->find($id);
        }
        
        $repository->setName($request->get('name'));
        $repository->setDescription($request->get('description'));
        $repository->setTimezone($request->get('timezone'));
        $repository->setEnterprise($Enterprise);
        $repository->setDb($db);
        
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($repository);
        $em->flush();
        
        return new Response("success");
    }
    
}

?>
