<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NestedController extends Controller
{
    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
    }

    public function chartsAction()
    {
        $session = $this->container->get('arii_core.session');
        
        // Une date peut etre passe en get
        $request = Request::createFromGlobals();
        if ($request->query->get( 'ref_date' )) {
            $ref_date   = $request->query->get( 'ref_date' );
            $session->setRefDate( $ref_date );
        } else {
            $ref_date   = $session->getRefDate();
        }
        $Timeline['ref_date'] = $ref_date;
        
        $past   = $session->getRefPast();
        $future = $session->getRefFuture();
        
        // On prend 24 fuseaux entre maintenant et le passe
        // on trouve le step en minute
        $step = ($future-$past)*2.5; // heure * 60 minutes / 24 fuseaux
        $Timeline['step'] = $step;
    
        // on recalcule la date courante moins la plage de passé 
        $year = substr($ref_date, 0, 4);
        $month = substr($ref_date, 5, 2);
        $day = substr($ref_date, 8, 2);
        
        $start = substr($session->getPast(),11,2);
        $Timeline['start'] = (60/$step)*$start;
        $Timeline['js_date'] = $year.','.($month - 1).','.$day;
        
        $refresh = $session->GetRefresh();
        
        // Liste des spoolers pour cette plage
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            'spooler'    => 'SPOOLER_ID',
            'start_time' => 'START_TIME',
            'end_time'   => 'END_TIME' );

        $qry = 'SELECT DISTINCT SPOOLER_ID 
                FROM SCHEDULER_HISTORY
                where '.$sql->History($Fields).'
                ORDER BY SPOOLER_ID';
        
        $SPOOLERS = array();
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            array_push( $SPOOLERS,$line['SPOOLER_ID'] ); 
        }
        $Timeline['spoolers'] = $SPOOLERS;
        
        return $this->render('AriiJOCBundle:Chains:charts.html.twig', array('refresh' => $refresh, 'Timeline' => $Timeline ) );
    }

/*******************************************/
    
    public function listAction()
    {
        return $this->render('AriiJOCBundle:Nested:list.html.twig');
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Nested:menu.xml.twig', array(), $response);
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
        return $this->render('AriiJOCBundle:Nested:toolbar.xml.twig', array(), $response);
    }

    public function list_xmlAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $sql = $this->container->get('arii_core.sql');        
        $date = $this->container->get('arii_core.date');        
        // On charge les scénarios, c'est la partie statique
        $Fields = array( '{spooler}' => 'fs.name',
                         'fjcn.job_id' => '(null)' );
        $qry = $sql->Select(array( 
                    'fjc.id as id_job_chain','fjc.title as chain_title','fjc.state as chain_status','fjc.path','fjc.name as chain_name','fjc.max_orders as global_max_orders',        
                    'fjc2.id as id_sub_chain','fjc2.title as sub_chain_title','fjc2.state as sub_chain_status','fjc2.path as sub_chain_path','fjc2.name as sub_chain_name','fjc2.orders','fjc2.running_orders','fjc2.max_orders',
                    'fs.id as id_spooler','fs.name as spooler_name','fs.updated as spooler_update','fs.state as spooler_state','fs.loops','fs.time',
                    'fjcn.id as id_step','fjcn.state','fjcn.action', 'fjcn.chain_id as id_sub_chain','fjcn.next_state','fjcn.error_state','fjcn.ordering',
                    'fjcn2.id as id_sub_step','fjcn2.state as sub_state','fjcn2.action as sub_action', 'fjcn2.job_id', 'fjcn2.next_state as sub_next_state', 'fjcn2.error_state as sub_error_state','fjcn2.ordering as sub_ordering',
                    'fj.id as id_job','fj.name as job_name','fj.title as job_title','fj.error','fj.state as job_state','fj.state_text','fj.last_info','fj.last_warning','fj.last_error','fj.error_text',
                    'fo.id as id_order','fo.name as order_name','fo.title','fo.start_time','fo.end_time','fo.suspended','fo.on_blacklist','fo.next_start_time','fo.setback','fo.setback_count',
                    'ft.id as id_task','ft.task','ft.pid','ft.state as task_state',
                    'ff.directory', 'ff.regex' ))
                .$sql->From(array('FOCUS_JOB_CHAINS fjc'))
                .$sql->LeftJoin('FOCUS_JOB_CHAIN_NODES fjcn',array('fjcn.job_chain_id','fjc.id'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS fjc2',array('fjcn.chain_id','fjc2.id'))
                .$sql->LeftJoin('FOCUS_JOB_CHAIN_NODES fjcn2',array('fjcn2.job_chain_id','fjc2.id'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fjc.spooler_id','fs.id'))  
                .$sql->LeftJoin('FOCUS_JOBS fj',array('fjcn2.job_id','fj.id'))
                .$sql->LeftJoin('FOCUS_ORDERS fo',array('fjcn2.id','fo.job_chain_node_id'))
                .$sql->LeftJoin('FOCUS_FILE_ORDERS ff',array('fjc2.id','ff.job_chain_id'))
                .$sql->LeftJoin('FOCUS_TASKS ft',array('fo.task_id','ft.id'))
                .$sql->Where( $Fields )
                .' and not(isnull(fjcn.next_state))'
                .$sql->OrderBy(array('fs.spooler_id','fjc.path','fjcn.ordering','fjcn2.ordering'));              

        $res = $data->sql->query($qry);
        $Info = $TaskInfo = $StepInfo = $JobInfo = $FileInfo = $GlobalInfo = $OrderInfo = $ChainInfo = $SpoolerInfo= $Open = array();
        
        $key_files = array();
        $Lines = array();
        // premiere passe pour recuperer les infos
        while ($line = $data->sql->get_next($res))
        {   
            $s = $line['spooler_name'];
            // si spooler pas encore traite
            if ($s=='supervisor') continue;
            if (!isset($SpoolerInfo[$s]['id'])) {
               $SpoolerInfo[$s]['id'] = $line['id_spooler'];                
               $updated = (time() - $line['spooler_update']);
               if ($updated>60) {
                   $SpoolerInfo[$s]['status'] = 'DELAYED';
               }
               else {
                   $SpoolerInfo[$s]['status'] = strtoupper($line['spooler_state']);
               }
               $SpoolerInfo[$s]['orders'] = 0;
               $SpoolerInfo[$s]['start_time'] = $date->ShortDate($date->Date2Local( $line['time'], $line['spooler_name'] ) );
               $SpoolerInfo[$s]['information'] = $this->get('translator')->trans('Last update').': '.$updated.'s'
                       .' ('.$this->get('translator')->trans('Loop').': '.$line['loops'].')';                
            }
            
            $chain = $line['path'];            
            // chain maitresse 
            $gn = $s.$chain;
            if (!isset($GlobalInfo[$gn]['id'])) {
                $GlobalInfo[$gn]['information'] = $line['sub_chain_name'];
                $GlobalInfo[$gn]['message'] = '';
                $GlobalInfo[$gn]['type'] = 'global';

                $GlobalInfo[$gn]['id'] = $line['id_job_chain'];
                $GlobalInfo[$gn]['status'] = 'ACTIVE';
                foreach (array('chain_name','global_max_orders') as $k) {
                    $GlobalInfo[$gn][$k] = $line[$k];
                }          
                $GlobalInfo[$gn]['title'] = $line['chain_title'];
                $GlobalInfo[$gn]['orders'] = 0;
                $GlobalInfo[$gn]['running_orders'] = 0;                    

                $key_files[$gn] = $gn;
            }
            
            // si noeud de fin, on quitte
            if ($line['sub_next_state']=='') continue;
            
            // Sous-chaine 
            $sub_chain = $chain.'/'.$line['state'];
            $cn = $s.$sub_chain;
            if (!isset($ChainInfo[$cn]['id'])) {
                $ChainInfo[$cn]['id'] = $line['id_step'];
                $ChainInfo[$cn]['information'] = $line['sub_chain_name'];
                if ($line['orders']>0) {
                    $ChainInfo[$cn]['message'] = $this->get('translator')->trans('Orders').': '.$line['orders'];
                    if ($line['max_orders']>0)
                        $ChainInfo[$cn]['message'] .= '/'.$line['max_orders'];
                    if ($line['running_orders']>0)
                    $ChainInfo[$cn]['message'] .= ' ('.$this->get('translator')->trans('Running').': '.$line['running_orders'].')';
                    $GlobalInfo[$gn]['orders'] += $line['orders'];
                    $SpoolerInfo[$s]['orders'] += $line['orders'];
                    $GlobalInfo[$gn]['running_orders'] += $line['running_orders'];                    
               }
               else {
                   $ChainInfo[$cn]['message'] = '';
               }
               $line['running_orders'].'/'.$line['max_orders'];
                $ChainInfo[$cn]['type'] = 'simple';

                $ChainInfo[$cn]['status'] = strtoupper($line['sub_chain_status']);
                if ($ChainInfo[$cn]['status']=='RUNNING')
                    $ChainInfo[$cn]['status']='ACTIVE';
                foreach (array('chain_name') as $k) {
                    $ChainInfo[$cn][$k] = $line[$k];
                }          
                $ChainInfo[$cn]['title'] = $line['sub_chain_title'];
                $key_files[$cn] = $cn;
                
            }            

            // Step 
            $sn = $cn.'/'.$line['sub_state'];
            if (!isset($StepInfo[$sn]['id'])) {
                $StepInfo[$sn]['id'] = $line['id_sub_step'];
                $StepInfo[$sn]['title'] = $line['job_title'];   
                if ($line['sub_action']=='next_state') {
                    $StepInfo[$sn]['status'] = 'SKIPPED';
                }
                elseif ($line['sub_action']=='stop') {
                    $StepInfo[$sn]['status'] = 'STOPPED';
                }
                elseif ($line['error']>0) {
                    // type de step
                    switch(substr($line['id_sub_step'],0,1)) {
                        case '!':
                            $StepInfo[$sn]['status'] = 'FATAL';
                            break;
                        case '?':
                            $StepInfo[$sn]['status'] = 'FALSE';
                            break;
                        default:
                            $StepInfo[$sn]['status'] = 'FAILURE';
                            break;
                    }
                }
                else {
                    switch(substr($line['id_sub_step'],0,1)) {
                        case '?':
                            $StepInfo[$sn]['status'] = 'TRUE';
                            break;
                        default:
                            $StepInfo[$sn]['status'] = strtoupper($line['job_state']);
                            break;
                    }
                }
                $StepInfo[$sn]['job_name'] = $line['job_name'];
                $StepInfo[$sn]['message'] = '';
                if ($line['error_text']!='')
                    $StepInfo[$sn]['message'] .= 'test'.$line['error_text'];
                if ($line['state_text']!='')
                    $StepInfo[$sn]['message'] .= $line['state_text'];
                $StepInfo[$sn]['information'] = '';
                if ($line['last_error']!='')
                    $StepInfo[$sn]['information'] .= $this->WriteLast('E',$line['last_error']);
                if ($line['last_warning']!='')
                    $StepInfo[$sn]['information'] .= $this->WriteLast('W',$line['last_warning']);
                if ($line['last_info']!='')
                    $StepInfo[$sn]['information'] .= $this->WriteLast('I',$line['last_info']);
                $key_files[$sn] = $sn;
            }

            // Le job en cas d'erreur            
            if (($line['job_state']=='stopped') or ($line['error']>0)) {

                $jn = $sn.'/'.$line['job_name'];
                if (!isset($JobInfo[$jn]['id'])) {
                    $JobInfo[$jn]['id'] = $line['id_job'];
                    $JobInfo[$jn]['title'] = '';
                    $JobInfo[$jn]['message'] = $line['error_text'];
                    $JobInfo[$jn]['information'] = $StepInfo[$sn]['information'];                    
                    $JobInfo[$jn]['status'] = strtoupper($line['job_state']);
                    if ($JobInfo[$jn]['status']=='STOPPED') {                    
                        $ChainInfo[$cn]['status'] = 'STOPPED';
                        $GlobalInfo[$gn]['status'] = 'STOPPED';
                    }
                    $key_files[$jn] = $jn;
                }
            }
            
            // Ordre liés à l'étape
            if ($line['order_name']!='') {
                $on = $sn.'/'.$line['order_name'];
                if (!isset($OrderInfo[$on]['id'])) {
                    foreach (array('title','order_name') as $k) {
                        $OrderInfo[$on][$k] = $line[$k];
                    }
                    foreach (array('start_time','end_time','next_start_time') as $k) {
                        if (substr($line[$k],0,4)=='2038') {
                            $OrderInfo[$on][$k] = $this->get('translator')->trans('Never');
                        }
                        elseif ($line[$k]!='') {
                            $OrderInfo[$on][$k] = $date->ShortDate($date->Date2Local( $line[$k], $line['spooler_name'] ) );
                        }
                        else {
                            $OrderInfo[$on][$k] = '';
                        }
                    }
                    $OrderInfo[$on]['information'] = '';
                    $OrderInfo[$on]['status'] = '';
                    $OrderInfo[$on]['message'] = '';
                    if ($ChainInfo[$cn]['status']=='STOPPED') {
                        $OrderInfo[$on]['status'] = 'STOPPED';
                        $OrderInfo[$on]['start_time'] = $date->ShortDate($date->Date2Local( $line['end_time'], $line['spooler_name'] ) );
                    }
                    elseif ($line['setback']!='') {
                        $OrderInfo[$on]['status'] = 'SETBACK';
                        $OrderInfo[$on]['start_time'] = $date->ShortDate($date->Date2Local( $line['setback'], $line['spooler_name'] ) );
                        $OrderInfo[$on]['message'] = $this->get('translator')->trans('Setback count: ').$line['setback_count'];
                    }
                    elseif ($line['suspended']>0) {
                        $OrderInfo[$on]['status'] = 'SUSPENDED';
                    }
                    elseif ($line['on_blacklist']>0) {
                        $OrderInfo[$on]['status'] = 'BLACKLIST';
                    }
                    elseif ($line['next_start_time']!='') {
                        $OrderInfo[$on]['status'] = 'READY';
                        $OrderInfo[$on]['start_time'] = $date->ShortDate($date->Date2Local( $line['next_start_time'], $line['spooler_name'] ) );
                    }
                    elseif ($line['end_time']!='') {
                        $OrderInfo[$on]['status'] = 'ENDED';
                        $OrderInfo[$on]['start_time'] = $date->ShortDate($date->Date2Local( $line['end_time'], $line['spooler_name'] ) );
                    }
                    elseif ($line['start_time']!='') {
                        $OrderInfo[$on]['status'] = 'RUNNING';
                        // On remonte le statut
                        if ($StepInfo[$sn]['status']!='STOPPED') {
                            $StepInfo[$sn]['status'] = 'RUNNING';
                        }
                        if (($ChainInfo[$cn]['status']!='JOB STOP')
                                && ($ChainInfo[$cn]['status']!='STOPPED')) { 
                            $ChainInfo[$cn]['status'] = 'RUNNING';
                            $GlobalInfo[$gn]['status'] = 'RUNNING';
                        }
                        // on ouvre ?
                        $Open['S:'.$StepInfo[$sn]['id']] = 
                        $Open['N:'.$ChainInfo[$cn]['id']] = 
                        $Open['G:'.$GlobalInfo[$gn]['id']] = 
                        $Open['X:'.$SpoolerInfo[$s]['id']] = true;                        
                    } 
                    else {
                        $OrderInfo[$on]['status'] = 'UNKNOWN';                        
                    }
                    $OrderInfo[$on]['id'] = $line['id_order'];
                    $key_files[$on] = $on;
                    
                    // Si le job est stopped, l'ordre ne eut être running 
                }
            }
            
            // un tache en cours
            if ($line['task']!='') {
                $tn = $on.'/'.$line['task'];
                if (!isset($TaskInfo[$tn]['id'])) {
                    $TaskInfo[$tn]['id'] = $line['id_task'];
                    $TaskInfo[$tn]['task'] = $line['task'];
                    $TaskInfo[$tn]['message'] = 'PID: '.$line['pid'];
                    if ($line['task_state']=='running_process') 
                        $TaskInfo[$tn]['status'] = 'PROCESSED';
                    else
                        $TaskInfo[$tn]['status'] = strtoupper($line['task_state']);
                    $TaskInfo[$tn]['information'] = '';
                    $key_files[$tn] = $tn;                    
                }
            }
        }
        
        // On complete les chaines globales
        foreach (array_keys($GlobalInfo) as $k) {
            if ($GlobalInfo[$k]['orders']>0) 
                $GlobalInfo[$k]['message'] .= $this->get('translator')->trans('Orders').': '.$GlobalInfo[$k]['orders'];
            if ($GlobalInfo[$k]['global_max_orders']>0)
                $GlobalInfo[$k]['message'] .= '/'.$GlobalInfo[$k]['global_max_orders'];
            if ($GlobalInfo[$k]['running_orders']>0)
                $GlobalInfo[$k]['message'] .= ' ('.$this->get('translator')->trans('Running').': '.$GlobalInfo[$k]['running_orders'].')';
        }

        // Synthese des spoolers
        foreach (array_keys($SpoolerInfo) as $k) {
            $SpoolerInfo[$k]['message'] =  $this->get('translator')->trans('Orders').': '.$SpoolerInfo[$k]['orders'];
        }
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        if (count($key_files)==0) {
           $no = '<?xml version="1.0" encoding="UTF-8"?>';
           $no .= '
    <rows><head><afterInit><call command="clearAll"/></afterInit></head>
<row id="scheduler"><cell/><cell image="spooler.png"> '.$this->get('translator')->trans('No nested chains.').'</cell>
</row></rows>';            
            $response->setContent( $no );
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

        $list .= $this->Orders2XML( $tree,'', $SpoolerInfo, $GlobalInfo, $ChainInfo, $OrderInfo, $StepInfo, $JobInfo, $TaskInfo, $FileInfo, $States['joc_chains'], $Open);
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }
    
    private function WriteLast($type,$msg) {
        $p = strpos($msg,' ');        
        return '['.$type.'] '.substr($msg,$p+1);
        if ($type=='I') $color='blue';
        elseif ($type=='W') $color='orange';
        elseif ($type=='E') $color='red';
        else $color='black';
         return '<font color="'.$color.'">['.$type.'] '.substr($msg,$p+1).'</font>';        
    }
    
    public function Orders2XML($leaf, $id="", $SpoolerInfo, $GlobalInfo, $ChainInfo, $OrderInfo, $StepInfo, $JobInfo, $TaskInfo, $FileInfo, $State, $Open )
    {
        $treegrid = $this->container->get('arii_joc.treegrid');
        $return = '';
        if (is_array($leaf)) {
            foreach (array_keys($leaf) as $k) {
                $Ids = explode('/',$k);
                $here = array_pop($Ids);
                $i  = substr("$id/$k",1);
                if (isset($GlobalInfo[$i]))
                {
                    $Info = $GlobalInfo[$i];
                    // Cas de la nested chain
                    $rowid = 'G:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open, $State,$rowid).$treegrid->getStyle($Info['status']).'>';
                    $return .= "<cell>$i</cell>";
                    $return .= '<cell image="global_chain.png">'.$treegrid->setTitle($Info['chain_name'],$Info['title']).'</cell>';
                    $return .= '<cell>'.$Info['message'].'</cell>';
                    $return .= '<cell>'.$Info['status'].'</cell>';
                    $return .= '<cell/>';
                    $return .= '<cell>'.$Info['information'].'</cell>';
                }
                elseif(isset($ChainInfo[$i]))
                {
                    $Info = $ChainInfo[$i];
                    // Cas de la nested chain
                    $rowid = 'N:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open, $State,$rowid).$treegrid->getStyle($Info['status']).'>';
                    $return .= "<cell>$i</cell>";
                    $return .= '<cell image="job_chain.png">'.$treegrid->setTitle($k,$Info['title']).'</cell>';
                    $return .= '<cell>'.$Info['message'].'</cell>';
                    $return .= '<cell>'.$Info['status'].'</cell>';
                    $return .= '<cell/>';
                    $return .= '<cell>'.$Info['information'].'</cell>';
                }
                elseif (isset($OrderInfo[$i]))
                {
                    $Info = $OrderInfo[$i];
                    $rowid = 'O:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open, $State,$rowid).$treegrid->getStyle($Info['status']).'>';
                    $return .= "<cell>$i</cell>";
                    $return .= '<cell image="order.png">'.$treegrid->setTitle($here,$Info['title']).'</cell>';
                    $return .= '<cell>'.$Info['message'].'</cell>';
                    $return .= '<cell>'.$Info['status'].'</cell>';
                    $return .= '<cell>'.$Info['start_time'].'</cell>';
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['information'] ).']]></cell>';
                  //  $return .= '<cell>'.$Info['next_start_time'].'</cell>';
                }
                elseif (isset($StepInfo[$i])) {
                    $Info = $StepInfo[$i];
                    $rowid = 'S:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'" open="1"'.$treegrid->getStyle($Info['status']).'>';
                    $return .= "<cell>$i ".$treegrid->XMLProtect($Info['title'])."</cell>";

                    if ($Info['status']=='SKIPPED') { $bullet='orange'; }
                    elseif  ($Info['status']=='STOPPED')  { $bullet='red'; }
                    else { $bullet='green'; }
                    $return .= '<cell image="bullet_'.$bullet.'.png">'.$treegrid->setTitle($here,$Info['title']).'</cell>';
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['message'] ).']]></cell>';
                    $return .= '<cell>'.$Info['status'].'</cell>';
                    $return .= "<cell/>";
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['information'] ).']]></cell>';
                }
                elseif (isset($JobInfo[$i])) {
                    $Info = $JobInfo[$i];
                    $rowid = 'J:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'" open="1"'.$treegrid->getStyle($Info['status']).'>';
                    $return .= "<cell>$i ".$treegrid->XMLProtect($Info['title'])."</cell>";
                    $return .= '<cell image="ordered_job.png">'.$this->setTitle($here,$Info['title']).'</cell>';
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['message'] ).']]></cell>';
                    $return .= '<cell>'.$Info['status'].'</cell>';
                    $return .= "<cell/>";
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['information'] ).']]></cell>';
                }
                elseif (isset($TaskInfo[$i])) {
                    $Info = $TaskInfo[$i]; 
                    $rowid = 'T:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'">';
                    $return .= "<cell>$i</cell>";
                    $return .= '<cell image="process.png"> '.$TaskInfo[$i]['task'].'</cell>';
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['message'] ).']]></cell>';
                    $return .= '<cell>'.$Info['status'].'</cell>';
                    $return .= "<cell/>";
                    $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['information'] ).']]></cell>';
                }
                elseif (isset($FileInfo[$i])) {
                    $Info = $FileInfo[$i]; 
                    $rowid = 'F:'.$Info['id'];
                    $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open, $State,$rowid).'>';
                    $return .= "<cell>$i</cell>";
                    $return .= '<cell image="file_order.png"><![CDATA['.$treegrid->XMLProtect($FileInfo[$i]['directory']).' <font color="grey">'.$this->XMLProtect($FileInfo[$i]['regex']).'</font>]]></cell>';
                }
                else {
                    if ($id == '') {
                        $Info = $SpoolerInfo[$i];
                        $rowid = 'X:'.$Info['id'];
                        $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open, $State,$rowid).$treegrid->getStyle($Info['status']).'>';
                        $return .= "<cell/>";
                        $return .= '<cell image="spooler.png"><![CDATA[<b> '.$here.'</b>]]></cell>';
                        $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['message'] ).']]></cell>';
                        $return .= '<cell><![CDATA['.$Info['status'].']]></cell>';
                        $return .= '<cell>'.$Info['start_time'].'</cell>';
                        $return .= '<cell><![CDATA['.$treegrid->XMLProtect( $Info['information'] ).']]></cell>';
                    }
                    else {
                        $rowid = 'P:'.$i;
                        $return .= '<row id="'.$rowid.'"'.$treegrid->openState($Open, $State,$rowid).'>';
                        $return .= "<cell/>";
                        $return .= '<cell image="folder.gif"> '.$here.'</cell>';
                    }
                }
                $return .= $this->Orders2XML( $leaf[$k], $id.'/'.$k, $SpoolerInfo, $GlobalInfo, $ChainInfo, $OrderInfo, $StepInfo, $JobInfo, $TaskInfo, $FileInfo, $State, $Open  );
                $return .= '</row>';
            }
        }
        return $return;
    }

}
