<?php

namespace Arii\DSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function ribbonAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        
        return $this->render('AriiDSBundle:Default:ribbon.json.twig',array(), $response );
    }
    
    public function readmeAction()
    {
        return $this->render('AriiDSBundle:Default:readme.html.twig');
    }

}
