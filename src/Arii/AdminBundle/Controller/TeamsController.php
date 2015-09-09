<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Arii\CoreBundle\Entity\TeamFilter;

class TeamsController extends Controller {

   public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Teams:menu.xml.twig", array(), $response);
    }

    public function selectAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise_name = $session->getEnterprise();
        $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->findOneBy(array('enterprise'=>$enterprise_name));
        $enterprise_id = $enterprise->getId();
        
        $db = $this->container->get("arii_core.db");
        $select = $db->Connector('select');
        $qry = "SELECT id,name FROM ARII_TEAM WHERE enterprise_id=$enterprise_id";
        $select->render_sql($qry,'id','id,name');
    }

}

?>
