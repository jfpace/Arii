<?php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction($name)
    {       
        return $this->render('AriiMFTBundle:Default:index.html.twig' );
    }

    public function ribbonAction()
    { 
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');    
        return $this->render('AriiMFTBundle:Default:ribbon.json.twig',array(), $response );
    }


    public function toolbarAction()
    {
        
        return $this->render('AriiMFTBundle:Default:toolbar.xml.twig' );
    }

    public function toolbar_activitiesAction()
    {
        
        return $this->render('AriiMFTBundle:Default:toolbar_activities.xml.twig' );
    }
}

