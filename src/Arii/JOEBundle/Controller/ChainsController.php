<?php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ChainsController extends Controller
{
    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Chains:index.html.twig' );
    }

}
