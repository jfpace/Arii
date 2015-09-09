<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class EnterpriseController extends Controller {
    public function enterprise_managementAction()
    {
        return $this->render("AriiAdminBundle:Enterprise:enterprise_management.html.twig");
    }
    
    public function show_enterpriseAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
        $data->render_table("ARII_ENTERPRISE","id","enterprise,modules");
    }
    
    public function edit_enterpriseAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('form');
        $data->render_table("ARII_ENTERPRISE","id","id,enterprise,modules");
    }
    
    public function delete_enterpriseAction()
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
	public function toolbar_add_siteAction()
    {
        return $this->render("AriiAdminBundle:Toolbar:toolbar_add_site.html.twig");
    }
    public function save_siteAction()
    {
        $request = Request::createFromGlobals();
        $enterprise_name = $request->get('enterprise');
        
        $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->findOneBy(array('enterprise'=>$enterprise_name));
        
        $site = new \Arii\CoreBundle\Entity\Site();
        $site->setName($request->get('name'));
        $site->setCountryCode($request->get('codes'));
        $site->setTimezone($request->get('timezone'));
        $site->setDescription($request->get('description'));
        $site->setEnterprise($enterprise);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($site);
        $em->flush();
        
        return new Response("success");
    }
}

?>
