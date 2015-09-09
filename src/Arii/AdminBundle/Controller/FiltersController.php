<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
class FiltersController extends Controller{

    public function indexAction()
    {
        return $this->render('AriiAdminBundle:Filters:index.html.twig');
    }

    public function gridAction()
    {   
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterpriseId();
/*      
        $config = $db->Config();
        $config->setHeader($this->get('translator')->trans("Name").','.$this->get('translator')->trans("Title").','.$this->get('translator')->trans("Spooler").','.$this->get('translator')->trans("Job").','.$this->get('translator')->trans("Job Chain").','.$this->get('translator')->trans("Order ID").','.$this->get('translator')->trans("Status"));
        $config->setInitWidths("80,80,60,60,60,60,*");
        $config->attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#select_filter");
        $config->setColTypes("ro,ro,ro,ro,ro,ro,ro");
        $data->set_config($config);
*/
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');

        $qry = "select id,filter,title,spooler,job,job_chain,order_id,status from ARII_FILTER where enterprise_id=".$enterprise." order by filter";
        $data->render_sql($qry,"id","filter,title,spooler,job,job_chain,order_id,status");
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
         return $this->render("AriiAdminBundle:Filters:menu.xml.twig", array(), $response);
    }

}

?>
