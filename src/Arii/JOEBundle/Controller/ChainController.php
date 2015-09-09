<?php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ChainController extends Controller
{
    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Chain:index.html.twig' );
    }

    public function editAction()
    {
        return $this->render('AriiJOEBundle:Chain:edit.html.twig', array( 'id'=> 0, 'path'=> 'test', 'type' => 'type'  ) );
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOEBundle:Chain:toolbar.xml.twig', array(), $response );
    }

}
