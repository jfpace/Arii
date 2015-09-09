<?php

namespace Arii\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SitesController extends Controller {
    
    public function indexAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        return $this->render('AriiAdminBundle:Sites:index.html.twig',array('id'=>$id));
    }

    public function gridAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterpriseId();
        
        $qry = "SELECT site.id,site.name,site.description,site.country_code FROM ARII_SITE site
                WHERE site.enterprise_id=".$enterprise." order by site.name";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('grid');
        $select->render_sql($qry,"id","name,description,country_code");
    }
    
    public function showAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterpriseId();
        $qry = "SELECT site.id,site.name,site.description,site.country_code FROM ARII_SITE site
                WHERE site.enterprise_id=".$enterprise." order by site.name";
        
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
        $config = $db->Config();
        $config->setHeader($this->get('translator')->trans("Name").','.$this->get('translator')->trans("Description").','.$this->get('translator')->trans("Country"));
        $config->setInitWidths("150,*,120");
        $config->setColTypes("ro,ro,ro");
        $data->set_config($config);
        $data->render_sql($qry,"id","name,description,country_code");
    }
    
    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Sites:menu.xml.twig", array(), $response);
    }
    
}

?>
