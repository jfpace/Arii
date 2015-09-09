<?php

namespace Arii\ConfigBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AriiConfigBundle:Default:index.html.twig');
    }
}
