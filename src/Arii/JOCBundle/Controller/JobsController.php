<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class JobsController extends Controller {

    protected $images;
    protected $ColorStatus = array (
            'SUCCESS' => '#ccebc5',
            'RUNNING' => '#ffffcc',
            'FAILURE' => '#fbb4ae',
            'STOPPED' => '#FF0000',
            'QUEUED' => '#AAA',
            'STOPPING' => '#ffffcc',
            'UNKNOW' => '#BBB',
            'PENDING' => '#ccebc5',
            'STARTING' => 'yellow',
            'FAILURE' => '#fbb4ae',
            'LOADED' => '#AAA',
            'STOPPING' => '#ffffcc',
            'NOT_INITIALIZED' => '#CCC',
            'ERROR' => '#fbb4ae',
            'WARNING' => '#ffffcc',
            'INFO' => 'lightcyan',
            'DEBUG' => 'lightcyan'
        );
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');          
    }

    public function indexAction()
    {
        return $this->render("AriiJOCBundle:Jobs:index.html.twig");
    }

    public function formAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiJOCBundle:Jobs:form.json.twig',array(), $response );
    }

    public function spoolerAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiJOCBundle:Jobs:form_spooler.json.twig',array(), $response );
    }

    public function targetAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiJOCBundle:Jobs:form_target.json.twig',array(), $response );
    }

    public function executionAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiJOCBundle:Jobs:form_execution.json.twig',array(), $response );
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiJOCBundle:Jobs:toolbar.xml.twig", array(), $response );
    }

    public function form_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiJOCBundle:Jobs:form_toolbar.xml.twig", array(), $response );
    }

    public function dashboardAction()
    {
        return $this->render("AriiJOCBundle:Jobs:dashboard.html.twig");
    }

    public function pie_chartAction()
    {
        return $this->render("AriiJOCBundle:Jobs:pie_chart.html.twig");
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiJOCBundle:Jobs:menu.xml.twig", array(), $response );
    }
    
    public function browser2Action()
    {
        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('chart');
        
        $request = Request::createFromGlobals();
        $path = $request->get('id');
        if (($p=strpos($path,'/'))>0) {
            $spooler = substr($path,0,$p);
            $path = substr($path,$p);
            if ($path=='') {
                $path = '/';
            }
        }
        else {
            $spooler = '%';
            $path = '/';
        }
        $cut = strlen($path);
        
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            '{spooler}'    => 'SPOOLER_ID',
            '{job_name}'   => 'fj.path',
            'fs.name'   => $spooler,
            'fj.path'      => $path.'%' );

        $qry = $sql->Select(array('fs.name as spooler',
            'fj.path','fj.state','fj.waiting_for_process',
            'flu.is_available','flu.is_missing'))
        .$sql->From(array('FOCUS_JOBS fj'))
        .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fj.spooler_id','fs.id'))
        .$sql->LeftJoin('FOCUS_LOCKS_USE flu',array('fj.id','flu.job_id'))
        .$sql->Where($Fields)
        .$sql->OrderBy(array('fs.name','fj.path'));

        $res = $data->sql->query($qry);
        $Dir = array();
        while ($line = $data->sql->get_next($res))
        {
            // on garde le début pour l'id
            $p = substr($line['path'],0,$cut);
            // et on ne prend que la fin
/*
            if (($e=strpos($p,'/',1))>0) {
                $p = substr($p,0,$e);
            }
*/
            $id = $line['spooler'].$p;
            
            $State[$id]['SUB'] = $p;
            $state = $line['state'];
            if ($state== 'running') {
                $state = 'RUNNING';
            }
            elseif ($state== 'stopped') {
                $state = 'STOPPED';
            }
            elseif ($state== 'not_initialized') {
                $state = 'UNDEF';
            }
            elseif ($state== 'pending') {
                if ($line['waiting_for_process']==1) {
                    $state = 'WAIT_PROCESS';
                }
                elseif ($line['is_missing']==1) {
                    $state = 'WAIT_LOCK';
                }
                else {
                    $state = 'PENDING';
                }
            }
            else {
                print "$state";
                exit();
            }
            
            if (isset($State[$id][$state])) {
                $State[$id][$state]++; 
            }
            else {
                $State[$id][$state]=1; 
            }
        }

        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<data>";
        foreach (array_keys($State) as $k) {
            $list .= '<item id="'.$k.'">';
            $list .= '<PATH>'.$k.'</PATH>';
            $list .= '<SPOOLER>'.$spooler.'</SPOOLER>';
            foreach (array('SUB','UNDEF','STOPPED','WAIT_PROCESS','WAIT_LOCK','PENDING','RUNNING') as $s ) {
                if (isset($State[$k][$s])) {
                    $nb = $State[$k][$s];
                }
                else {
                    $nb = 0;
                }
                $list .= '<'.$s.'>'.$nb.'</'.$s.'>';
            }
            $list .= '</item>';
        }
        $list .= "</data>";
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $list );
        return $response;
    }

    public function browserAction()
    {
        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('chart');
        
        $request = Request::createFromGlobals();
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            '{spooler}'    => 'SPOOLER_ID',
            '{job_name}'   => 'fj.path' );

        $qry = $sql->Select(array('fs.id as spooler_id','fs.name as spooler',
            'fj.path','fj.state','fj.waiting_for_process',
            'flu.is_available','flu.is_missing',
            'fjs.error','fjs.start_time','fjs.end_time'))
        .$sql->From(array('FOCUS_JOBS fj'))
        .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fj.spooler_id','fs.id'))
        .$sql->LeftJoin('FOCUS_LOCKS_USE flu',array('fj.id','flu.job_id'))
        .$sql->LeftJoin('FOCUS_JOB_STATUS fjs',array('fj.id','fjs.job_id'))
        .$sql->Where($Fields)
        .$sql->OrderBy(array('fs.name','fj.path'));

        $res = $data->sql->query($qry);
        $key_files = array();
        $Info = array();
        while ($line = $data->sql->get_next($res))
        {
            // on garde le début pour l'id
            $Paths = explode('/',$line['spooler'].'#'.$line['spooler_id'].$line['path']);
            $state = $line['state'];
            if ($state== 'running') {
                $state = 'RUNNING';
            }
            elseif ($state== 'stopped') {
                $state = 'STOPPED';
            }
            elseif ($state== 'not_initialized') {
                $state = 'UNDEF';
            }
            elseif ($state== 'pending') {
                if ($line['waiting_for_process']==1) {
                    $state = 'WAIT_PROCESS';
                }
                elseif ($line['is_missing']==1) {
                    $state = 'WAIT_LOCK';
                }
                elseif ($line['start_time']=='') {
                    $state = 'PENDING';                    
                }
                elseif ($line['end_time']=='') {
                    $state = 'RUNNING';
                }
                elseif ($line['error']==0) {
                    $state = 'FAILURE';
                }
                elseif ($line['error']==1) {
                    $state = 'SUCCESS';
                }
                else {
                    $state = 'UNKNOWN';
                }
            }
            else {
                print "$state";
                exit();
            }
            
            $job = array_pop($Paths);
            $id = '';
            foreach ($Paths as $p) {
                $id .= '/'.$p;
                if (isset($Info[$id]['nb']))
                    $Info[$id]['nb']++;
                else 
                    $Info[$id]['nb'] = 1;
                
                if (isset($Info[$id][$state]))
                    $Info[$id][$state]++;
                else 
                    $Info[$id][$state] = 1;
                $key_files[$id] = $id;        
            }            
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        if (count($key_files)==0) {
            $response->setContent( $this->NoRecord() );
            return $response;
        }
        $tree = $this->explodeTree($key_files, "/");
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        $list .= $this->BrowserXML( $tree,'', $Info);
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

    public function BrowserXML($leaf,$id='',$Info)
    {
        $Color= array( 
            'STOPPED' => '#ff0000',
            'WAIT_PROCESS' => '#bebada',
            'WAIT_LOCK' => '#80b1d3',   
            'UNDEF' => '#ded9d9',
            'PENDING' => '#8dd3c7',
            'FAILURE' => '#fbb4ae',
            'RUNNING' => '#ffffcc',
            'SUCCESS' => '#ccebc5' );
        $tools = $this->container->get('arii_core.tools');
        $return = '';
        if (is_array($leaf)) {
            foreach (array_keys($leaf) as $k) {
                $Ids = explode('/',$k);
                $here = array_pop($Ids);
                $i  = '/'.substr("$id/$k",1); 
                if(isset($Info[$i]['nb']))
                {                    
                    $row = $Info[$i];
                    $return .= '<row>';
                    if (strpos($i,'/',1)===false) {
                        $return .= '<cell image="server.png">'.substr($here,0,strpos($here,'#')).'</cell>';
                    }
                    else {
                        $return .= '<cell image="folder.gif">'.$here.'</cell>';
                    }
                    $return .= '<cell>'.$Info[$i]['nb'].'</cell>';
                    foreach (array('PENDING','RUNNING','SUCCESS','FAILURE','STOPPED','UNDEF','WAIT_PROCESS','WAIT_LOCK') as $s) {
                        if (isset($Info[$i][$s])) {                           
                            $percent = round($Info[$i][$s]*100/$Info[$i]['nb']);
                            $return .= '<cell>'.$Info[$i][$s].'</cell>';
                            // $return .= '<cell style="padding: 0;"><![CDATA[<img src="'.$this->generateUrl('png_JOC_percent', array('percent' => $percent, 'color' => $Color[$s] )).'"/> '.$percent.'%]]></cell>';
                              $return .= '<cell style="background-image: url('.$this->generateUrl('png_JOC_percent', array('percent' => $percent, 'color' => $Color[$s] )).'); background-repeat: no-repeat;"> '.$percent.'%</cell>';
                        }
                        else 
                            $return .= '<cell/><cell/>';
                    }
                } else {
                    $return .= '<row id="'.$i.'">';
                    $return .= '<cell image="server.png">'.$here.'</cell>';
                }
                $return .= $this->BrowserXML( $leaf[$k], $id.'/'.$k, $Info);
                $return .= '</row>';
            }
        }
        return $return;
    }

    public function gridAction($ordered = 0, $only_warning = 1)
    {
        $request = Request::createFromGlobals();
        if ($request->get('chained')!='') {
            $ordered = $request->get('chained');
        }
        if ($request->get('only_warning')!='') {
            $only_warning = $request->get('only_warning');
        }

        $State = $this->container->get('arii_joc.state');
        $Jobs = $State->Jobs($ordered,$only_warning);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');      
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        
        foreach ($Jobs as $k=>$job) {
            $state = $job['STATE'];
            if ($only_warning and ($state=='pending')) continue;
            
            if (isset($this->ColorStatus[$state])) 
                $color = $this->ColorStatus[$state];
            else
                $color = 'black';
            $list .= '<row id="'.$job['DBID'].'" bgColor="'.$color.'">';
            $list .= '<cell>'.$job['SPOOLER'].'</cell>';
            $folder = $job['FOLDER'];
            $list .= '<cell>'.$job['FOLDER'].'</cell>';
            $list .= '<cell>'.$job['NAME'].'</cell>';
            $list .= '<cell>'.$job['TITLE'].'</cell>';
            $list .= '<cell>'.$state.'</cell>';
            $list .= '<cell>'.$job['STATE_TEXT'].'</cell>';
            $level = strtoupper($job['HIGHEST_LEVEL']);
            if ($level =='INFO') {
                $list .= '<cell>'.$job['LAST_INFO'].'</cell>';
            }
            elseif (substr($level,0,5)=='DEBUG') {
                $list .= '<cell bgColor="'.$this->ColorStatus['DEBUG'].'">'.$job['LAST_INFO'].'</cell>';
            }
            elseif (isset($job['LAST_'.$level])) {
                $list .= '<cell bgColor="'.$this->ColorStatus[$level].'">'.$job['LAST_'.$level].'</cell>';
            }
            else {
                $list .= '<cell/>';                
            }
            $list .= '<cell>'.$job['NEXT_START_TIME'].'</cell>';
            
            # Nettoyage des répertoires
            # A tester cette optimisation
            if (isset($Length[$folder])) {
                $l = $Length[$folder]; 
            }
            else {
                $l = strlen($job['FOLDER']);
                $Length[$folder] = $l;
            }
            foreach (array('SCHEDULE_NAME','PROCESS_CLASS_NAME') as $k) {
                $pc = $job[$k];
                if ($pc=='') {
                    $list .= "<cell/>";
                } 
                else {
                    if (substr($pc,0,$l)==$folder)
                            $pc = substr($pc,$l+1);
                    $list .= '<cell>'.$pc.'</cell>';
                }
            }
            $list .= '</row>';
        }
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

    public function pieAction($history_max=0,$ordered = 0, $only_warning = 1) {        

        $request = Request::createFromGlobals();
        if ($request->get('history')>0) {
            $history_max = $request->get('history');
        }
        if ($request->get('chained')!='') {
            $ordered = $request->get('chained');
        }
        if ($request->get('only_warning')!='') {
            $only_warning = $request->get('only_warning');
        }

        $state = $this->container->get('arii_joc.state');
        $Jobs = $state->Jobs($ordered,$only_warning);
        
        $State = array();
        foreach ($Jobs as $k=>$job) {
            $state = $job['STATE'];
            if (isset($State[$state]))
                $State[$state]++;
            else 
                $State[$state]=1;
        }
        
        $pie = '<data>';
        ksort($State);
        foreach (array_keys($State) as $k) {
            if (($State[$k]>0) or ($only_warning and ($k=='pending'))) {
                if (isset($this->ColorStatus[$k])) 
                    $color = $this->ColorStatus[$k];
                else
                    $color = 'black';
                $pie .= '<item id="'.$k.'"><STATUS>'.$k.'</STATUS><JOBS>'.$State[$k].'</JOBS><COLOR>'.$color.'</COLOR></item>';
            }
         }
        $pie .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $pie );
        return $response;
    }

    private function TextProtect($message) {
         return str_replace(array('<','>'),array('&lt;','agt;'),substr($message,15));
    }
    public function Job2XML($leaf,$id='',$Info)
    {
        $tools = $this->container->get('arii_core.tools');
        $color = array (
            'STARTING'=> 'yellow',
            'PENDING' => '#ccebc5',
            'SUCCESS' => '#ccebc5',
            'RUNNING' => '#ffffcc',
            'FAILURE' => '#fbb4ae',
            'STOPPED' => '#FF0000',
            'UNKNOWN' => 'grey',
            'INIT!' => 'black',
            '!QUEUE' => '#b25fdc',
            '!LOCK' => 'lightblue'
            );
        $return = '';
        if (is_array($leaf)) {
            foreach (array_keys($leaf) as $k) {
                $Ids = explode('/',$k);
                $here = array_pop($Ids);
                $i  = substr("$id/$k",1);                
                if(isset($Info[$i]))
                {                    
                    $row = $Info[$i];
                    $cell = "";
                    if ($row['ordered'] == 1)
                    {
                        $job_type = "ordered_job";
                    } elseif ($row['ordered'] == 0)
                    {
                        $job_type = "standalone_job";
                    }
                    
                    $blocked="ACT.";
                    if ($row['lock'] != '') {
                        $status = '!LOCK';
                        $blocked="WAIT";
                    }
                    elseif ($row['waiting_for_process']>0) {
                        $status = '!QUEUE';
                        $blocked="WAIT";
                    }
                    elseif ($row['state']=='stopped') {
                        $status="STOPPED";
                        $blocked="STOP";
                    }
                    elseif ($row['state']=='running') {
                        $status="RUNNING";
                    }
                    elseif ($row['state']=='not_initialized') {
                        $status="INIT!";
                        $blocked="STOP";
                    }
                    elseif ($row['state'] == 'UNKNOWN')  {
                        $status="UNKNOWN";                        
                    }
                    elseif ($row['state'] == 'STARTING')  {
                        $status="STARTING";                        
                    }
                    elseif ($row['error']==0) {
                        $status = "SUCCESS";
                    }
                    elseif ($row['error']>0) {
                        $status = "FAILURE";
                    }
                    else {
                        $status=strtoupper($row['state']);                    
                    }

                    $return .= '<row id="J:'.$row['job_id'].'" style="background-color: '.$color[$status].';" open="1">';
                    $cell .= '<cell>'.$i.' '.$row['title'].'</cell>';
                    $cell .= '<cell image="'.$job_type.'.png">'.$this->setTitle($here,$row['title']).'</cell>';
                    $cell .= '<cell>'.$status.'</cell>';
                    $cell .= '<cell>'.$blocked.'</cell>';
                    $cell .= '<cell><![CDATA[<img src="'.$this->images.'/'.strtolower($status).'.png"/>]]></cell>';
                    if (($status!='INIT!') and ($status!='PENDING') and (substr($status,0,1)!='!')) {
                        $cell .= '<cell>'.$row['exit_code'].'</cell>';
                        $cell .= '<cell>'.$row['start_time'].'</cell>';
                        $cell .= '<cell>'.$row['end_time'].'</cell>';
                        $cell .= '<cell>'.$row['duration'].'</cell>';
                        if ($row['start_time']!='')
                            $cell .= '<cell><![CDATA[<img src="'.$this->generateUrl('png_JID_gantt').'?'.$tools->Gantt($row['real_start_time'],$row['real_end_time'],$status).'"/>]]></cell>'; 
                        else 
                            $cell .= '<cell/>';
                    }
                    else {
                        $cell .= '<cell/><cell/><cell/><cell/><cell/>';
                    }
                    $cell .= '<cell><![CDATA['.$row['message'].']]></cell>';
                    if ($status!='INIT!') {
                        $cell .= '<cell>'.$row['next_start_time'].'</cell>';
                        $cell .= '<cell>'.$row['process_class'].'</cell>';
                    }
                    else {
                        $cell .= '<cell/><cell/><cell/>';
                    }
                    if ($row['cause']!='')
                        $cell .= '<cell><![CDATA[<img src="'.$this->images.'/'.strtolower($row['cause']).'.png"/>]]></cell>'; 

                    $return .= $cell;
                } 
                elseif ($id == '') {
                        $return .= '<row id="S:'.$i.'" open="1">';
                        $return .= '<cell>'.$i.'</cell>';
                        $return .= '<cell image="spooler.png"><![CDATA[<b> '.$here.'</b>]]></cell>';
                }
                else {
                        $return .= '<row id="F:'.$i.'" open="1">';
                        $return .= '<cell>'.$i.'</cell>';
                        $return .= '<cell image="folder.gif">'.$here.'</cell>';
                }
                $return .= $this->Job2XML( $leaf[$k], $id.'/'.$k, $Info);
                $return .= '</row>';
            }
        }
        return $return;
    }

    private function openState($Open, $State,$rowid) {
        if (isset($Open[$rowid]) and ($Open[$rowid])) return ' open="1"';
        if (isset($State[$rowid])) {
            if ($State[$rowid]==1)
                return ' open="1"';
        }
        return '';
    }

    private function setTitle($name, $title) {
        $info  = $this->XMLProtect($name);
        if (trim($title)!='') {
            if (substr($title,0,3)!='<a ')                    
                $info .= ' <font color="grey">('.$this->XMLProtect($title).')</font>';
            else 
                $info .= ' '.$title;
        }
        return '<![CDATA[ '.$info.']]>';
    }
    
    private function XMLProtect ($txt) {
        $txt = str_replace('<','&lt;',$txt);
        $txt = str_replace('>','&gt;',$txt);
       return $txt;
    }

    public function NoRecord()
    {
        $no = '<?xml version="1.0" encoding="UTF-8"?>';
        $no .= '<rows><head><afterInit><call command="clearAll"/></afterInit></head>
                <row id="scheduler" open="1"><cell image="spooler.png"><b>No record </b></cell>
                </row></rows>';
        return $no;
    }
    
    
}

?>