<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;

class SidebarController extends Controller
{
    public function todoAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('\'Setback orders\' as type','fo.id','fo.name as target','fo.suspended as source'))
                .$sql->From(array('FOCUS_ORDERS fo'))
                ." where fo.setback_count>0"
                .' union '
                .$sql->Select(array('\'Suspended orders\' as type','fo.id','fo.name as target','fo.suspended as source'))
                .$sql->From(array('FOCUS_ORDERS fo'))
                .$sql->Where(array( 'fo.suspended' => 1 ))
                .' union '
                .$sql->Select(array('\'Stopped jobs\' as type','fj.id','fj.name as target','fj.state as source'))
                .$sql->From(array('FOCUS_JOBS fj'))
                .$sql->Where(array( 'fj.state' => 'stopped' ))
                .' union '
                .$sql->Select(array('\'Waiting for process\' as type','fj.id','fj.name as target','fj.process_class_name as source'))
                .$sql->From(array('FOCUS_JOBS fj'))
                .$sql->Where(array( 'fj.waiting_for_process' => 1 ));
      
        $res = $data->sql->query( $qry );
        $Todo = $Check = array();
        $nb=0;
        while ($line = $data->sql->get_next($res)) {
            $type = $line['type'];
            // par défaut 1 item par type
            $item = $type;
            // déclinaisons
            if ($type=='Waiting for process') {
                $item = $line['source'];
                if ($line['source']=='') {
                    $type = 'Process class missing';
                }
                else {
                    $type = 'process_class_waiting';
                }
            }
            $Check[$item]['type'] = $type;
            if (!isset($Check[$item]['target'])) $Check[$item]['target'] = array(); 
            array_push($Check[$item]['target'], basename($line['target']));
            if (!isset($Check[$item]['source'])) $Check[$item]['source'] = array(); 
            array_push($Check[$item]['source'], basename($line['source']));
            $nb++;
        }

        if ($nb==0)
            return new Response('');
        foreach (array_keys($Check) as $item) {
            if ($Check[$item]['type'] == 'process_class_waiting') {
                $Todo[$item]['icon'] = 'process_class_waiting';
                $Todo[$item]['title'] = $this->get('translator')->trans('Wait for').' '.implode(', ',$Check[$item]['source']);
                $Todo[$item]['message'] = implode(', ',$Check[$item]['target']);                
            }
            else {
                $Todo[$item]['icon'] = $Check[$item]['type'];
                $Todo[$item]['title'] = $this->get('translator')->trans($Check[$item]['type']);
                $Todo[$item]['message'] = implode(', ',$Check[$item]['target']);                                
            }
        }
        
        return $this->render('AriiJOCBundle:Sidebar:todo.html.twig', array('Todo' => $Todo ) );
    }
    
    public function quickinfoAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('STATE_TEXT'))
                .$sql->From(array('FOCUS_ORDERS'))
                .' where STATE_TEXT like "%"';
        $res = $data->sql->query( $qry );
        $Todo = array();
        $nb=0;
        while ($line = $data->sql->get_next($res)) {
            $New['type'] ='prompt';
            $New['title'] =  utf8_encode ( substr($line['STATE_TEXT'],8) );
            $Msg = array();
            if ($line['TITLE'] != '') 
                array_push($Msg,$line['TITLE']);
            array_push($Msg,'['.$line['ID'].' -> '.$line['JOB_CHAIN'].'('.$line['STATE'].')]');
            $New['message'] = implode( '<br/>', $Msg );
            $Actions['Accept'] = 'javascript:alert("Accept" );';
            $Actions['Cancel'] = 'javascript:alert("Cancel" );';
            $New['actions'] = $Actions;
            array_push($Todo, $New);
            $nb++;
        }       
/*        if ($nb==0);
            return new Response("TEST");
*/        return $this->render('AriiJOCBundle:Sidebar:quickinfo.html.twig', array('Todo' => $Todo ) );
    }

    public function calendarAction() 
    {
        $session = $this->getRequest()->getSession();
        $request = Request::createFromGlobals();
        
        // Date courante
        $info = localtime(time(), true);
        $dc = $info['tm_mday'];        
        $datec = sprintf("%04d-%02d",$info['tm_year']+1900,$info['tm_mon']+1);
        $heurec = sprintf("%02d:%02d:%02d",$info['tm_hour'],$info['tm_min'],$info['tm_sec']);
        
        $time = $request->query->get( 'ref_date' );
        if ($time == "") {
            $time = $session->get('ref_date' );
        }

        // Date reference Get ou Session ou Date actuelle
        if ($time=="") {    
            $time = time();
            $info = localtime($time, true);
            $heure = sprintf("%02d:%02d:%02d",$info['tm_hour'],$info['tm_min'],$info['tm_sec']);
            $y = $info['tm_year']+1900;
            $m = $info['tm_mon']+1;
            $d = $info['tm_mday'];
        }
        else {    
            $y = substr($time,0,4);
            $m = substr($time,5,2);
            $d = substr($time,8,2);
        /*  $h = substr($time,11,2);
            $mi = substr($time,14,2);
            $s = substr($time,17,2);
       */
            $heure = substr($time,11,8);
        }
      
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');

        $qry = 'select day(start_time) as jour,count(id) as nb,error from SCHEDULER_HISTORY where month(start_time)='.$m.' and not(isnull(end_time)) group by day(start_time),error';
        $res = $data->sql->query( $qry );
        $max = 0;
        while ($line = $data->sql->get_next($res)) {
            $jour = $line['jour']; 
            $Max[$jour]=0;
            if ($line['error']>0) {
                $Error[$jour] = $line['nb'];
                $Max[$jour] += $line['nb'];
            }
            else {
                $Success[$jour] = $line['nb'];
                $Max[$jour] += $line['nb'];
            }
            if ($Max[$jour]>$max) {
                $max = $Max[$jour];
            } 
        }

        $Cal['heure'] = $heurec;
        
        // Precedent
        $mp = $m - 1;
        if ($mp<1) {
            $yp = $y - 1;
        }
        else {
            $yp = $y;
        }
        $Cal['precedent'] = $_SERVER['PHP_SELF'].'?ref_date='.sprintf("%04d-%02d-%02d ",$yp,$mp,$d).$heurec;

        // 1er jour du mois
        $Cal['mois'] = 'str_month.'.($m*1);
        $Cal['annee'] = $y;
        $date = sprintf("%04d-%02d",$y,$m );

        $first = mktime(0,0,0,$m,1,$y);
        // dernier jour du mois
        if ($m==12) {
            $m=1;
            $y++;
        }
        else {
            $m++;
        }
        $last = mktime(0,0,0,$m,1,$y);
        // Jour de la semaine de ce mois
        $info_first = localtime($first, true);
        $jf = $info_first['tm_wday'];
        // on doit avoir 35 jours !
        // on commence la semaine au lundi
        // $jf = ($jf+1) % 7;
        for($i=0;$i<35;$i++) {
            $D[$i] = '<span></span>';
        }
        // Nombre de jours
        $nb = ($last - $first)/86400;
        // Si le jour est superieur, on se recale au mois
        if ($d>$nb) $d=$nb;
        
        for($i=1;$i<=$nb;$i++) {
            $j = $jf+$i-2;
            $D[$j] = '<a href="'.$_SERVER['PHP_SELF'].'?ref_date='.$date.'-'.substr("0".$i,-2).' '.$heurec.'"';
            if (($date==$datec) and ($i==$dc)) 
                $D[$j] .= ' class="today"';
            elseif ($i==$d)
                $D[$j] .= ' class="event"  style="border: 2px solid yellow;"';
            else {
                // couleur par erreurs
                $nb_jobs = 0;
                if (isset($Success[$i])) {
                    $nb_jobs = $Success[$i];
                    $bg = '';
                    if (isset($Error[$i])) {
                        $nb_jobs += $Error[$i];
                        $percent = round($Error[$i]*255/$nb_jobs);
                        $red = dechex($percent);
                        $green = dechex(255-$percent);
                        $bg = 'background-color: #'.substr('0'.$red,-2).substr('0'.$green,-2).'00;';
                    }
                    $volume=round($nb_jobs*3/$max);                   
                    $D[$j] .= ' style="border: '.$volume.'px solid #AAA;'.$bg.'"';
                }
            }
            $D[$j] .= '>'.$i.'</a>';

        }
        $Cal['jours'] = $D;
        $Cal['suivant'] = $_SERVER['PHP_SELF'].'?ref_date='.sprintf("%04d-%02d-%02d ",$y,$m,$d).$heurec;
        
        $ref_date = $date.'-'.substr("0".$d,-2).' '.$heurec;
        $session->set( 'ref_date', $ref_date );
        
        // Passe et futur
        $Cal['ref_past'] = $session->get('ref_past' );
        if ($Cal['ref_past']=="") 
            $Cal['ref_past'] = 4;
        $Cal['ref_future'] = $session->get('ref_future' );
        if ($Cal['ref_future']=="") 
            $Cal['ref_future'] = 2;
        
      return $this->render('AriiJOCBundle:Sidebar:calendar.html.twig', $Cal );
    }

}
