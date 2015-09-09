<?php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Arii\MFTBundle\Entity\Transfer;

class CommonController extends Controller
{
    public function langAction()
    {
	  return $this->render('AriiMFTBundle:Default:index.html.twig' );
    }
}
