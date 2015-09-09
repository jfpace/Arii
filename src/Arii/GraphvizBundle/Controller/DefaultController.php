<?php

namespace Arii\GraphvizBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AriiGraphvizBundle:Default:index.html.twig');
    }
    
    public function selectionAction()
    {
        return $this->render('AriiGraphvizBundle:Default:selection.html.twig');
    }
    
    public function ribbonAction()
    {
        $folder = $this->container->get('arii_core.folder');
        $Dir = $folder->Remotes();
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiGraphvizBundle:Default:ribbon.json.twig',array('Schedulers' => $Dir), $response);
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiGraphvizBundle:Default:toolbar.xml.twig',array(), $response);
    }

    public function legendAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiGraphvizBundle:Default:legend.xml.twig',array(), $response);
    }

}
