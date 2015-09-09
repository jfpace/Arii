<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class HistoryController extends Controller
{
    protected $images;
    protected $TZLocal;
    protected $TZSpooler;
    protected $TZOffset;
    protected $CurrentDate;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
          
          $this->CurrentDate = date('Y-m-d');
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
        
        return $this->render('AriiJOCBundle:History:charts.html.twig', array('refresh' => $refresh, 'Timeline' => $Timeline ) );
    }

    public function listAction()
    {
        return $this->render('AriiJOCBundle:History:list.html.twig' );
    }

    public function timelineAction()
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
        $step = ($future-$past)*3600*2.5; // heure * 60 minutes / 24 fuseaux
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
        
        return $this->render('AriiJOCBundle:History:timeline.html.twig', array('refresh' => $refresh, 'Timeline' => $Timeline ) );
    }

    public function pieAction()
    {
       
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
       
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            'spooler'    => 'SPOOLER_ID',
            'start_time' => 'START_TIME',
            'end_time'   => 'END_TIME' );

$qry = 'SELECT count(ID) as NB,left(START_TIME,13) as START_TIME,END_TIME as END_TIME,ERROR
 FROM SCHEDULER_HISTORY
 where '.$sql->History($Fields).'
 GROUP BY left(START_TIME,13),left(END_TIME,13),ERROR';

    $res = $data->sql->query( $qry );
        $running = $success = $failure = 0;
        while ($line = $data->sql->get_next($res)) {
            $nb = $line['NB']; 
            if ($line['END_TIME'] == '') {
                $running+=$nb;
            }
            elseif ($line['ERROR']==0) {
                $success+=$nb;
            }
            else {
                $failure+=$nb;
            }
        }
        $pie = '<data>';
        $pie .= '<item id="1"><STATUS>SUCCESS</STATUS><JOBS>'.$success.'</JOBS><COLOR>#749400</COLOR></item>';
        $pie .= '<item id="2"><STATUS>FAILURE</STATUS><JOBS>'.$failure.'</JOBS><COLOR>red</COLOR></item>';
        $pie .= '<item id="3"><STATUS>RUNNING</STATUS><JOBS>'.$running.'</JOBS><COLOR>orange</COLOR></item>';
        $pie .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $pie );
        return $response;
    }

    public function timeline_xmlAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('scheduler');

       $session =  $this->container->get('arii_core.session'); 
        $this->ref_date  =  $session->getRefDate();

//        $options = $dhtmlx->Connector('options');

        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            'spooler'    => 'sh.SPOOLER_ID',
            'job_name'   => 'sh.JOB_NAME',
            'error'      => 'sh.ERROR',
            'start_time' => 'sh.START_TIME',
            'end_time'   => 'sh.END_TIME' );

  /*        $qry = 'SELECT distinct sh.SPOOLER_ID as label, sh.SPOOLER_ID as value
                  FROM SCHEDULER_HISTORY sh
                  where not(sh.JOB_NAME="(Spooler)") and '.$sql->History($Fields).' order by sh.SPOOLER_ID';  
          $options->render_sql($qry,"section_id","value,label");
 */          
          $qry = 'SELECT sh.ID, sh.SPOOLER_ID as section_id, sh.JOB_NAME, sh.START_TIME, sh.END_TIME, sh.ERROR, sh.EXIT_CODE, sh.CAUSE, sh.PID, "#8DA33C" as color  
                  FROM SCHEDULER_HISTORY sh
                  where not(sh.JOB_NAME="(Spooler)") and '.$sql->History($Fields).' order by sh.SPOOLER_ID, sh.JOB_NAME,sh.START_TIME';  

//          $data->set_options("section_id", $options );
          $data->event->attach("beforeRender", array( $this, "color_rows") );
          $data->render_sql($qry,"ID","START_TIME,END_TIME,JOB_NAME,color,section_id");
    }
    
    function color_rows($row){
        if ($row->get_value('END_TIME')=='') {
            $row->set_value("color", 'orange');
            $row->set_value("END_TIME", $this->ref_date );
        }
        elseif ($row->get_value('ERROR')>0) {
            $row->set_value("color", 'red');
        }
    }    

    public function barAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            'spooler'    => 'sh1.SPOOLER_ID',
            'start_time' => 'sh1.START_TIME',
            'end_time'   => 'sh1.END_TIME' );

$qry = 'SELECT sh1.ID,sh1.START_TIME,sh1.END_TIME,sh1.ERROR
 FROM SCHEDULER_HISTORY sh1
 where '.$sql->History($Fields).' 
 order by sh1.END_TIME';

        $res = $data->sql->query( $qry );
        // Par jour 
        
        while ($line = $data->sql->get_next($res)) {
            # On recupere les heures
            $day = substr($line['START_TIME'],8,5);
            $Days[$day]=1;
            if ($line['END_TIME']='') {
                if (isset($HR[$day])) 
                    $HR[$day]++;
                else $HR[$day]=1;
            }
            else {
                if ($line['ERROR']==0) {
                    if (isset($HS[$day])) 
                        $HS[$day]++;
                    else $HS[$day]=1;
                }
                else {
                    if (isset($HF[$day])) 
                        $HF[$day]++;
                    else $HF[$day]=1;
                }
            }
        }
        $bar = "<?xml version='1.0' encoding='utf-8' ?>";
        $bar .= '<data>';
        if (isset($Days)) {
            foreach($Days as $i=>$v) {
                if (!isset($HS[$i])) $HS[$i]=0;
                if (!isset($HF[$i])) $HF[$i]=0;
                if (!isset($HR[$i])) $HR[$i]=0;
                $bar .= '<item id="'.$i.'"><HOUR>'.substr($i,-2).'</HOUR><SUCCESS>'.$HS[$i].'</SUCCESS><FAILURE>'.$HF[$i].'</FAILURE><RUNNING>'.$HR[$i].'</RUNNING></item>';
            }
        }
        $bar .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $bar );
        return $response;
    }

/******************************************************************/ 
    public function list_xmlAction()
    {
        $request = Request::createFromGlobals();
        $checked_id = $request->get('checked_rows');
        $checked_id = "*".$checked_id;
		
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        $session = $this->container->get('arii_core.session');
        $sql = $this->container->get('arii_core.sql');
        $Status = $session->get('status');
        
        $Site =  $session->getSite();
        $this->TZLocal = $Site['timezone'];
        $target_offset = $this->getOffset($this->TZLocal);
        foreach ($session->getSpoolers() as $k=>$v) {
            $s = $v['name'];
            $t = $v['timezone'];
            $this->TZSpooler[$s] = $t;
            $this->TZOffset[$s] = $target_offset - $this->getOffset($t);
        }

        $key_files = array();
         $Info = array();
/* On stocke les états */
        $Fields = array (
            'spooler'    => 'sh.SPOOLER_ID',
            'job_name'   => 'sh.JOB_NAME' );
     
/*
             $qry = 'SELECT sh.SPOOLER_ID, sh.PATH as JOB_NAME, sh.STOPPED, sh.NEXT_START_TIME 
            FROM SCHEDULER_JOBS sh
            where '.$sql->History($Fields).'
            and (sh.STOPPED=1 or not(isnull(sh.NEXT_START_TIME)))';
 */
             $qry = 'SELECT sh.SPOOLER_ID, sh.PATH as JOB_NAME, sh.STOPPED, sh.NEXT_START_TIME 
            FROM SCHEDULER_JOBS sh
            where '.$sql->History($Fields).'
            or (sh.STOPPED=1 or not(isnull(sh.NEXT_START_TIME)))';
        
        $res = $data->sql->query( $qry ); 
        while ($line = $data->sql->get_next($res)) {
             $jn = $line['SPOOLER_ID'].'/'.$line['JOB_NAME'];
             if ($line['STOPPED']=='1' ) {
                 $Stopped[$jn] = true;
                 $key_files[$jn] = $jn;
             }
             if ($line['NEXT_START_TIME']!='' )
                 $Next[$jn] = $line['NEXT_START_TIME'];
        }
        
        $StopID = array();
        if (!empty($Stopped)) {
            $qry = 'select max(ID) as ID from SCHEDULER_HISTORY where concat(SPOOLER_ID,"/",JOB_NAME) in ( "'.implode('","',array_keys($Stopped)).'" ) group by SPOOLER_ID,JOB_NAME';
            $res = $data->sql->query( $qry ); 
            while ($line = $data->sql->get_next($res)) {
                array_push($StopID,$line['ID']);
            }
        }

/* On prend l'historique */
        $Fields = array (
           'spooler'    => 'sh.SPOOLER_ID', 
            'job_name'   => 'sh.JOB_NAME',
            'error'      => 'sh.ERROR',
            'start_time' => 'sh.START_TIME',
            'end_time'   => 'sh.END_TIME' );

        $qry = '';
          $qry .= 'SELECT sh.ID, sh.SPOOLER_ID, sh.JOB_NAME, sh.START_TIME, sh.END_TIME, sh.ERROR, sh.EXIT_CODE, sh.CAUSE, sh.PID 
                  FROM SCHEDULER_HISTORY sh
                  where not(sh.JOB_NAME="(Spooler)") and '.$sql->History($Fields).' or sh.ID in ('.implode(',',$StopID).')';
          $qry .= ' order by sh.SPOOLER_ID, sh.JOB_NAME,sh.START_TIME';  

/*
 $qry = 'SELECT sh.ID, sh.SPOOLER_ID, sh.JOB_NAME, sh.START_TIME, sh.END_TIME, sh.ERROR, sh.EXIT_CODE, sh.CAUSE, sh.PID, sj.STOPPED, sj.NEXT_START_TIME
 FROM SCHEDULER_JOBS sj
 left join SCHEDULER_HISTORY sh
 on sj.SPOOLER_ID=sh.SPOOLER_ID and sj.PATH=sh.JOB_NAME
 where '.$sql->History($Fields).' order by sh.ID desc ';
*/
        $res = $data->sql->query( $qry );
        $nb=0;
        while ($line = $data->sql->get_next($res)) {
            $nb++;

                $jn = $line['SPOOLER_ID'].'/'.$line['JOB_NAME'];     
                
                // Cas particulier pour les RUNNING
                if ($line['END_TIME']=='') {
                    // le jn est traité 
                    $Info[$jn] = '#'.$line['CAUSE'];
                    // le nouveau jn prend en compte les instances
                    $jn = $line['SPOOLER_ID'].'/'.$line['JOB_NAME'].'/'.$line['PID'];                
                    
                }
                // Doublons ?
                //if (isset($Info[$jn]))
                //    continue;

                $status = 'SUCCESS';
                if (isset($Stopped[$jn])) {
                    $status = 'STOPPED';
                }
                elseif ($line['END_TIME'] == '') {
                    $status = 'RUNNING';
                }
                elseif ($line['ERROR']>0) {
                    $status = 'FAILURE';
                }
               
                
                $next = '';
                if (isset($Next[$jn]))
                    $next = $Next[$jn];
                if(!isset($Status))
                {
                $Info[$jn]= $line['ID'].'|'.$line['START_TIME'].'|'.$line['END_TIME'].'|'.$line['EXIT_CODE'].'|'.$line['ERROR'].'|'.$line['CAUSE'].'|'.$next.'|'.$status.'|'.$line['SPOOLER_ID'];
                $key_files[$jn] = $jn;
                } elseif(array_sum($Status)==0){
                $Info[$jn]= $line['ID'].'|'.$line['START_TIME'].'|'.$line['END_TIME'].'|'.$line['EXIT_CODE'].'|'.$line['ERROR'].'|'.$line['CAUSE'].'|'.$next.'|'.$status.'|'.$line['SPOOLER_ID'];
                $key_files[$jn] = $jn;
                } else {
                    $keys = array();
                    foreach ($Status as $k => $v)
                    {
                        if($v == 1)
                            array_push ($keys, $k);
                    }
                    $filter = ' '.implode(' ', $keys);
                    if(strpos($filter, $status)>0)
                    {
                       $Info[$jn]= $line['ID'].'|'.$line['START_TIME'].'|'.$line['END_TIME'].'|'.$line['EXIT_CODE'].'|'.$line['ERROR'].'|'.$line['CAUSE'].'|'.$next.'|'.$status.'|'.$line['SPOOLER_ID'];
                       $key_files[$jn] = $jn;
                    } 
                }
        }
        // print "<pre>";  print_r($tree); print "</pre>"; exit();
        
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
        /*implode(' ',$status)
        SUCCESS FAILURE
        strpos(SUCCESS )*/
        $list .= $this->History2XML( $tree, '', $Info ,$checked_id);
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }
    
    function History2XML( $leaf, $id = '', $Info,$checked_id ) {
            $color = array (
                'SUCCESS' => '#ccebc5',
                'RUNNING' => '#ffffcc',
                'FAILURE' => '#fbb4ae',
                'STOPPED' => '#FF0000'
                );
            $return = '';
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $Ids = explode('/',$k);
                            $here = array_pop($Ids);
                            $i  = substr("$id/$k",1);
                            # On ne prend que l'historique
                            if (isset($Info[$i])) {
                                $cell = '';
                                // cas du running
                                if (substr($Info[$i],0,1)=='#') {
                                    list( $cause ) = explode('|',substr($Info[$i],1));
                                    $return .= '<row id="'.$i.'" style="background-color: '.$color['RUNNING'].';"  open="1">';
                                    $job_type = 'standalone_job';
                                    if ($cause == 'order') {
                                        $job_type = 'ordered_job';
                                    }
                                    if (strpos($checked_id,$dbid)>0)
                                    {
                                        $checked = 1;
                                    } else 
                                    {
                                        $checked = 0;
                                    }
                                    //if ($here==0) {
                                    $cell .= '<cell image="'.$job_type.'.png">'.$here.'</cell>';
                                    $cell .= '<cell>'.$checked.'</cell>';
                                    $cell .= '<cell>RUNNING</cell>';
                                    $cell .= '<cell><![CDATA[<img src="'.$this->images.'/running.png"/>]]></cell>';
                                    $return .= $cell;
                                }
                                else {
                                    list($dbid, $start_time, $end_time, $exit_code, $error, $cause, $next_start_time, $status, $spooler ) = explode('|',$Info[$i]);
                                    $return .= '<row id="'.$dbid.'" style="background-color: '.$color[$status].';"  open="1">';
                                    $job_type = 'standalone_job';
                                    if ($cause == 'order') {
                                        $job_type = 'ordered_job';
                                    }
                                    if (strpos($checked_id,$dbid)>0)
                                    {
                                        $checked = 1;
                                    } else 
                                    {
                                        $checked = 0;
                                    }
                                    //if ($here==0) {
                                    $cell .= '<cell image="'.$job_type.'.png">'.$here.'</cell>';
                                    $cell .= '<cell>'.$checked.'</cell>';
                                    //}
                                    /*else {
                                        $cell .= '<cell image="blank.gif">'.$job_name.'</cell>';
                                    }
                                     * 
                                     */
                                    # Infos cachées
                                    if ($status != 'RUNNING') {
                                        $cell .= '<userdata name="jobtype">instance</userdata>';
                                        $cell .= '<cell>'.$status.'</cell>';
                                        $cell .= '<cell><![CDATA[<img src="'.$this->images.'/'.strtolower($status).'.png"/>]]></cell>';
                                    }
                                    else { 
                                        $cell .= '<userdata name="jobtype">'.$job_type.'</userdata>';
                                        $cell .= '<cell></cell>';
                                        $cell .= '<cell></cell>';                                        
                                    }
                                    list($start,$end,$next,$duration) = $this->getLocaltimes( $start_time, $end_time, substr($next_start_time,0,16), $spooler ); 
                                    $cell .= '<cell>'.$start.'</cell>';
                                    $cell .= '<cell>'.$end.'</cell>';
                                    $cell .= '<cell>'.$duration.'</cell>';
                                    $cell .= '<cell>'.$exit_code.'</cell>';
                                    $cell .= '<cell>'.$next.'</cell>';
                                    $cell .= '<cell><![CDATA[<img src="'.$this->images.'/'.strtolower($cause).'.png"/>]]></cell>';
                                    $return .= $cell;
                                }
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
                           $return .= $this->History2XML( $leaf[$k], $id.'/'.$k, $Info ,$checked_id);
                           $return .= '</row>';
                    }
            }
            else {
    /*
                    $Inf = explode('|',$Info[$leaf]);
                    if ($Inf[0] == 'ORDER') {
                            print '<cell><![CDATA[<img src="'.$Images.'/lightning.png"/>]]>ORDER</cell>';
                    }
    */
            }
            return $return;
    }

/******************************************************************/
private function getOffset( $tz ) {
    if ($tz=='') {
        // ATTENTION !!!!!!!!!!!!!!!!!!!!!!!!!!!
        $tz = 'GMT';
    }
    if ($tz == 'GMT') {
        $offset = 0;
    }
    else {
        $offset =  timezone_offset_get( new \DateTimeZone( $tz ), new \DateTime() );
    }
    // print "(TZ $tz ".($offset/3600).")";
    return $offset;
}

    private function getLocaltimes( $start, $end, $next, $spooler ) {
/*
        print "$spooler OFFSET : ".($this->TZOffset[$spooler]/3600);
        print "<br/>";
        $tz = date_default_timezone_get();
        print strtotime($start);
        date_default_timezone_set('UTC');
        print "<br/>UTC: ";
        print strtotime($start);
        $tz = date_default_timezone_get();
        print $tz;
        exit();
*/
        if (isset($this->TZOffset[$spooler])) 
            $offset = $this->TZOffset[$spooler];
        else 
            $offset = 0;
        $start_time = strtotime($start);
        $end_time = strtotime($end);
        if ($next != '') {
            $next = date( 'Y-m-d H:i:s', strtotime($next) + $offset);
        }
        $duration = $end_time - $start_time;
        return array( $this->ShortDate( date( 'Y-m-d H:i:s', $start_time + $offset) ), 
                      $this->ShortDate( date( 'Y-m-d H:i:s', $end_time  + $offset ) ), 
                      $this->ShortDate( $next ) , $duration );
    }
            
    private function ShortDate($date) {
        if (substr($date,0,10)==$this->CurrentDate)
                return substr($date,11);
        return $date;
    }
    private function FormatTime($d) {
       $str = '';
       if ($d>86400) {
           $n = (int) ($d/86400);
           $d %= 86400;
           $str .= ' '.$n.'d'; 
           return $str;
       }
       if ($d>3600) {
           $n = (int) ($d/3600);
           $d %= 3600;
           $str .= ' '.$n.'h';           
           return $str;
       }
       if ($d>60) {
           $n = (int) ($d/60);
           $d %= 60;
           $str .= ' '.$n.'m';           
       }
       if ($d>0) 
        $str .= ' '.$d.'s';
       return $str;        
    }
    
    private function Duration($start,$end = '' ) {
       if ($end == '') {
           $end = time();
       }
       $d = $end - $start;
       return $this->FormatTime($d);
    }
/*****************************************/
    // change xml to array
    private function xml2array($contents, $get_attributes=1, $priority = 'tag') {
        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if(!$xml_values) return;//Hmm...

        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; //Refference

        //Go through the tags.
        $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = array();
            $attributes_data = array();

            if(isset($value)) {
                if($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too.
            if(isset($attributes) and $get_attributes) {
                foreach($attributes as $attr => $val) {
                    if($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }

            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;
                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag.'_'.$level] = 1;

                    $current = &$current[$tag];

                } else { //There was another element with the same tag name

                    if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag.'_'.$level] = 2;

                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }

                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                    $current = &$current[$tag][$last_item_index];
                }

            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

                } else { //If taken, put all things inside a list(array)
                    if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

                        if($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;

                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if($priority == 'tag' and $get_attributes) {
                            if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well

                                $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                                unset($current[$tag.'_attr']);
                            }

                            if($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                    }
                }

            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }

        return($xml_array);
    } 
    
    // http://kevin.vanzonneveld.net/techblog/article/convert_anything_to_tree_structures_in_php/
    function explodeTree($array, $delimiter = '_', $baseval = false)
    {
      if(!is_array($array)) return false;
      $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
      $returnArr = array();
      foreach ($array as $key => $val) {
        // Get parent parts and the current leaf
        $parts  = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);

        // Build parent structure
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
          if (!isset($parentArr[$part])) {
            $parentArr[$part] = array();
          } elseif (!is_array($parentArr[$part])) {
            if ($baseval) {
              $parentArr[$part] = array('__base_val' => $parentArr[$part]);
            } else {
              $parentArr[$part] = array();
            }
          }
          $parentArr = &$parentArr[$part];
        }

        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
          $parentArr[$leafPart] = $val;
        } elseif ($baseval && is_array($parentArr[$leafPart])) {
          $parentArr[$leafPart]['__base_val'] = $val;
        }
      }
      return $returnArr;
    }

    public function NoRecord()
    {
        $no = '<?xml version="1.0" encoding="UTF-8"?>';
        $no .= '
    <rows><head><afterInit><call command="clearAll"/></afterInit></head>
<row id="scheduler" open="1"><cell image="wa/spooler.png"><b>No record </b></cell>
</row></rows>';
        return $no;
    }

}
