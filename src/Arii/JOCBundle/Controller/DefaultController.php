<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class DefaultController extends Controller
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
        
        return $this->render('AriiJOCBundle:Default:index.html.twig', array('refresh' => $refresh, 'Timeline' => $Timeline ) );
    }

    public function readmeAction()
    {
        return $this->render('AriiJOCBundle:Default:readme.html.twig');
    }

    public function ribbonAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        
        return $this->render('AriiJOCBundle:Default:ribbon.json.twig',array(), $response );
    }

    public function menuAction()
    {
        return $this->render('AriiJOCBundle:Global:menu.xml.twig' );
    }

    public function plannedAction()
    {
        return $this->render('AriiJOCBundle:Default:planned.html.twig', 
                array(  'refresh'=>$this->getRefresh() )
                );
    }
    
    public function planned_pieAction()
    {
        return $this->render('AriiJOCBundle:Default:planned_pie.html.twig' );
    }

    public function historyAction()
    {
        return $this->render('AriiJOCBundle:Default:history.html.twig' );
    }

    public function history_pieAction()
    {
        return $this->render('AriiJOCBundle:Default:history_pie.html.twig' );
    }

    public function history_timelineAction()
    {
        return $this->render('AriiJOCBundle:Default:history_timeline.html.twig');
    }

    public function messagesAction()
    {
        return $this->render('AriiJOCBundle:Default:messages.html.twig');
    }
    
    public function spoolersAction()
    {
        return $this->render('AriiJOCBundle:Default:spoolers.html.twig');
    }

    public function timelineAction()
    {
        return $this->render('AriiJOCBundle:Default:timeline.html.twig');
    }

    public function pie_chartAction()
    {
        $request = $here = $this->getRequest()->getPathInfo();
        if (strpos($request,"/orders"))
            return $this->render('AriiJOCBundle:Sidebar:pie_chart_orders.html.twig');
        return $this->render('AriiJOCBundle:Sidebar:pie_chart.html.twig');
    }

    public function job_infoAction()
    {
        $request = $here = $this->getRequest()->getPathInfo();
        if (strpos($request,"/orders"))
            return $this->render('AriiJOCBundle:Sidebar:job_info_orders.html.twig');
        return $this->render('AriiJOCBundle:Sidebar:job_info.html.twig');
    }
    
}
