<?php

namespace Arii\ATSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $Colors = $this->container->getParameter('color_status');
        foreach ($Colors as $k=>$v) {
            if (($p=strpos($v,"/"))>0) $Colors[$k] = substr($Colors[$k],0,$p);                    
        }
        return $this->render('AriiATSBundle:Default:index.html.twig', array("color" => $Colors));
    }

    public function readmeAction()
    {
        return $this->render('AriiATSBundle:Default:readme.html.twig');
    }

    public function ribbonAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        
        return $this->render('AriiATSBundle:Default:ribbon.json.twig',array(), $response );
    }

    public function docAction() {
        $request = Request::createFromGlobals();
        $lang = $this->getRequest()->getLocale();
        $template = $this->container->getParameter('ats_doc');

        $doc = $this->container->get('arii_doc.doc');
        $url = $doc->Url($template);
        
        header("Location: $url");
        die();
    }
}
