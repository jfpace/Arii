<?php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Arii\MFTBundle\Entity\Transfer;

class BackgroundController extends Controller
{
    public function postAction()
    {
        print "POST RECEIVED !!!";
        print_r($_POST);
        print_r($_FILES);
        
        set_time_limit ( 300 );
        $f = fopen('php://input', 'r');
        $data = '';
        while(!feof($f)) {
            $r = fread($f,1024);
            $data .= "$r\n";
        }
        fclose($f);
        print "Size: ".strlen($data)."\n";
        print $data;
        file_put_contents('c:\temp\christian.log',$data);
	  exit();
    }
}
