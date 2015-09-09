<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class LocksController extends Controller {
    protected $ColorStatus = array( 
        "AVAILABLE" => "#ccebc5",
        "FREE" => "#ccebc5",
        "LOCKED" => "#fbb4ae",
        "READY" => "#ccebc5",
        "TRUE" => "#ccebc5",
        "SUSPENDED" => "red",
        "CHAIN STOP." => "red",
        "SPOOLER STOP." => "red",
        "SPOOLER PAUSED" => "#fbb4ae",
        "NODE STOP." => "red",
        "NODE SKIP." => "#ffffcc",
        "JOB STOP." => "#fbb4ae",        
        "RUNNING" => "#ffffcc",
        "WARNING" => "#fbb4ae",
        "FAILURE" => "#fbb4ae",
        "FALSE" => "#fbb4ae",
        "DONE" => "lightblue",
        "ENDED" => "lightblue",
        "ON REQUEST" => "lightblue",
        "FATAL" => 'red',
        'WAITING' => '#DDD',
        "SETBACK" => "#fbb4ae",
        'UNKNOWN!' => '#FFF'
        );

    protected $image;

    public function __construct( )
    {
        $request = Request::createFromGlobals();
        $this->images = $request->getUriForPath('/../arii/images/wa');
    }

    public function indexAction()
    {
        return $this->render("AriiJOCBundle:Locks:index.html.twig");
    }
    
    public function gridAction($sort='last')
    {
        $request = Request::createFromGlobals();        
        $nested = $request->get('chained');
        $only_warning = $request->get('only_warning');
        $sort = $request->get('sort');

        $state = $this->container->get('arii_joc.state');
        $Locks = $state->Locks();
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        
        foreach($Locks as $id=>$line) {
            if ($line['STATE']=='active') {
                if ($line['IS_FREE']==1)
                    $status = 'FREE';
                else 
                    $status = 'LOCKED';
            }
            else {
                $status = strtoupper($line['STATE']);
            }
            $list .= '<row id="'.$id.'" bgColor="'.$this->ColorStatus[$status].'">';
            $list .= '<cell>'.$line['SPOOLER'].'</cell>';
            $list .= '<cell>'.$line['FOLDER'].'</cell>';
            $list .= '<cell>'.$line['NAME'].'</cell>';
            $list .= '<cell>'.$status.'</cell>';
            $list .= '<cell>'.$line['MAX_NON_EXCLUSIVE'].'</cell>';
            $list .= '</row>';
        }

        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

   public function UseAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');      
        $qry = $sql->Select(array('u.ID','IS_AVAILABLE','IS_MISSING','j.PATH as JOB'))
                .$sql->From(array('FOCUS_LOCKS_USE u'))
                .$sql->LeftJoin('FOCUS_JOBS j',array('u.JOB_ID','j.ID'))
                .$sql->Where(array('LOCK_ID' => $id));

        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('grid');
        $data->event->attach("beforeRender",array($this,"use_render"));
        $data->render_sql($qry,'ID','JOB,STATUS');
    }

    function use_render ($data){
        if ($data->get_value('IS_MISSING')==1) {
            $data->set_value('STATUS','MISSING');
        }
        elseif ($data->get_value('IS_AVAILABLE')==1) {
            $data->set_value('STATUS','AVAILABLE');
        }
        else {
            $data->set_value('STATUS','WAITING');
        }
    }

}

?>
