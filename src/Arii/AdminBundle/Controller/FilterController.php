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
    
    public function team_filter_listAction()
    {
        $request = Request::createFromGlobals();
        $team_id = $request->get('id');
        $db = $this->container->get("arii_core.db");
        $grid = $db->Connector('grid');
        $config = $db->Config();
        $config->setHeader($this->get("translator")->trans('Name').','.$this->get("translator")->trans('Linked Filter').','.$this->get("translator")->trans('R').','.$this->get("translator")->trans('W').','.$this->get("translator")->trans('X'));
        $config->setInitWidths("*,100,30,30,30");
        $config->setColTypes("ed,ro,ch,ch,ch");
        $grid->set_config($config);
        $qry = "SELECT atf.*,af.filter as filter_name FROM ARII_TEAM_FILTER atf
                LEFT JOIN ARII_FILTER af
                ON atf.filter_id = af.id
                WHERE team_id='$team_id'";
        
        $grid->render_sql($qry,"filter_id","name,filter_name,R,W,X");
    }
    
    public function showAction()
    {   
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
        $config = $db->Config();
        $config->setHeader($this->get('translator')->trans("Name").','.$this->get('translator')->trans("Title").','.$this->get('translator')->trans("Spooler").','.$this->get('translator')->trans("Job").','.$this->get('translator')->trans("Job Chain").','.$this->get('translator')->trans("Order").','.$this->get('translator')->trans("Repository").','.$this->get('translator')->trans("Status"));
        $config->setInitWidths("120,120,120,120,120,120,120,*");
        $config->attachHeader("#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#text_filter,#select_filter");
        $config->setColTypes("ro,ro,ro,ro,ro,ro,ro,ro,");
        $data->set_config($config);
        $data->render_table('ARII_FILTER',"id","filter,title,spooler,job,job_chain,order_id,repository,status");
        
    }
    
    public function saveAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        if ($id != '')
        {
            $filter = $this->getDoctrine()->getRepository("AriiCoreBundle:Filter")->find($id);
        } else
        {
            $filter = new \Arii\CoreBundle\Entity\Filter();
        }
        
        $filter->setFilter($request->get('filter'));
        $filter->setTitle($request->get('title'));
        $filter->setSpooler($request->get('spooler'));
        $filter->setJob($request->get('job'));
        $filter->setJobChain($request->get('job_chain'));
        $filter->setOrderId($request->get('order_id'));
        $filter->setRepository($request->get('repository'));
        $filter->setStatus($request->get('status'));
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($filter);
        $em->flush();
        
        return new Response("success");
    }
    
}

?>
