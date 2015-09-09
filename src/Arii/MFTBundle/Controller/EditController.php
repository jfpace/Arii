<?php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EditController extends Controller
{
    public function indexAction()
    {
        
        return $this->render('AriiMFTBundle:Edit:index.html.twig' );
    }

    public function editAction()
    {
        
        return $this->render('AriiMFTBundle:Edit:edit.html.twig' );
    }

    public function toolbarAction()
    {
        
        return $this->render('AriiMFTBundle:Edit:toolbar.xml.twig' );
    }

}

