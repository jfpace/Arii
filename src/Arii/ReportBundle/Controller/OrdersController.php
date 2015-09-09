<?php

namespace Arii\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class OrdersController extends Controller
{
    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
    }

    public function gridAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('grid');

 $qry = 'SELECT 
    soh.HISTORY_ID as ID,soh.SPOOLER_ID,soh.JOB_CHAIN,soh.ORDER_ID,soh.TITLE,soh.STATE,soh.STATE_TEXT,soh.START_TIME,soh.END_TIME
 FROM SCHEDULER_ORDER_HISTORY soh
 WHERE JOB_CHAIN="Reports/jasper_reports"
 ORDER by START_TIME desc';
        $data->render_sql($qry,"ID","SPOOLER_ID,ORDER_ID,TITLE,STATE,STATE_TEXT,START_TIME,END_TIME");
    }
}
