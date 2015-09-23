<?php

namespace Arii\DSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class OrdersController extends Controller
{
    protected $images;
    protected $ColorStatus = array (
            'EXECUTED' => '#ccebc5',
            'WAITING' => '#DDD',
            'DELAYED' => '#ffffcc',
            'BLOCKED' => '#fbb4ae',
            'UNKNOWN' => '#FF0000'
        );
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
    }

    public function indexAction()
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
        if ($step == 0) $step = 1;
        $Timeline['step'] = 60;
    
        // on recalcule la date courante moins la plage de passé 
        $year = substr($ref_date, 0, 4);
        $month = substr($ref_date, 5, 2);
        $day = substr($ref_date, 8, 2);
        
        $start = substr($session->getPast(),11,2);
        $Timeline['start'] = (60/$step)*$start;
        $Timeline['js_date'] = $year.','.($month - 1).','.$day;
        
        $Timeline['start'] = 0;
        $refresh = $session->GetRefresh();
        
        // Liste des spoolers pour cette plage
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            '{spooler}'    => 'SPOOLER_ID',
            '{start_time}' => 'START_TIME' );

    $qry = $sql->Select(array('SPOOLER_ID'),'distinct') 
               .$sql->From(array('SCHEDULER_ORDER_HISTORY'))
               .$sql->Where($Fields)
               .$sql->OrderBy(array( 'SPOOLER_ID' ));

    $SPOOLERS = array();
        if ($data) {
            $res = $data->sql->query( $qry );
            while ($line = $data->sql->get_next($res)) {
                array_push( $SPOOLERS,$line['SPOOLER_ID'] ); 
            }
        }
        $Timeline['spoolers'] = $SPOOLERS;
       return $this->render('AriiDSBundle:Orders:index.html.twig', array('refresh' => $refresh, 'Timeline' => $Timeline ) );
    }

    public function icalAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            '{spooler}'    => 'SCHEDULER_ID',
            '{job_name}'   => 'JOB',
/*            '{start_time}' => 'SCHEDULE_PLANNED',
            '{end_time}'   => 'SCHEDULE_PLANNED',
*/            'SCHEDULE_EXECUTED'   => '(null)' );

        $qry = $sql->Select(array( 'ID','SCHEDULER_ID','SCHEDULER_HISTORY_ID','SCHEDULER_ORDER_HISTORY_ID','JOB','JOB_CHAIN','ORDER_ID','SCHEDULE_PLANNED','STATUS','RESULT'))
                .$sql->From(array('DAYS_SCHEDULE'))
                .$sql->Where($Fields);

        $res = $data->sql->query( $qry );
        $Events = array();
        $Orders = array();
        $Jobs = array();
        while ($line = $data->sql->get_next($res)) {
            $event['ID'] = $line['ID'].'@'.$line['SCHEDULER_ID'];
            if ($line['JOB']!='') {
                $obj = ' JOB '.$line['JOB'];
                array_push($Jobs, $sql->AndOr(array(
                    'SPOOLER_ID' => $line['SCHEDULER_ID'],
                    'JOB_NAME' => substr($line['JOB'],1)
                        ))
                    );
               $event['TYPE'] = 'J';
               $event['DB'] = $line['SCHEDULER_ID'].$line['JOB'];
            }
            else {
                $obj = ' ORDER '.$line['ORDER_ID'].' ->'.$line['JOB_CHAIN'];
                array_push($Orders, $sql->AndOr(array(
                    'SPOOLER_ID' => $line['SCHEDULER_ID'],
                    'JOB_CHAIN' => substr($line['JOB_CHAIN'],1),
                    'ORDER_ID' => $line['ORDER_ID']
                        ))
                    );
               $event['TYPE'] = 'J';
               $event['DB'] = $line['SCHEDULER_ID'].$line['JOB_CHAIN'].'/'.$line['ORDER_ID'];
            }
            $event['SUMMARY'] = '['.$line['SCHEDULER_ID'].']'.$obj;
            $event['DTSTART'] = $line['SCHEDULE_PLANNED'];
            $event['DTEND'] = $line['SCHEDULE_PLANNED'];
            array_push($Events,$event);
        }
        
        // On va chercher les temps des jobs
        $qry = $sql->Select(array('SPOOLER_ID','JOB_NAME','avg(END_TIME-START_TIME) as DURATION'))
                .$sql->From(array('SCHEDULER_HISTORY'))
                .' where error = 0 and ('
                .implode(' OR ',$Jobs)
                .')'
                .$sql->GroupBy(array('SPOOLER_ID','JOB_NAME'));

        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $id = $line['SPOOLER_ID'].'/'.$line['JOB_NAME'];
            $Duration[$id] = round($line['DURATION']);
        }

        // On va chercher les temps des jobs
        $qry = $sql->Select(array('SPOOLER_ID','JOB_CHAIN','ORDER_ID','avg(END_TIME-START_TIME) as DURATION'))
                .$sql->From(array('SCHEDULER_ORDER_HISTORY'))
                .' where ('
                .implode(' OR ',$Orders)
                .')'
                .$sql->GroupBy(array('SPOOLER_ID','JOB_CHAIN','ORDER_ID'));

        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $id = $line['SPOOLER_ID'].'/'.$line['JOB_CHAIN'].'/'.$line['ORDER_ID'];
            $Duration[$id] = round($line['DURATION']);
        }

        $ical = $this->container->get('arii_core.cal');
        // $ical->VCalendar($Events);
          $ical = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
";
        foreach ($Events as $e) {
            $id = $e['ID'];
            $db = $e['DB'];
            if (isset($Duration[$db])) {
                $duration = $Duration[$db];
                $info = "($duration".'s)';
            }
            else {
                $duration = 3600;
                $info = "";
            }
            $ical .= "BEGIN:VEVENT
UID: ".$e['ID']."
DTSTAMP:" . gmdate('Ymd').'T'. gmdate('His') . "Z
DTSTART:".$this->tzformat($e['DTSTART'])."
DTEND:".$this->tzformat($e['DTSTART'],$duration)."
SUMMARY:".$e['SUMMARY']." $info
END:VEVENT
";
        }
$ical .= "END:VCALENDAR";

// set correct content-type-header
header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename=arii.ics');
echo $ical;
exit;
        
    }
public function tzformat($date,$plus=0) {
    if ($plus>0) {
        $time = strtotime($date);
        $date = date( 'Y-m-d H:i:s', $time + $plus);
    }
    $date = str_replace(array(' ',':','-'),array('T','',''),$date);
    return $date.'Z';
}

   public function listAction()
    {
        return $this->render('AriiDSBundle:Orders:list.html.twig');
    }

   public function activitiesAction()
    {
        return $this->render('AriiDSBundle:Orders:activities.html.twig');
    }

    public function form_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiDSBundle:Orders:form_toolbar.xml.twig',array(), $response );
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiDSBundle:Orders:toolbar.xml.twig',array(), $response );
    }

    public function formAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiDSBundle:Orders:form.json.twig',array(), $response );
    }

   public function menuAction()
    {
        return $this->render('AriiDSBundle:Orders:menu.xml.twig');
    }

    public function pieAction($history_max=0,$ordered = 0,$only_warning=1) {        
        $request = Request::createFromGlobals();
        $cyclic = $request->get('cyclic');
        $only_warning = $request->get('only_warning');

        $ds = $this->container->get('arii_ds.dailyschedule');
        $Jobs = $ds->DailySchedule($cyclic, $only_warning, false);

        $States = array('WAITING','EXECUTED','DELAYED','BLOCKED');
        foreach ($States as $state) {
            $Status[$state]=0;
        }

        foreach ($Jobs as $k=>$job) {
            $status = $job['STATUS'];
            $Status[$status]++;
        }
        
        if ($only_warning) $Status['EXECUTED']=$Status['WAITING']=0; 
        $pie = '<data>';
        foreach ($Status as $status=>$nb) {
            $pie .= '<item id="'.$status.'"><STATUS>'.str_replace(' ','_',$status).'</STATUS><ORDERS>'.$nb.'</ORDERS><COLOR>'.$this->ColorStatus[$status].'</COLOR></item>';
        }
         $pie .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $pie );
        return $response;
    }

    public function barAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            '{spooler}'    => 'SCHEDULER_ID',
            '{start_time}' => 'SCHEDULE_PLANNED',
            '{end_time}'   => 'SCHEDULE_EXECUTED' );

      $qry = $sql->Select(array('SCHEDULE_PLANNED','STATUS'))
              .$sql->From(array('DAYS_SCHEDULE'))
              .$sql->Where($Fields);
              
        $res = $data->sql->query( $qry );
        // Par jour 
        
        while ($line = $data->sql->get_next($res)) {
            # On recupere les heures
            $day = substr($line['SCHEDULE_PLANNED'],8,5);
            $Days[$day]=1;
            if ($line['STATUS']==0) {
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
        $bar = "<?xml version='1.0' encoding='utf-8' ?>";
        $bar .= "<data>";
        if (isset($Days)) {
            foreach($Days as $i=>$v) {
                if (!isset($HS[$i])) $HS[$i]=0;
                if (!isset($HF[$i])) $HF[$i]=0;
                $bar .= '<item id="'.$i.'"><HOUR>'.substr($i,-2).'</HOUR><SUCCESS>'.$HS[$i].'</SUCCESS><FAILURE>'.$HF[$i].'</FAILURE></item>';
            }
        }
        $bar .= "</data>";
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $bar );
        return $response;
    }

    public function timelineAction($ordered=false)
    {
        $request = Request::createFromGlobals();
        $cyclic = $request->get('cyclic');
        $only_warning = $request->get('only_warning');

        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $session =  $this->container->get('arii_core.session'); 
        $this->ref_date  =  $session->getRefDate();
        
        $ds = $this->container->get('arii_ds.dailyschedule');
        $Jobs = $ds->DailySchedule( $cyclic, $only_warning, false);
        
        $xml = '<data>';
        foreach ($Jobs as $id=>$line) {
            $xml .= '<event id="'.$line['ID'].'">';
            $xml .= '<start_date>'.$line['SCHEDULE_PLANNED'].'</start_date>';
            if ($line['SCHEDULE_EXECUTED']!='')
                $xml .= '<end_date>'.$line['SCHEDULE_EXECUTED'].'</end_date>';
            else 
                $xml .= '<end_date>'.$line['SCHEDULE_PLANNED'].'</end_date>';
            $status = $line['STATUS'];
            $xml .= '<text>'.$line['ORDER_ID'].' > '.$line['JOB_CHAIN'].' ('.$line['SCHEDULER_ID'].')</text>';
            $xml .= '<section_id>'.$line['SCHEDULER_ID'].'</section_id>';
            $xml .= '<textColor>black</textColor>';
            $xml .= '<color>'.$this->ColorStatus[$status].'</color>';
            $xml .= '</event>';
        }
        $xml .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');        
        $response->setContent( $xml );
        return $response;    
    }

    public function gridAction($ordered = 0,$stopped=1) {

        $request = Request::createFromGlobals();
        $cyclic = $request->get('cyclic');
        $only_warning = $request->get('only_warning');

        $history = $this->container->get('arii_ds.dailyschedule');
        $Orders = $history->DailySchedule( $cyclic, $only_warning, false);
        
        $date = $this->container->get('arii_core.date');
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        foreach ($Orders as $k=>$order) {
            $status = $order['STATUS'];
            if (isset($this->ColorStatus[$status])) 
                $color = $this->ColorStatus[$status];
            else 
                $color = '#AAA';
            list($planned,$executed,$next,$duration) = $date->getLocaltimes( $order['SCHEDULE_PLANNED'],$order['SCHEDULE_EXECUTED'],'', $order['SCHEDULER_ID'], false  );   
            $list .='<row id="'.$order['ID'].'" style="background-color: '.$color.'">';
            $list .='<cell>'.$order['SCHEDULER_ID'].'</cell>';
            $list .='<cell>'.dirname($order['JOB_CHAIN']).'</cell>';
            $list .='<cell>'.basename($order['JOB_CHAIN']).'</cell>';
            $list .='<cell>'.$order['ORDER_ID'].'</cell>';
            $list .='<cell>'.$status.'</cell>';
            $list .='<cell>'.substr($planned,0,16).'</cell>';
            $list .='<cell>'.substr($executed,0,16).'</cell>';
            $list .='<userdata name="SCHEDULER_ORDER_HISTORY_ID">'.$order['SCHEDULER_HISTORY_ID'].'</userdata>';
            $list .='</row>';
        }
        
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

}
