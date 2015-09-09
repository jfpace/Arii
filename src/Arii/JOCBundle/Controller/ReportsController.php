<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ReportsController extends Controller {

    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');          
    }

    public function jobsAction()
    {
        return $this->render("AriiJOCBundle:Reports:jobs.html.twig");
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Reports:toolbar.xml.twig', array(), $response);
    }

    public function report_jobsAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
        
        $sql = $this->container->get('arii_core.sql');
        $Fields = array (
            '{spooler}'    => 'SPOOLER_NAME' );
            
        $qry = $sql->Select(array('fj.ID','fj.NAME','fj.TITLE','fj.PATH','fj.STATE','fj.NEXT_START_TIME',
                                'fs.NAME as SPOOLER_NAME')) 
        .$sql->From(array('FOCUS_JOBS fj'))
        .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fj.spooler_id','fs.id'))
        .$sql->Where($Fields)
        .$sql->OrderBy(array('fj.NAME','fj.PATH'));

//        $data->event->attach("beforeRender", array( $this, "color_messages") );
        $data->render_sql($qry,"ID","NAME,TITLE,STATE,NEXT_START_TIME,PATH,SPOOLER_NAME");
    }

}

?>