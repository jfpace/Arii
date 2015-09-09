<?php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FormController extends Controller
{
    public function formAction()
    {
        
        return $this->render('AriiJOEBundle:Forms:shell_windows.html.twig');
    }

}
