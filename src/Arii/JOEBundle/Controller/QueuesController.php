<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Form\JobType;
use Symfony\Component\HttpFoundation\Request;

class QueuesController extends Controller
{

    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Queue:index.html.twig');
    }

}
