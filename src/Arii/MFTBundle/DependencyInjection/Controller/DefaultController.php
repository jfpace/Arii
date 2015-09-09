<?php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        
        return $this->render('AriiMFTBundle:Default:index.html.twig' );
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

