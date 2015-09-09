<?php

namespace Arii\ConfigBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ToolbarController extends Controller
{
    public function globalAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        
        return $this->render('AriiConfigBundle:Toolbar:global.xml.twig',array(), $response );
    }

}
?>