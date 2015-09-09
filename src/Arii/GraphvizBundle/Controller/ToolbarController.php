<?php

namespace Arii\GraphvizBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ToolbarController extends Controller
{
    public function graphAction()
    {
        return $this->render('AriiGraphvizBundle:Toolbar:graph.html.twig');
    }

}
