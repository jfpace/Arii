<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Entity\ParamJob;
use Arii\JOEBundle\Entity\Script;

class CommandController extends Controller
{

    public function windows_shellAction()
    {
        return $this->render('AriiJOEBundle:Command:edit_bat.html.twig', 
                array(  'id' => -1, 
                        'location' => '/test'));
    }
}
