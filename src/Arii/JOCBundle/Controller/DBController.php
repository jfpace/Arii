<?php
// src/Arii/JODBundle/Controller/DBController.php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DBController extends Controller
{
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
    }
    
/******************************************************************/
    public function purge_historyAction($lines=40)
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        /* Traitements à purger */
        $qry = 'select count(ID) as ID,SPOOLER_ID, JOB_NAME from SCHEDULER_HISTORY group by SPOOLER_ID,JOB_NAME having(count(ID)>'.$lines.')';
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $spooler = $line['SPOOLER_ID'];
            $job_name = $line['JOB_NAME'];
            print $line['ID'].") ".$line['JOB_NAME']." ".$line['SPOOLER_ID'].": ";
            $sel = 'select ID from SCHEDULER_HISTORY where JOB_NAME="'.$job_name.'" and SPOOLER_ID="'.$spooler.'" order by ID desc';
            $res_sel = $data->sql->query( $sel );
            $row = 0;
            while (($line2 = $data->sql->get_next($res_sel)) and ($row<$lines)) { $row++; }
            $max_id = $line2['ID'];
            print "[$max_id]<br/>";
            $del = 'delete from SCHEDULER_HISTORY where JOB_NAME="'.$job_name.'" and SPOOLER_ID="'.$spooler.'" and ID<='.$max_id;
            $res_del = $data->sql->query( $del );
        }
        exit();
    }  

    /******************************************************************/
    public function statusAction($lines=40)
    {
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        # L'urgence concerne les jobs en running
        $qry = 'select max(ss.ID) as ID from SCHEDULER_HISTORY ss
                left join SCHEDULER_STATUS sh
                on ss.ID=sh.ID
                where isnull(ss.END_TIME)
                group by sh.SPOOLER_ID,sh.JOB_NAME
                having isnull(max(ss.ID))';
        $res = $data->sql->query( $qry );
        $nb = 0;
        # par groupes de 100
        $IDs = array();
        while (($line = $data->sql->get_next($res)) and ($nb<100)) {
            array_push($IDs,$line['ID']);
            $nb++;
        }
        print "RUNNING: $nb\n";
        if ($nb>0) {
            # Insertion des nouveaux enregistrements
            $qry = 'update SCHEDULER_STATUS (SPOOLER_ID, CLUSTER_MEMBER_ID, JOB_NAME, START_TIME, END_TIME, CAUSE, STEPS, EXIT_CODE, ERROR, ERROR_CODE, ERROR_TEXT, PARAMETERS, PID) 
                select SPOOLER_ID, CLUSTER_MEMBER_ID, JOB_NAME, START_TIME, END_TIME, CAUSE, STEPS, EXIT_CODE, ERROR, ERROR_CODE, ERROR_TEXT, PARAMETERS, PID from SCHEDULER_HISTORY
                where ID in ('.implode(',',$IDs).')';
            $res2 = $data->sql->query( $qry );
        }
        
        # Premiere etape: comparaison de ce qui existe en HISTORY sans STATUS
        $qry = 'select max(sh.ID) as ID from SCHEDULER_HISTORY sh
                left join SCHEDULER_STATUS ss 
                on sh.ID=ss.ID
                group by sh.SPOOLER_ID,sh.JOB_NAME
                having isnull(max(ss.ID))';
        $res = $data->sql->query( $qry );
        $nb = 0;
        # par groupes de 100
        $IDs = array();
        while (($line = $data->sql->get_next($res)) and ($nb<100)) {
            array_push($IDs,$line['ID']);
            $nb++;
        }
        print "NEW: $nb\n";
        if ($nb>0) {
            # Insertion des nouveaux enregistrements
            $qry = 'insert into SCHEDULER_STATUS (ID, SPOOLER_ID, CLUSTER_MEMBER_ID, JOB_NAME, START_TIME, END_TIME, CAUSE, STEPS, EXIT_CODE, ERROR, ERROR_CODE, ERROR_TEXT, PARAMETERS, PID) 
                select ID, SPOOLER_ID, CLUSTER_MEMBER_ID, JOB_NAME, START_TIME, END_TIME, CAUSE, STEPS, EXIT_CODE, ERROR, ERROR_CODE, ERROR_TEXT, PARAMETERS, PID from SCHEDULER_HISTORY
                where ID in ('.implode(',',$IDs).')';
            $res = $data->sql->query( $qry );
        }
        # on cherche les IDs modifiés
        $qry = 'select max(sh.ID) as ID,max(ss.ID) as ID2 from SCHEDULER_HISTORY sh
                left join SCHEDULER_STATUS ss 
                on sh.ID=ss.ID
                group by sh.SPOOLER_ID,sh.JOB_NAME
                having max(sh.ID)<>max(ss.ID)';
        $res = $data->sql->query( $qry );
        $nb = 0;
        # par groupes de 100
        $IDs = array(); $IDs_del = array();
        while (($line = $data->sql->get_next($res)) and ($nb<100)) {
            array_push($IDs,$line['ID']);
            array_push($IDs_del,$line['ID2']);
            $nb++;
        }
        print "NEW ID: $nb\n";
        # Suppression et insertion
        if ($nb>0 ) {
            $qry = 'delete from SCHEDULER_STATUS where ID in ('.implode(',',$IDs_del).')';            
            $res3 = $data->sql->query( $qry );
            $qry = 'insert into SCHEDULER_STATUS (ID, SPOOLER_ID, CLUSTER_MEMBER_ID, JOB_NAME, START_TIME, END_TIME, CAUSE, STEPS, EXIT_CODE, ERROR, ERROR_CODE, ERROR_TEXT, PARAMETERS, PID) 
                select ID, SPOOLER_ID, CLUSTER_MEMBER_ID, JOB_NAME, START_TIME, END_TIME, CAUSE, STEPS, EXIT_CODE, ERROR, ERROR_CODE, ERROR_TEXT, PARAMETERS, PID from SCHEDULER_HISTORY
                where ID in ('.implode(',',$IDs).')';            
            $res4 = $data->sql->query( $qry );
        }
        # Suppression des status non historises
        
        exit();
    }  

/******************************************************************/
    public function reorgAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            'spooler'    => 'sh.SPOOLER_ID',
            'job_name'   => 'sh.JOB_NAME',
            'error'      => 'sh.ERROR',
            'stopped'    => 'sj.STOPPED');
/*            
$qry = 'SELECT sh.ID, sh.SPOOLER_ID, sh.JOB_NAME, sh.START_TIME, sh.END_TIME, sh.ERROR, sh.EXIT_CODE, sh.CAUSE, sh.PID, sj.STOPPED, sj.NEXT_START_TIME
 FROM SCHEDULER_HISTORY sh
 left join SCHEDULER_JOBS sj 
 on sh.SPOOLER_ID=sj.SPOOLER_ID and sh.JOB_NAME=sj.PATH
 where '.$sql->Filter($Fields).' order by sh.ID desc ';
 */
$qry = 'insert into ARII_SCHEDULER_STATUS (ID,ID_HISTORY,SPOOLER_ID,CLUSTER_MEMBER_ID,JOB_NAME,START_TIME,END_TIME) 
 SELECT max(ID) as ID,max(ID) as ID_HISTORY, sh.SPOOLER_ID,sh.CLUSTER_MEMBER_ID, sh.JOB_NAME, max(sh.START_TIME) as START_TIME, max(sh.END_TIME) as END_TIME
 FROM SCHEDULER_HISTORY sh
 group by SPOOLER_ID,CLUSTER_MEMBER_ID,JOB_NAME';
        $res = $data->sql->query( $qry );
        // $data->render_sql($qry,"ID","ID,JOB_NAME,START_TIME,END_TIME");
        exit();
    }
    
/******************************************************************/
    public function JobChainsAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');

        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            'spooler'    => 'SPOOLER_ID',
            'job_chain'  => 'JOB_CHAIN',
            'start_time' => 'START_TIME',
            'end_time'   => 'END_TIME',
            'error'      => 'ERROR' );
            
$qry = 'SELECT 
 soh.HISTORY_ID as ID,soh.SPOOLER_ID,soh.JOB_CHAIN,soh.ORDER_ID,soh.TITLE,soh.STATE,soh.START_TIME,soh.END_TIME,sosh.ERROR
 FROM SCHEDULER_ORDER_HISTORY soh 
 INNER JOIN (
 SELECT max( HISTORY_ID ) AS HISTORY_ID
 FROM SCHEDULER_ORDER_HISTORY
 WHERE '.$sql->Filter($Fields).' 
 GROUP BY SPOOLER_ID,JOB_CHAIN,ORDER_ID
 ) soh2 ON soh.HISTORY_ID = soh2.HISTORY_ID 
 left join SCHEDULER_ORDER_STEP_HISTORY sosh 
 on soh.HISTORY_ID = sosh.HISTORY_ID';

        $res = $data->sql->query( $qry );
        $nb = 0; $key_files = array();
        while ($line = $data->sql->get_next($res)) {
                # Creation des icones
                $cn = $line['SPOOLER_ID'].'/'.$line['JOB_CHAIN'];
                $Icon[$cn] = 'job_chain';

                $jn = $cn.'/'.$line['ORDER_ID'];
                $Icon[$jn] = 'order';
                
                # On prends les n derniers ordres pour un job_chain
                if (isset($Nb[$cn])) {
                    $Nb[$cn]++;
                    if ($Nb[$cn]>30) continue;
                }
                else {
                    $Nb[$cn]=0;
                }
                $nb++;
                $Info[$jn]= $line['ID'].'|'.$line['START_TIME'].'|'.$line['END_TIME'].'|'.$line['STATE'].'|'.$line['ORDER_ID'].'|'.$line['ERROR'];
                $key_files[$jn] = $jn;
        }
        $tree = $this->explodeTree($key_files, "/");
/*
        print "<pre>";
        print_r($tree);
        print "</pre>";
       exit();
*/
        header("Content-type: text/xml");
        if ($nb==0) {
            $this->NoRecord();
        }
        
        print '<?xml version="1.0" encoding="UTF-8"?>';
        print "<rows>\n";
        print '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        print $this->JobChains2XML( $tree, '', $Info, $Nb, $Icon );
        print "</rows>\n";
        exit();
        /*
        <rows> <userdata name="gud1"> userdata value 1 </userdata> <userdata name="gud2"> userdata value 2 </userdata> <row id="honda" selected="1" call="1" xmlkids="1"> <userdata name="hondaUD"> userdata value for honda </userdata> <cell image="folder.gif">Honda</cell> </row> <row id="bmw"> <cell image="folder.gif">BMW</cell> <row id="bmw1"> <userdata name="bmwUD1"> userdata value for bmw1 1 </userdata> <userdata name="bmwUD2"> userdata value for bmw1 2 </userdata> <cell image="leaf.gif">325i</cell> <cell>30,800</cell> <cell>2.5L</cell> <cell>184</cell> <cell>19</cell> <cell>27</cell> </row> <row id="bmw2"> <cell image="leaf.gif">M3 Coupe</cell> <cell>47,100</cell> <cell>3.2L</cell> <cell>333</cell> <cell>16</cell> <cell>24</cell> </row> </row> <row id="vw"> <cell image="folder.gif">Volkswagen</cell> <row id="vw1"> <cell>Colf GL 2.0</cell> <cell>15,580</cell> <cell>2.0L</cell> <cell>115</cell> <cell>24</cell> <cell>30</cell> </row> </row> <row id="mazda"> <cell image="folder.gif">Mazda</cell> <row id="mazda1"> <cell>MX-5 Miata</cell> <cell>21,868</cell> <cell>1.8L</cell> <cell>142</cell> <cell>22</cell> <cell>28</cell> </row> </row> <row id="porsche"> <cell image="folder.gif">Porsche</cell> <row id="porsche1"> <cell>Porsche 911</cell> <cell>128,200</cell> <cell>3.6L</cell> <cell>415</cell> <cell>14</cell> <cell>22</cell> <cell>4.06</cell> <cell>12.31</cell> <cell>120.63</cell> <cell>119</cell> </row> </row> </rows> 
        */

    }
    
     function JobChains2XML( $leaf, $id = '', $Info, $Nb, $Icon ) {
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $Ids = explode('/',$k);
                            $here = array_pop($Ids);
                            $i  = substr("$id/$k",1);
                            # On ne prend que l'historique
                            if (isset($Info[$i])) {
                                $cell = '';
                                list($dbid, $start_time, $end_time, $state, $order, $error ) = explode('|',$Info[$i]);
                                $status = 'SUCCESS';
                                $color = '#ccebc5';
                                if ($end_time == '') {
                                    $status = 'RUNNING';
                                    $color = '#ffffcc';
                                }
                                elseif ($error>0) {
                                    $status = 'FAILURE';
                                    $color = '#fbb4ae';                                    
                                }
/*
                                  if ($exit_code>0) {
                                    $status = 'FAILURE';
                                    $color =  '#fbb4ae';
                                }
 */
                                print '<row id="'.$dbid.'" style="background-color: '.$color.';"  open="1">';
                                $cell .= '<cell image="wa'.$this->images.'/step.png">'.$here.'</cell>';
                                $cell .= '<cell>'.$state.'</cell>';
                                $cell .= '<cell>'.$status.'</cell>';
                                $cell .= '<cell><![CDATA[<img src="'.$this->images.'/'.strtolower($status).'.png"/>]]></cell>';
                                $cell .= '<cell>'.$this->ShortDate($start_time).'</cell>';
                                $cell .= '<cell>'.$this->ShortDate($end_time).'</cell>';
                                $cell .= '<cell>'.$this->Duration(strtotime($start_time),strtotime($end_time)).'</cell>';
                                $cell .= '<cell>'.$error.'</cell>';
                                print $cell;
                            }
                            else {
                                    print '<row id="'.$i.'" open="1">';
                                    if ($id == '') {
                                        print '<cell image="'.$this->images.'/spooler.png"><![CDATA[<b> '.$here.'</b>]]></cell>';
                                    }
                                    elseif (isset($Icon[$i])) {
                                        print '<cell image="'.$this->images.'/'.$Icon[$i].'.png">'.$here.'</cell>';
                                    }
                                    else {
                                        print '<cell image="folder.gif">'.$here.'</cell>';
                                    }
                            }
                           $this->JobChains2XML( $leaf[$k], $id.'/'.$k, $Info, $Nb, $Icon );
                           print '</row>';
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
    }

/******************************************************************/
    function ShortDate($date) {
        if (substr($date,0,10)==date('Y-m-d'))
                return substr($date,11);
        return $date;
    }
    function FormatTime($d) {
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
    
    function Duration($start,$end = '' ) {
       if ($end == '') {
           $end = time();
       }
       $d = $end - $start;
       return $this->FormatTime($d);
    }

/******************************************************************/

    public function timelineAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('scheduler');

        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            'spooler'    => 'sh.SPOOLER_ID',
            'job_name'   => 'sh.JOB_NAME',
            'start_time' => 'sh.START_TIME',
            'end_time'   => 'sh.END_TIME',
            'error'      => 'sh.ERROR',
            'stopped'    => 'sj.STOPPED');
$qry = 'SELECT sh.ID, sh.SPOOLER_ID as details, 20 as section_id, sh.JOB_NAME as event_name, sh.START_TIME, sh.END_TIME
 FROM SCHEDULER_HISTORY sh
 where '.$sql->Filter($Fields).' order by sh.ID desc ';

        $data->render_sql($qry,"ID","START_TIME,END_TIME,event_name,details,section_id");
    }

    public function NoRecord()
    {
        print '<?xml version="1.0" encoding="UTF-8"?>';
        print '
    <rows><head><afterInit><call command="clearAll"/></afterInit></head>
<row id="scheduler" open="1"><cell image="wa/spooler.png"><b>No record </b></cell>
</row></rows>';
        exit();
    }
    
}

