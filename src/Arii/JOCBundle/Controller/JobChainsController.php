<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class JobChainsController extends Controller{
    
    public function listAction()
    {
        return $this->render('AriiJOCBundle:JobChains:list.html.twig');
    }
    
    public function list_xmlAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $qry = "SELECT asp.scheduler as spooler_id,fjc.path,fjc.name as job_chain_name,fjc.state,fjc.last_write_time,fjc.updated,fjcn.state,fo.name as order_name
                FROM FOCUS_JOB_CHAINS fjc
                LEFT JOIN FOCUS_SPOOLERS fs
                ON fjc.spooler_id=fs.id
                LEFT JOIN ARII_SPOOLER asp
                ON fs.spooler_id=asp.id
                LEFT JOIN FOCUS_JOB_CHAIN_NODES fjcn
                ON fjcn.job_chain_id=fjc.id
                LEFT JOIN FOCUS_ORDERS fo
                ON fo.job_chain_node_id=fjcn.id";
        $res = $data->sql->query($qry);
        while ($line = $data->sql->get_next($res))
        {
            $jcn = $line['spooler_id'].'/'.$line[''];
        }
    }
    
}

?>
