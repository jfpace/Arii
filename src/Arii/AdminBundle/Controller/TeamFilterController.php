<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Arii\CoreBundle\Entity\TeamFilter;

class TeamFilterController extends Controller {
    
    public function menuAction()
    {   
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:TeamFilter:menu.xml.twig", array(), $response);
    }

    public function toolbarAction()
    {   
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:TeamFilter:toolbar.xml.twig", array(), $response);
    }
    
    public function processorAction()
    {
        $request = Request::createFromGlobals();
        $team_id = $request->get("id");
        $response = new Response();
        $response->headers->set("Content-type","text/xml");
        
        $content = '<?xml version="1.0" encoding="iso-8859-1"?>'; 
        $content .= "<data>";
        
        $ids = explode(",",$request->get('ids'));
        file_put_contents("c:/temp.txt", $request);
        for ($i=0; $i < sizeof($ids); $i++) { 
            $rowId = $ids[$i]; //id or row which was updated 
            //$newId = $rowId;  will be used for insert operation	
            $mode = $request->get($rowId."_!nativeeditor_status"); //get request mode
            
            switch($mode){
                    case "deleted":
                        $filter = $this->getDoctrine()->getRepository("AriiCoreBundle:Filter")->find($rowId);
                        $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($team_id);

                        $team_filter = $this->getDoctrine()->getRepository("AriiCoreBundle:TeamFilter")->findOneBy(array('team'=>$team,'filter'=>$filter));    
                        $em = $this->getDoctrine()->getManager();
                        $em->remove($team_filter);
                        $em->flush();

                        $action = "delete";
                    break;    
                    default:
                        $filter = $this->getDoctrine()->getRepository("AriiCoreBundle:Filter")->find($rowId);
                        $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($team_id);
                        
                        $team_filter = $this->getDoctrine()->getRepository("AriiCoreBundle:TeamFilter")->findOneBy(array('team'=>$team,'filter'=>$filter));
                        $team_filter->setName($request->get($rowId."_c0"));
                        $team_filter->setR($request->get($rowId."_c2"));
                        $team_filter->setW($request->get($rowId."_c3"));
                        $team_filter->setX($request->get($rowId."_c4"));
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($team_filter);
                        $em->flush();
                        //file_put_contents("c:/temp.txt", $team_filter->getR());
                        $action = "update";
            }
            $content .= "<action type='".$action."' sid='".$rowId."' tid=''/>";
        }
        $content .= "</data>";
        $response->setContent($content);
        return $response;
    }
    
}

?>
