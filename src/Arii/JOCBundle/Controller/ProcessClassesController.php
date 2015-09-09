<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ProcessClassesController extends Controller {
    protected $image;
    public function __construct( )
    {
        $request = Request::createFromGlobals();
        $this->images = $request->getUriForPath('/../arii/images/wa');
    }

    public function indexAction()
    {
        return $this->render("AriiJOCBundle:ProcessClasses:index.html.twig");
    }
    
    public function gridAction()
    {
        $db = $this->container->get("arii_core.db");
        $data = $db->Connector('grid');
        
        $sql = $this->container->get("arii_core.sql");
        $date = $this->container->get("arii_core.date");
        $qry = $sql->Select(array('fs.NAME as SPOOLER','fp.ID','fp.NAME','fp.PATH','fp.MAX_PROCESSES','fp.REMOTE_SCHEDULER','fp.PROCESSES','fp.ERROR'))
            .$sql->From(array('FOCUS_PROCESS_CLASSES fp'))
            .$sql->LeftJoin('FOCUS_SPOOLERS fs', array('fp.SPOOLER_ID','fs.ID'))
            .$sql->OrderBy(array('fs.NAME','fp.PATH'));
        
        $res = $data->sql->query($qry);
        $Orders = $Steps = array();
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        while ($line = $data->sql->get_next($res))
        {
            $list .= '<row id="'.$line['ID'].'">';
            $list .= '<cell>'.$line['SPOOLER'].'</cell>';
            $list .= '<cell>'.str_replace('\\','/',dirname($line['PATH'])).'</cell>';
            $list .= '<cell>'.$line['NAME'].'</cell>';
            $list .= '<cell>'.$line['PROCESSES'].'</cell>';
            $list .= '<cell>'.$line['MAX_PROCESSES'].'</cell>';
            $list .= '<cell>'.$line['REMOTE_SCHEDULER'].'</cell>';
            $list .= '<cell>'.$line['ERROR'].'</cell>';
            $list .= '</row>';
        }
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }
}

?>
