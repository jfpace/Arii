<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Form\JobType;
use Symfony\Component\HttpFoundation\Request;

class MailController extends Controller
{

    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Connection:index.html.twig');
    }

    public function editAction($mode='new')
    {
        // en cas d'edit on retrouve l'id par le request (demandé par dhtmlx)
        $id = -1;
        if ($mode == 'edit') {
            $request = Request::createFromGlobals();
             if ($request->get('id')) {
                 $id = $request->get('id' );
             }
        }
        return $this->render('AriiJOEBundle:Mail:edit.html.twig', array( 'mode'=> $mode, 'id' => $id ));
    }

}
