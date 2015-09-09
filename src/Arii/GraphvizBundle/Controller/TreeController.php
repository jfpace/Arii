<?php
namespace Arii\GraphvizBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TreeController extends Controller
{
    public function selectionAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiGraphvizBundle:Sidebar:selection.xml.twig', array(), $response );
    }

}
