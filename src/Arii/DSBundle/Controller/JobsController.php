<?php

namespace Arii\DSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class JobsController extends Controller
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
          $this->images = $request->getUriForPath('/../bundles/ariicore/images/wa');          
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
               .$sql->From(array('SCHEDULER_HISTORY'))
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
       return $this->render('AriiDSBundle:Jobs:index.html.twig', array('refresh' => $refresh, 'Timeline' => $Timeline ) );
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiDSBundle:Jobs:toolbar.xml.twig',array(), $response );
    }

    public function form_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiDSBundle:Jobs:form_toolbar.xml.twig',array(), $response );
    }

    public function formAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiDSBundle:Jobs:form.json.twig',array(), $response );
    }

    public function gridAction($ordered = 0,$stopped=1) {

        $request = Request::createFromGlobals();
        $cyclic = $request->get('cyclic');
        $only_warning = $request->get('only_warning');

        $history = $this->container->get('arii_ds.dailyschedule');
        $Jobs = $history->DailySchedule( $cyclic, $only_warning, true);
        
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
        foreach ($Jobs as $k=>$job) {
            $status = $job['STATUS'];
            if (isset($this->ColorStatus[$status])) 
                $color = $this->ColorStatus[$status];
            else 
                $color = '#AAA';
            list($planned,$executed,$next,$duration) = $date->getLocaltimes( $job['SCHEDULE_PLANNED'],$job['SCHEDULE_EXECUTED'],'', $job['SCHEDULER_ID'], false  );   
            $list .='<row id="'.$job['ID'].'" style="background-color: '.$color.'">';
            $list .='<cell>'.$job['SCHEDULER_ID'].'</cell>';
            $list .='<cell>'.dirname($job['JOB']).'</cell>';
            $list .='<cell>'.basename($job['JOB']).'</cell>';
            $list .='<cell>'.$status.'</cell>';
            $list .='<cell>'.substr($planned,0,16).'</cell>';
            $list .='<cell>'.substr($executed,0,16).'</cell>';
            $list .='<userdata name="SCHEDULER_HISTORY_ID">'.$job['SCHEDULER_HISTORY_ID'].'</userdata>';
            $list .='</row>';
        }
        
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }
    
    public function pieAction($history_max=0,$ordered = 0,$only_warning=1) {        
        $request = Request::createFromGlobals();
        $cyclic = $request->get('cyclic');
        $only_warning = $request->get('only_warning');

        $ds = $this->container->get('arii_ds.dailyschedule');
        $Jobs = $ds->DailySchedule($cyclic, $only_warning, true);

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
            $pie .= '<item id="'.$status.'"><STATUS>'.str_replace(' ','_',$status).'</STATUS><JOBS>'.$nb.'</JOBS><COLOR>'.$this->ColorStatus[$status].'</COLOR></item>';
        }
         $pie .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $pie );
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
        $Jobs = $ds->DailySchedule( $cyclic, $only_warning, true);
        
        $xml = '<data>';
        foreach ($Jobs as $id=>$line) {
            $xml .= '<event id="'.$line['ID'].'">';
            $xml .= '<start_date>'.$line['SCHEDULE_PLANNED'].'</start_date>';
            if ($line['SCHEDULE_EXECUTED']!='')
                $xml .= '<end_date>'.$line['SCHEDULE_EXECUTED'].'</end_date>';
            else 
                $xml .= '<end_date>'.$line['SCHEDULE_PLANNED'].'</end_date>';
            $status = $line['STATUS'];
            $xml .= '<text>'.$line['JOB'].' ('.$line['SCHEDULER_ID'].')</text>';
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

}