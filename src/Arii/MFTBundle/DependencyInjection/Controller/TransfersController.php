<?php
// src/Arii/MFTBundle/Controller/TransfersController.php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class TransfersController extends Controller
{
    public function indexAction( $name = 'eric' )
    {
       return $this->render('AriiMFTBundle:Default:index.html.twig' , array('name' =>$name));
    }

    public function activitiesAction()
    {
       return $this->render('AriiMFTBundle:Default:activities.html.twig' );
    }

    public function graphAction( $name = 'eric' )
    {
       return $this->render('AriiMFTBundle:Default:graph.html.twig' , array('name' =>$name));
    }

}
