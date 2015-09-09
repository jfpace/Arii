<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RemoteSchedulerController extends Controller {
    
    public function listAction()
    {
        return $this->render("AriiJOCBundle:RemoteScheduler:list.html.twig");
    }
    
    public function list_xmlAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $qry = "SELECT frs.id,fs.name as spooler,
                    frs.name,frs.hostname,frs.ip,frs.tcp_port,frs.version 
                FROM FOCUS_REMOTE_SCHEDULERS frs
                LEFT JOIN FOCUS_SPOOLERS fs
                ON frs.spooler_id=fs.id
                ORDER BY fs.name";

        $res = $data->sql->query( $qry );
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rows><head><afterInit><call command="clearAll"/></afterInit></head>';
        $key_files = array();
        $Info = array();
        while ($line = $data->sql->get_next($res)) {
            $id = $line['spooler'].'/'.$line['name'];
            foreach (array('id','ip','hostname','tcp_port','version') as $k) {
                $Info[$id][$k]=$line[$k];
            }
            
            $key_files[$id] = $id;
        }
        $tools = $this->container->get('arii_core.tools');
        $tree = $tools->explodeTree($key_files, "/");
        
        $xml .= $this->Connect2XML( $tree, '', $Info );
        
        $xml .= '</rows>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $xml );
        return $response;        
    }

    function Connect2XML( $leaf, $id = '', $Info ) {
            $return = '';
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $Ids = explode('/',$k);
                            $here = array_pop($Ids);
                            $i  = substr("$id/$k",1);
                            # On ne prend que l'historique
                            if (isset($Info[$i])) {
                                $return .= '<row id="'.$Info[$i]['id'].'" open="1">';
                                $return .= '<cell image="port.png"> '.$here.'</cell>';
                                foreach (array('hostname','ip','tcp_port','version') as $j ) {
                                    $return .= '<cell>'.$Info[$i][$j].'</cell>';
                                }
                            }
                            else {
                                $return .= '<row id="'.$i.'" open="1">';
                                if (strpos($i,'/')>0) 
                                    $return .= '<cell image="server.png"> '.$here.'</cell>';
                                else 
                                    $return .= '<cell image="spooler.png"> '.$here.'</cell>';
                            }
                           $return .= $this->Connect2XML( $leaf[$k], $id.'/'.$k, $Info );
                           $return .= '</row>';
                    }
            }
            return $return;
    }

    public function NoRecord()
    {
        $no = '<?xml version="1.0" encoding="UTF-8"?>';
        $no .= '
    <rows><head><afterInit><call command="clearAll"/></afterInit></head>
<row id="scheduler" open="1"><cell image="spooler.png"><b>No record </b></cell>
</row></rows>';
        return $no;
    }
}

?>
