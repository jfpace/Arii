<?php

namespace Arii\ATSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class JobsController extends Controller
{
    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../bundles/ariicore/images/wa');          
    }

    public function indexAction()
    {
        return $this->render('AriiATSBundle:Jobs:index.html.twig');
    }

    public function grid_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiATSBundle:Jobs:grid_toolbar.xml.twig',array(), $response );
    }

    public function grid_menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiATSBundle:Jobs:grid_menu.xml.twig',array(), $response );
    }

    public function statusAction($only_warning=0,$job_only=0)
    {
        $request = Request::createFromGlobals();
        if ($request->query->get( 'only_warning' ))
            $only_warning = $request->query->get( 'only_warning' );
        if ($request->query->get( 'job_warning' ))
            $only_warning = $request->query->get( 'job_warning' );

        $sql = $this->container->get('arii_core.sql');                  
        $qry = $sql->Select(array('j.JOID','j.BOX_JOID','j.JOB_NAME','j.JOB_TYPE','j.DESCRIPTION','j.AS_APPLIC','j.AS_GROUP',
                                    's.STATUS','s.LAST_START','s.LAST_END','s.EXIT_CODE',
                                    't.LINEAGE','t.DEPTH'))
                .$sql->From(array('UJO_JOB j'))
                .$sql->LeftJoin('UJO_JOB_STATUS s',array('j.JOID','s.JOID'))
                .$sql->LeftJoin('UJO_JOB_TREE t',array('j.JOID','t.JOID'))
                .$sql->Where(
                        array(  'j.IS_ACTIVE' => 1, 
                                '{job_name}' => 'j.JOB_NAME', 
                                '{start_timestamp}'=> 's.LAST_START'))                
                .$sql->OrderBy(array('s.STATUS_TIME desc'));
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');

        $res = $data->sql->query($qry);
        $autosys = $this->container->get('arii_ats.autosys');
        while ($line = $data->sql->get_next($res))
        {            
            if ($only_warning and ($line['STATUS']==4)) continue;
            $status = $autosys->Status($line['STATUS']);            
            $joid = $line['JOID'];
            $Job[$joid] = $line;            
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        
        $autosys = $this->container->get('arii_ats.autosys');
        foreach($Job as $k=>$j) {
            $status = $autosys->Status($j['STATUS']);
            list($bgcolor,$color) = $autosys->ColorStatus($status);
            $list .= '<row id="'.$k.'" style="background-color: '.$bgcolor.'">';
            $box = $j['BOX_JOID'];
            
            if ($box > 0) {
                if (isset($j['LINEAGE'])) {                    
                    $Folder = explode('/',$j['LINEAGE']);
                    $folder = '';
                    array_shift($Folder);
                    array_pop($Folder);
                    foreach ($Folder as $f) {
                        if (isset($Job[$f]['JOB_NAME']))
                            $folder .= '/'.$Job[$f]['JOB_NAME'];
                        else 
                            $folder .= '/['.$f.']';
                    }
                }
                else
                    $folder = "[$box]";
            }
            else
                $folder = '';
/*
            while (isset($Job[$box]) and ($Job[$box]['BOX_JOID']>0)) {
               $box = $Job[$box]['BOX_JOID'];
               $folder = $Job[$box]['JOB_NAME'].'/'.$folder;
            }
*/            
            if ($folder != '')
                $list .= '<cell>'.$folder.'</cell>';
            else 
                $list .= '<cell/>';
            
            $list .= '<cell>'.$j['JOB_NAME'].'</cell>';               
            $list .= '<cell>'.$status.'</cell>';               
            $list .= '<cell>'.$autosys->JobType($j['JOB_TYPE']).'</cell>';               
                
            $date = $this->container->get('arii_core.date');
            foreach (array('LAST_START','LAST_END') as $f ) {
                $list .= '<cell>'.$date->Time2Local($j[$f],'VA1',true).'</cell>';               
            }
            if ($j['LAST_END']>0)
                $list .= '<cell>'.($j['LAST_END']-$j['LAST_START']).'</cell>';               
            else
                $list .= '<cell/>';  
            
            if ($j['EXIT_CODE']!='-656')
                $list .= '<cell>'.$j['EXIT_CODE'].'</cell>';               
            else 
                $list .= '<cell/>';  
            $list .= '<cell>'.$j['AS_APPLIC'].'</cell>';               
            $list .= '<cell>'.$j['AS_GROUP'].'</cell>';               
            $list .= '</row>';
        }
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;        
    }

    public function pieAction($only_warning=0) {
        $request = Request::createFromGlobals();
        if ($request->query->get( 'only_warning' ))
            $only_warning = $request->query->get( 'only_warning' );
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        // Jobs
        $Fields = array( '{job_name}'   => 'JOB_NAME' );
        
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('STATUS','count(JOID) as NB'))
                .$sql->From(array('UJO_JOBST'))
                .$sql->Where(array('{job_name}' => 'JOB_NAME', '{start_timestamp}'=> 'LAST_START'))                
                .$sql->GroupBy(array('STATUS'));

        $res = $data->sql->query($qry);
        $autosys = $this->container->get('arii_ats.autosys');
        while ($line = $data->sql->get_next($res))
        {                        
            $status = $autosys->Status($line['STATUS']);
            if (($only_warning==1) and ($line['STATUS']==4))
                $Status[$status] = 0;
            else 
                $Status[$status] = $line['NB'];
        }
        $pie = '<data>';
        foreach (array('SUCCESS','FAILURE','TERMINATED','RUNNING','INACTIVE','ACTIVATED','WAIT_REPLY','JOB_ON_ICE','JOB_ON_HOLD','JOB_ON_NOEXEC') as $s) {
            list($bgcolor,$color) = $autosys->ColorStatus($s);
            if (isset($Status[$s]))
                $pie .= '<item id="'.$s.'"><STATUS>'.$s.'</STATUS><JOBS>'.$Status[$s].'</JOBS><COLOR>'.$bgcolor.'</COLOR></item>';
        }
        $pie .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $pie );
        return $response;
    }

    public function treeAction() {
        $sql = $this->container->get('arii_core.sql');                  
        $qry = $sql->Select(array('JOID','BOX_JOID','JOB_NAME','DESCRIPTION'))
                .$sql->From(array('UJO_JOBST'))
                .$sql->Where(array( 
                    '{start_timestamp}' => 'LAST_START', 
                    '{job_name}' => 'JOB_NAME' ))
                .$sql->OrderBy(array('JOB_NAME'));

        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('tree');
//        $data->event->attach("beforeRender",array($this,"form_render"));
        $data->render_sql($qry,'JOID','JOB_NAME','DESCRIPTION','BOX_JOID');
    }
    
    public function barchartAction($tag='application',$only_warning=0) {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');

        $request = Request::createFromGlobals();
        if ($request->query->get( 'tag' )) 
            $tag = $request->query->get( 'tag' );
        if ($request->query->get( 'only_warning' ))
            $only_warning = $request->query->get( 'only_warning' );

        $Fields = array( '{job_name}'   => 'JOB_NAME' );
        $sql = $this->container->get('arii_core.sql');
        if ($tag=='GROUP')
            $field = 'AS_GROUP';
        else
            $field = 'AS_APPLIC';
        
        $qry = $sql->Select(array('j.'.$field.' as DOMAIN','s.STATUS','count(j.JOID) as NB'))
                .$sql->From(array('UJO_JOB j'))
                .$sql->LeftJoin('UJO_JOB_STATUS s',array('j.JOID','s.JOID'))
                .$sql->Where(array(
                    '{job_name}' => 'j.JOB_NAME', 
                    '{start_timestamp}'=> 's.LAST_START',
                    'j.IS_ACTIVE'=>1 ))                
                .$sql->GroupBy(array('j.'.$field,'s.STATUS'));

        $res = $data->sql->query($qry);
        $autosys = $this->container->get('arii_ats.autosys');
        while ($line = $data->sql->get_next($res))
        {
            $domain = $line['DOMAIN'];
            if ($domain == '') continue;
            
            if ($domain == '')
                $domain = "UNKNOWN";
            $id = $domain.'/'.$autosys->Status($line['STATUS']);
            $Domain[$domain] = 1;
            if(($only_warning==1) and ($line['STATUS']==4)) {
                $Status[$id] = 0;            
            }
            else {
                $Status[$id] = $line['NB'];            
            }
        }
        $bar = '<data>';
        if (!empty($Domain)) {
            ksort($Domain);
            foreach (array_keys($Domain) as $dom) {
                $bar .= '<item id="'.$dom.'"><domain>'.$dom.'</domain>';
                foreach (array('SUCCESS','FAILURE','TERMINATED','RUNNING','INACTIVE','ACTIVATED','JOB_ON_ICE','JOB_ON_HOLD') as $s) {
                    if (isset($Status["$dom/$s"]))
                        $bar .= "<$s>".$Status["$dom/$s"]."</$s>";
                    else 
                        $bar .= "<$s>0</$s>";
                }
                $bar .= '</item>';
            }
        }
        $bar .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $bar );
        return $response;
    }

}