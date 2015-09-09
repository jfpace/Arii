<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ConnectionsController extends Controller {
    
    public function indexAction()
    {
        return $this->render("AriiJOCBundle:Connections:index.html.twig");
    }
    
    public function gridAction()
    {
        $db = $this->container->get("arii_core.db");
        $data = $db->Connector('grid');
        
        $sql = $this->container->get("arii_core.sql");
        $date = $this->container->get("arii_core.date");
        
        $qry = $sql->Select(array('fs.NAME as SPOOLER','fp.ID','fp.OPERATION_TYPE','fp.RESPONSES','fp.RECEIVED_BYTES','fp.SEND_BYTES','fp.STATE','fp.HOST_IP','fp.PORT'))
            .$sql->From(array('FOCUS_CONNECTIONS fp'))
            .$sql->LeftJoin('FOCUS_SPOOLERS fs', array('fp.SPOOLER_ID','fs.ID'))
            .$sql->OrderBy(array('fs.NAME','HOST_IP','PORT desc'));
        
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
            $list .= '<cell>'.$line['HOST_IP'].'</cell>';
            $list .= '<cell>'.$line['PORT'].'</cell>';
            $list .= '<cell>'.$line['OPERATION_TYPE'].'</cell>';
            $list .= '<cell>'.$line['STATE'].'</cell>';
            $list .= '<cell>'.$line['RESPONSES'].'</cell>';
            $list .= '<cell>'.$line['RECEIVED_BYTES'].'</cell>';
            $list .= '<cell>'.$line['SEND_BYTES'].'</cell>';
            $list .= '</row>';
        }
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

}

?>
