<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RuntimesController extends Controller {
    protected $image;
    public function __construct( )
    {
        $request = Request::createFromGlobals();
        $this->images = $request->getUriForPath('/../arii/images/wa');
    }

    public function listAction()
    {
        return $this->render("AriiJOCBundle:Runtimes:list.html.twig");
    }
    
    public function ordersAction()
    {
        $db = $this->container->get("arii_core.db");
        $data = $db->Connector('grid');
        
        $sql = $this->container->get("arii_core.sql");
        $date = $this->container->get("arii_core.date");
        
        $qry = $sql->Select(array(
                            'fs.NAME as SPOOLER','fo.PATH','fjcn.STATE',
                            'fosr.ID as STEP_ID','fosr.RUN_TIME as STEP_RUNTIME','fosr.RUNS as STEP_RUNS','fosr.UPDATED as STEP_UPDATED','fosr.HISTORY_ID as STEP_HISTORY','fosr.TASK_ID as STEP_TASK','fosr.DIFF as STEP_DIFF',
                            'fore.ID as ORDER_ID','fore.RUN_TIME as ORDER_RUNTIME','fore.RUNS as ORDER_RUNS','fore.UPDATED as ORDER_UPDATED','fore.HISTORY_ID as ORDER_HISTORY','fore.DIFF as ORDER_DIFF'));
        $qry .= $sql->From(array('FOCUS_ORDER_STEP_RUNTIMES fosr'));
        $qry .= $sql->LeftJoin('FOCUS_ORDER_RUNTIMES fore',array('fosr.ORDER_ID','fore.ORDER_ID'));
        $qry .= $sql->LeftJoin('FOCUS_ORDERS fo',array('fosr.ORDER_ID','fo.ID'));
        $qry .= $sql->LeftJoin('FOCUS_JOB_CHAIN_NODES fjcn',array('fosr.JOB_CHAIN_NODE_ID','fjcn.ID'));
        $qry .= $sql->LeftJoin('FOCUS_SPOOLERS fs',array('fo.SPOOLER_ID','fs.ID'));
        $qry .= $sql->OrderBy(array('fs.NAME','fo.PATH','fjcn.STATE'));
        $res = $data->sql->query($qry);
        $Orders = $Steps = array();
        while ($line = $data->sql->get_next($res))
        {
            $o = $line['SPOOLER'].$line['PATH'];
            if (!isset($Orders[$o])) {
                $Orders[$o]['id'] = $line['ORDER_ID'];
                $Orders[$o]['runtime'] = $date->FormatTime($line['ORDER_RUNTIME']);
                $Orders[$o]['runs'] = $line['ORDER_RUNS'];
                $Orders[$o]['update'] = $date->ShortDate(date( 'Y-m-d H:i:s', $line['ORDER_UPDATED'] ));
                $Orders[$o]['history'] = $line['ORDER_HISTORY'];
                $Orders[$o]['diff'] = $line['ORDER_DIFF'];
                $key_files[$o] = $o;
            }
            
            $s = $o.'/'.$line['STATE'];
            if (!isset($Steps[$s])) {
                $Steps[$s]['id'] = $line['STEP_ID'];
                $Steps[$s]['runtime'] = $date->FormatTime($line['STEP_RUNTIME']);
                $Steps[$s]['runs'] = $line['STEP_RUNS'];
                $Steps[$s]['update'] = $date->ShortDate(date( 'Y-m-d H:i:s', $line['STEP_UPDATED'] ));
                $Steps[$s]['history'] = $line['STEP_HISTORY'];
                $Steps[$s]['task'] = $line['STEP_TASK'];
                $Steps[$s]['diff'] = $line['STEP_DIFF'];
                $key_files[$s] = $s;
            }
        }
    //    print_r($key_files); exit();
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        $tools = $this->container->get("arii_core.tools");
        $tree = $tools->explodeTree($key_files, "/");
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        $list .= $this->Orders2XML( $tree, '', $Orders, $Steps);
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }
    
    function Orders2XML( $leaf, $id = '', $Orders, $Steps ) {
            $return = '';
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $Ids = explode('/',$k);
                            $here = array_pop($Ids);
                            $i  = substr("$id/$k",1);
                            # On ne prend que l'historique
                            if (isset($Orders[$i])) {
                                $Info = $Orders[$i];
                                $return .= '<row id="O:'.$Info['id'].'" open="1">';
                                $return .= '<cell image="order.png">'.$here.'</cell>';
                                $return .= '<cell>'.$Info['runtime'].'</cell>';
                                $return .= '<cell>'.$Info['runs'].'</cell>';
                                if ($Info['runtime']>0) 
                                    $return .= '<cell>'.round($Info['diff']*100/$Info['runtime']).'%</cell>';
                                else 
                                    $return .= '<cell/>';
                                $return .= '<cell>'.$Info['history'].'</cell>';
                                $return .= '<cell/>';
                                $return .= '<cell>'.$Info['update'].'</cell>';
                            }
                            elseif (isset($Steps[$i])) {
                                $Info = $Steps[$i];
                                $return .= '<row id="S:'.$Info['id'].'" open="1">';
                                $return .= '<cell image="step.png">'.$here.'</cell>';
                                $return .= '<cell>'.$Info['runtime'].'</cell>';
                                $return .= '<cell>'.$Info['runs'].'</cell>';
                                if ($Info['runtime']>0) 
                                    $return .= '<cell>'.round($Info['diff']*100/$Info['runtime']).'%</cell>';
                                else 
                                    $return .= '<cell/>';
                                $return .= '<cell>'.$Info['history'].'</cell>';
                                $return .= '<cell>'.$Info['task'].'</cell>';
                                $return .= '<cell>'.$Info['update'].'</cell>';
                            }
                            else {
                                    $return .= '<row id="'.$i.'" open="1">';
                                    if ($id == '') {
                                        $return .= '<cell image="spooler.png"><![CDATA[<b> '.$here.'</b>]]></cell>';
                                        $return .= '<userdata name="type">spooler</userdata>';
                                    }
                                    else {
                                        $return .= '<cell image="folder.gif">'.$here.'</cell>';
                                    }
                            }                            
                           $return .= $this->Orders2XML( $leaf[$k], $id.'/'.$k, $Orders, $Steps );
                           $return .= '</row>';
                    }
            }
            return $return;
    }
    
}

?>
