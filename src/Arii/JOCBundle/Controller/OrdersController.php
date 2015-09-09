<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrdersController extends Controller
{
    protected $ColorStatus = array( 
        "SUCCESS" => "#ccebc5",
        "READY" => "#ccebc5",
        "TRUE" => "#ccebc5",
        "SUSPENDED" => "red",
        "CHAIN STOP." => "red",
        "SPOOLER STOP." => "red",
        "SPOOLER PAUSED" => "#fbb4ae",
        "NODE STOP." => "red",
        "NODE SKIP." => "#ffffcc",
        "JOB STOP." => "#fbb4ae",        
        "RUNNING" => "#ffffcc",
        "ERROR" => "#fbb4ae",
        "WARNING" => "#fbb4ae",
        "FAILURE" => "#fbb4ae",
        "FALSE" => "#fbb4ae",
        "DONE" => "lightblue",
        "ENDED" => "lightblue",
        "ON REQUEST" => "lightblue",
        "FATAL" => 'red',
        'WAITING' => '#DDD',
        "SETBACK" => "#fbb4ae",
        'UNKNOWN!' => '#FFF'
        );
    
    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
    }
    
    public function indexAction()
    {
      return $this->render('AriiJOCBundle:Orders:index.html.twig' );
    }

   public function grid_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Orders:grid_toolbar.xml.twig',array(), $response );
    }

    public function formAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiJOCBundle:Orders:form.json.twig',array(), $response );
    }

    public function form_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiJOCBundle:Orders:form_toolbar.xml.twig", array(), $response );
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Orders:grid_menu.xml.twig',array(), $response );
    }

    public function listAction()
    {
        return $this->render('AriiJOCBundle:Orders:list.html.twig');
    }

    public function gridAction($sort='last')
    {
        $request = Request::createFromGlobals();        
        $nested = $request->get('chained');
        $only_warning = $request->get('only_warning');
        $sort = $request->get('sort');

        $state = $this->container->get('arii_joc.state');
        $Orders = $state->Orders($nested,$only_warning,$sort);
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        
        foreach($Orders as $line) {
            $status = $line['STATUS'];
            $list .= '<row id="'.$line['ID'].'" bgColor="'.$this->ColorStatus[$status].'">';
            $list .= '<cell>'.$line['SPOOLER_ID'].'</cell>';
            $list .= '<cell>'.$line['JOB_CHAIN'].'</cell>';
            $list .= '<cell>'.$line['ORDER'].'</cell>';
            $list .= '<cell>'.$line['STATE'].'</cell>';
            $list .= '<cell>'.$line['STATE_TEXT'].'</cell>';
            $list .= '<cell>'.$line['STATUS'].'</cell>';
            $list .= '<cell>'.$line['START_TIME'].'</cell>';
            $list .= '<cell>'.$line['NEXT_START_TIME'].'</cell>';
            $list .= '<cell>'.$line['TITLE'].'</cell>';
            $list .= '</row>';
        }

        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }
    
    public function list_xmlAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');

        $sql = $this->container->get('arii_core.sql');
        // On charge les scénarios, c'est la partie statique
        $qry = $sql->Select(array( 'fo.ID as ORDER_ID','fo.NAME as ORDER_NAME','fo.START_TIME as ORDER_START','fo.NEXT_START_TIME','fo.TITLE as ORDER_TITLE','fo.STATE as ORDER_STATE',
                    'fc.ID as CHAIN_ID', 'fc.NAME as CHAIN_NAME', 'fc.PATH', 'fc.TITLE as CHAIN_TITLE',
                    'fs.id as SPOOLER_ID','fs.SPOOLER_ID as SPOOLER_NAME', 
                    'fjcn.STATE', 
                    'fj.ID as NODE_JOB_ID','fj.NAME as JOB_NAME', 'fj.TITLE as JOB_TITLE',
                    'fjc.ID as NODE_CHAIN_ID','fjc.NAME as CHAIN_NAME', 'fjc.TITLE as NODE_TITLE',
                    'status.START_TIME','status.END_TIME','status.STATE','status.STATE_TEXT'
                     ))
                .$sql->From(array('FOCUS_ORDERS fo'))
                .$sql->LeftJoin('FOCUS_ORDER_STATUS status',array('fo.ID','status.ORDER_ID'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS fc',array('fo.JOB_CHAIN_ID','fc.ID'))
                .$sql->LeftJoin('FOCUS_JOB_CHAIN_NODES fjcn',array('fo.JOB_CHAIN_NODE_ID','fjcn.ID'))
                .$sql->LeftJoin('FOCUS_JOBS fj',array('fjcn.JOB_ID','fj.ID'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS fjc',array('fjcn.JOB_CHAIN_ID','fjc.ID'))                
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fo.SPOOLER_ID','fs.id'))
                .$sql->OrderBy(array('fs.SPOOLER_ID','fo.PATH'));              

        $res = $data->sql->query($qry);
        $Info = array();
        $StepInfo = array();
        $key_files = array();
        $ChainInfo = $OrderInfo = $StepInfo = $Open = array();
        while ($line = $data->sql->get_next($res))
        {
            $order_name = $line['ORDER_NAME'];
            $chain_name = $line['PATH'];
            
            $cn = $line['SPOOLER_ID'].$chain_name;
            foreach (array('CHAIN_ID','CHAIN_NAME','CHAIN_TITLE') as $k) {
                $ChainInfo[$cn][$k] = $line[$k];
            }             
            $key_files[$cn] = $cn;

            // Ordre liés à l'étape
            $on = $cn.'/'.$order_name;
            foreach (array('ORDER_ID','ORDER_NAME','ORDER_TITLE','NEXT_START_TIME','ORDER_STATE','STATE','START_TIME','END_TIME') as $k) {
                $OrderInfo[$on][$k] = $line[$k];
            }
            $OrderInfo[$on]['ORDER_STATUS'] = 'SUSPENDED';
            $key_files[$on] = $on;

            // Mauvaise synchro ?
            if ($line['STATE']!='ORDER_STATE') {
          //      foreach (array('STATE','START_TIME','END_TIME') as $k) {
          //          $OrderInfo[$on][$k] = '';
          //      }
            }
            // Etapes liees a l'ordre
/*            $sn = $on.'/'.$line['STATE'];
            foreach (array('STATE','NODE_JOB_ID','JOB_NAME','JOB_TITLE','NODE_CHAIN_ID','CHAIN_NAME') as $k) {
                $StepInfo[$sn][$k] = $line[$k];
            }            
            $key_files[$sn] = $sn;
*/
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        if (count($key_files)==0) {
            $response->setContent( $this->NoRecord() );
            return $response;
        }
        
        $tools = $this->container->get('arii_core.tools');       
        $tree = $tools->explodeTree($key_files, "/");
        
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        $session = $this->container->get('arii_core.session');
        $States = $session->get('state');
        if (!isset($States['joc_chains'])) $States['joc_chains'] =array();            
        $list .= $this->Orders2XML( $tree,'', $ChainInfo, $OrderInfo, $StepInfo, $States['joc_chains'], $Open);
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }
    
    public function Orders2XML($leaf, $id="", $ChainInfo, $OrderInfo, $StepInfo, $State, $Open)
    {
        $treegrid = $this->container->get('arii_joc.treegrid');
        $return = '';
        if (is_array($leaf)) {
            foreach (array_keys($leaf) as $k) {
                $Ids = explode('/',$k);
                $here = array_pop($Ids);
                $i  = substr("$id/$k",1);
                if(isset($ChainInfo[$i]))
                {
                    $Info = $ChainInfo[$i];
                    $rowid = 'C#'.$Info['CHAIN_ID'];
                    $return .= '<row id="'.$rowid.'" open="1">';
                    $return .= '<cell image="job_chain.png">'.$treegrid->setTitle($Info['CHAIN_NAME'],$Info['CHAIN_TITLE']).'</cell>';
                }
                elseif (isset($OrderInfo[$i]))
                {
                    $Info = $OrderInfo[$i];
                    $rowid = 'O:'.$Info['ORDER_ID'];
                    $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open, $State,$rowid).$treegrid->getStyle($Info['ORDER_STATUS']).'>';
                    $return .= '<cell image="order.png"> '.$treegrid->setTitle($Info['ORDER_NAME'],$Info['ORDER_TITLE']).'</cell>';
                    $return .= '<cell>'.$Info['ORDER_STATE'].'</cell>';
                    $return .= '<cell>'.$Info['ORDER_STATUS'].'</cell>';
                    $return .= '<cell>'.$Info['START_TIME'].'</cell>';
                    $return .= '<cell>'.$Info['END_TIME'].'</cell>';
                    $return .= '<cell>'.$Info['NEXT_START_TIME'].'</cell>';
                }
                elseif (isset($StepInfo[$i])) {
                    $Info = $StepInfo[$i];
                    $rowid = 'S:'.$treegrid->XMLProtect($i);
                    $return .= '<row id="'.$rowid.'" open="1">';
                    $return .= '<cell image="bullet_green.png">'.$treegrid->XMLProtect($here).'</cell>';
                    $return .= '<cell image="bullet_green.png">'.$treegrid->setTitle($Info['JOB_NAME'],$Info['JOB_TITLE']).'</cell>';
                }
                else {
                    if ($id == '') {
                        $rowid = 'P#'.$i;
                        $return .= '<row id="'.$rowid.'" open="1">';
                        $return .= '<cell image="spooler.png"><![CDATA[<b> '.$here.'</b>]]></cell>';
                        $return .= '<userdata name="type">spooler</userdata>';
                    }
                    else {
                        $return .= '<row id="'.$i.'" open="1">';
                        $return .= '<cell image="folder.gif">'.$here.'</cell>';
                    }
                }
                $return .= $this->Orders2XML( $leaf[$k], $id.'/'.$k, $ChainInfo, $OrderInfo, $StepInfo, $State, $Open );
                $return .= '</row>';
            }
        }
        return $return;
    }
/*******************************************/
    public function pieAction()
    {
        $request = Request::createFromGlobals();        
        $nested = $request->get('chained');
        $only_warning = $request->get('only_warning');
        $sort = $request->get('sort');

        $state = $this->container->get('arii_joc.state');
        $Orders = $state->Orders($nested,$only_warning,$sort);
        
        $S = array('SUSPENDED','SETBACK','RUNNING','WAITING','DONE');
        foreach ($S as $status) {
            $Status[$status]=0;
        }
        foreach ($Orders as $order) {
            $status = $order['STATUS'];
            if (isset($Status[$status]))
                $Status[$status]++;
            else 
                $Status[$status]=1;
        }
        $pie = "<?xml version='1.0' encoding='utf-8' ?>";
        $pie .= "<data>";
        foreach ($S as $status) {
            $pie .= '<item id="'.$status.'"><STATUS>'.$status.'</STATUS>';
            $pie .= '<JOBS>'.$Status[$status].'</JOBS>';
            $pie .= '<COLOR>'.$this->ColorStatus[$status].'</COLOR></item>';
        }
        $pie .= "</data>";
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $pie );
        return $response;
//        return $this->render('AriiJOCBundle:Menu:global.xml.twig', array( 'update' => $refresh, 'database' => $database ), $response);
    }

}
