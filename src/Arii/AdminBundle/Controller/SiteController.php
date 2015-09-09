<?php

namespace Arii\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SiteController extends Controller {
    
    
    public function formAction()
    {
        $dhtmlx = $this->container->get('arii_core.db');
        $form = $dhtmlx->Connector('form');
        $form->render_table("ARII_SITE","id","id,name,timezone,description,country_code,latitude,longitude");
    }
    
    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Site:toolbar.xml.twig", array(), $response);
    }

    public function deleteAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $qry1 = "DELETE FROM ARII_SPOOLER WHERE (not(isnull(supervisor_id)) or not(isnull(primary_id))) and site_id='$id'";
        $res = $data->sql->query($qry1);

        $qry2 = "DELETE FROM ARII_SPOOLER WHERE site_id='$id' ";
        $res = $data->sql->query($qry2);

        $qry3 = "DELETE FROM ARII_SITE WHERE id=$id";        
        $res = $data->sql->query($qry3);
        
        return new Response("success");
        
    }
    
    public function saveAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $session = $this->container->get('arii_core.session');
        $enterprise_id = $session->getEnterpriseId();
        $Enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->find($enterprise_id);
        
        $site = new \Arii\CoreBundle\Entity\Site();
        if ($id != "")
        {
            $site = $this->getDoctrine()->getRepository("AriiCoreBundle:Site")->find($id);
        }
        
        $site->setName($request->get('name'));
        $site->setDescription($request->get('description'));
        $site->setCountryCode($request->get('country_code'));
        $site->setTimezone($request->get('timezone'));
        $site->setEnterprise($Enterprise);
        $site->setLatitude($request->get('latitude'));
        $site->setLongitude($request->get('longitude'));
        $site->setAddress($request->get('address'));
        $site->setCity($request->get('city'));
        $site->setZipcode($request->get('zipcode'));
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($site);
        $em->flush();
        
        return new Response("success");
    }
    
}

?>
