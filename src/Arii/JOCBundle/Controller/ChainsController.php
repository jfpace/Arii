<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChainsController extends Controller
{
    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
    }

    public function listAction()
    {
        return $this->render('AriiJOCBundle:Chains:list.html.twig');
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Chains:menu.xml.twig', array(), $response);
    }

    public function pie_chartAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Chains:pie_chart.html.twig', array(), $response);
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Chains:toolbar.xml.twig', array(), $response);
    }

    public function list_xmlAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $sql = $this->container->get('arii_core.sql');        
        $date = $this->container->get('arii_core.date');        
        // On charge les scénarios, c'est la partie statique
        $Fields = array( '{spooler}' => 'fs.name',
                         'fj.id' => '(!null)' );
        $qry = $sql->Select(array( 
                    'fs.id as SPOOLER_ID','fs.name as SPOOLER_NAME','fs.updated as spooler_update',
                    'fjc.id as id_job_chain','fjc.title as chain_title','fjc.state as chain_status','fjc.path','fjc.name as chain_name','fjc.order_id_space',
                    'fjcn.id as id_step','fjcn.state','fjcn.action', 'fjcn.job_id', 'fjcn.chain_id as id_sub_chain','fjcn.next_state','fjcn.ordering',
                    'fj.id as id_job','fj.name as job_name','fj.title as job_title','fj.error','fj.state_text','fj.last_info','fj.last_warning','fj.last_error',
                    'fo.id as id_order','fo.name as order_name','fo.title','fo.start_time','fo.end_time','fo.suspended','fo.on_blacklist','fo.next_start_time',
                    'ff.directory', 'ff.regex' ))
                .$sql->From(array('FOCUS_JOB_CHAIN_NODES fjcn'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS fjc',array('fjc.id','fjcn.job_chain_id'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fjc.spooler_id','fs.id'))     
                .$sql->LeftJoin('FOCUS_JOBS fj',array('fjcn.job_id','fj.id'))
                .$sql->LeftJoin('FOCUS_ORDERS fo',array('fjcn.id','fo.job_chain_node_id'))
                .$sql->LeftJoin('FOCUS_FILE_ORDERS ff',array('fjc.id','ff.job_chain_id'))
                .$sql->Where( $Fields )
                .$sql->OrderBy(array('fs.name','fjc.path','fjcn.ordering'));              
        $res = $data->sql->query($qry);
        $Info = $StepInfo = $JobInfo = $FileInfo = $OrderInfo = $Space = array();
        
        $key_files = array();
        $Lines = array();
        // premiere passe pour recuperer les infos
        $Nodes = array();
        while ($line = $data->sql->get_next($res))
        {   
            $s = $line['SPOOLER_NAME'];
            // si spooler pas encore traite
            // if ($s=='supervisor') continue;
            if (!isset($SpoolerInfo[$s])) {
               $updated = (time() - $line['spooler_update']);
               if ($updated>60) {
                   $SpoolerInfo[$s]['status'] = 'DELAYED';
               }
               else {
                   $SpoolerInfo[$s]['status'] = 'STARTED';
               }
               foreach (array('SPOOLER_ID','SPOOLER_NAME') as $k) {
                   $SpoolerInfo[$s][$k] = $line[$k];              
               }
               $SpoolerInfo[$s]['message'] = '[INFO] '.$this->get('translator')->trans('Last update').': '.$updated.'s';                
            }
            
            $chain = $line['path'];
            $cn = $s.$chain;
            $ChainInfo[$cn]['id'] = $line['id_job_chain'];
            $ChainInfo[$cn]['information'] = '';
            $ChainInfo[$cn]['message'] = 'JOBS '.' '.$line['chain_name'].' dans '.$line['state'].' '.' '.$line['ordering'];
            $ChainInfo[$cn]['type'] = 'simple';

            $ChainInfo[$cn]['status'] = 'ACTIVE';
            foreach (array('chain_name') as $k) {
                $ChainInfo[$cn][$k] = $line[$k];
            }          
            $ChainInfo[$cn]['title'] = $line['chain_title'];
            $ChainInfo[$cn]['status'] = strtoupper($line['chain_status']);
            if ($ChainInfo[$cn]['status']=='RUNNING')
                $ChainInfo[$cn]['status']='ACTIVE';
            foreach (array('chain_name') as $k) {
                $ChainInfo[$cn][$k] = $line[$k];
            }          
            $key_files[$cn] = $cn;
                
            // Etapes liees
            $sn = $cn.'/'.$line['state'];
            foreach (array('state','job_name') as $k) {
                $StepInfo[$sn][$k] = $line[$k];
            }
            if ($line['action']=='stop') {
                $StepInfo[$sn]['status'] = 'STOPPED';
            }
            elseif ($line['action']=='next_state') {
                $StepInfo[$sn]['status'] = 'SKIPPED';
            }
            elseif ($line['error']>0) {
                $StepInfo[$sn]['status'] = 'FAILURE';
            }
            else {
                $StepInfo[$sn]['status'] = 'SUCCESS';
            }
            $StepInfo[$sn]['id'] = $line['id_step'];
            $StepInfo[$sn]['title'] = $line['job_title'];            
            $StepInfo[$sn]['message'] = 'test'.$line['ordering'].' '.$line['state'].' (('.$line['job_name'];
            $StepInfo[$sn]['information'] = $line['job_name'];
            if ($line['state_text']!='')
                $StepInfo[$sn]['information'] .= $line['state_text']."\n";
            if ($line['last_error']!='')
                $StepInfo[$sn]['information'] .= '[ERROR] '.$line['last_error']."\n";
            if ($line['last_warning']!='')
                $StepInfo[$sn]['information'] .= '[WARNING] '.$line['last_warning']."\n";
            if ($line['last_info']!='')
                $StepInfo[$sn]['information'] .= '[INFO] '.$line['last_info']."\n";
            $key_files[$sn] = $sn;
            
            // Job lié au step 
/*
            $jn = $sn.'/'.$line['job_name'];
            $key_files[$sn] = $sn;
            foreach (array('job_name','job_title') as $k) {
                $JobInfo[$jn][$k] = $line[$k];
            }
*/            
            // Ordre liés à l'étape
            if ($line['order_name']!='') {
                $on = $sn.'/'.$line['order_name'];
                foreach (array('title','order_name') as $k) {
                    $OrderInfo[$on][$k] = $line[$k];
                }
                foreach (array('start_time','end_time','next_start_time') as $k) {
                    if (substr($line[$k],0,4)=='2038') {
                        $OrderInfo[$on][$k] = $this->get('translator')->trans('Never');
                    }
                    elseif ($line[$k]!='') {
                        $OrderInfo[$on][$k] = $date->ShortDate($date->Date2Local( $line[$k], $line['SPOOLER_NAME'] ) );
                    }
                    else {
                        $OrderInfo[$on][$k] = '';
                    }
                }
                $OrderInfo[$on]['status'] = '';
                if ($line['suspended']>0) {
                    $OrderInfo[$on]['status'] = 'SUSPENDED';
                }
                elseif ($line['on_blacklist']>0) {
                    $OrderInfo[$on]['status'] = 'BLACKLIST';
                }
                elseif ($line['start_time']!='') {
                    $OrderInfo[$on]['status'] = 'RUNNING';
                } 
                $OrderInfo[$on]['id'] = $line['id_order'];
                $key_files[$on] = $on;
            }
        }
/*      
print "<pre>";
//print_r($Master);
print_r($key_files);
exit();
*/
        // Reorganisation des nested chains

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
        
        $list .= $this->Orders2XML( $tree,'', $SpoolerInfo, $ChainInfo, $OrderInfo, $StepInfo, $JobInfo, $FileInfo, $Space, $States['joc_chains']);
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }
    
    public function Orders2XML($leaf, $id="", $SpoolerInfo, $ChainInfo, $OrderInfo, $StepInfo, $JobInfo, $FileInfo, $Space, $State, $Open=array())
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
                    // Cas de la nested chain
                    $rowid = 'C:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open,$State,$rowid).$treegrid->getStyle($Info['status']).'>';
                    $return .= "<cell>$i</cell>";
                    $return .= '<cell image="job_chain.png">'.$treegrid->setTitle($k,$Info['title']).'</cell>';
                    $return .= '<cell>'.$Info['message'].'</cell>';
                    $return .= '<cell>'.$Info['status'].'</cell>';
                    $return .= '<cell/>';
                    $return .= '<cell/>';
                    $return .= '<cell/>';
                    $return .= '<cell>'.$Info['information'].'</cell>';
                }
                elseif (isset($OrderInfo[$i]))
                {
                    $Info = $OrderInfo[$i];
                    $rowid = 'O:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open,$State,$rowid).$treegrid->getStyle($Info['status']).'>';
                    $return .= "<cell>$i</cell>";
                    $return .= '<cell image="order.png">'.$treegrid->setTitle($here,$Info['title']).'</cell>';
                    $return .= '<cell>'.'</cell>';
                    $return .= '<cell>'.$Info['status'].'</cell>';
                    $return .= '<cell>'.$Info['start_time'].'</cell>';
                    $return .= '<cell>'.$Info['end_time'].'</cell>';
                    $return .= '<cell>'.$Info['next_start_time'].'</cell>';
                }
                elseif (isset($StepInfo[$i])) {
                    $Info = $StepInfo[$i]; 
                    $rowid = 'S:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'" open="1"'.$treegrid->getStyle($Info['status']).'>';
                    $return .= "<cell>$i</cell>";

                    if ($Info['status']=='SKIPPED') { $bullet='orange'; }
                    elseif  ($Info['status']=='STOPPED')  { $bullet='red'; }
                    else { $bullet='green'; }
                    $return .= '<cell image="bullet_'.$bullet.'.png">'.$treegrid->setTitle($here,$Info['title']).'</cell>';
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['message'] ).']]></cell>';
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['status'] ).']]></cell>';
                    $return .= "<cell/>";
                    $return .= "<cell/>";
                    $return .= "<cell/>";
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['message'] ).']]></cell>';
                }
                elseif (isset($FileInfo[$i])) {
                    $Info = $FileInfo[$i]; 
                    $rowid = 'F:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open,$State,$rowid).'>';
                    $return .= "<cell>$i</cell>";
                    $return .= '<cell image="file_order.png"><![CDATA['.$treegrid->XMLProtect($FileInfo[$i]['directory']).' <font color="grey">'.$this->XMLProtect($FileInfo[$i]['regex']).'</font>]]></cell>';
                }
                else {
                    if ($id == '') {
                        $Info = $SpoolerInfo[$i];
                        $rowid = 'X:'.$Info['SPOOLER_ID'];
                        $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open,$State,$rowid).$treegrid->getStyle($Info['status']).'>';
                        $return .= "<cell/>";
                        $return .= '<cell image="spooler.png"><![CDATA[<b> '.$Info['SPOOLER_NAME'].'</b>]]></cell>';
                        $return .= '<userdata name="type">spooler</userdata>';
                        $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['message'] ).']]></cell>';
                        $return .= '<cell><![CDATA['.$Info['status'].']]></cell>';
                    }
                    else {
                        $rowid = $i;
                        $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open,$State,$rowid).'>';
                        $return .= "<cell/>";
                        $return .= '<cell image="folder.gif"> '.$here.'</cell>';
                    }
                }
                $return .= $this->Orders2XML( $leaf[$k], $id.'/'.$k, $SpoolerInfo, $ChainInfo, $OrderInfo, $StepInfo, $JobInfo, $FileInfo, $Space, $State );
                $return .= '</row>';
            }
        }
        return $return;
    }
 
}
