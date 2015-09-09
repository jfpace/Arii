<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ToolbarController extends Controller
{
    public function globalAction()
    {
        // date de reference
        $session = $this->container->get('arii_core.session');
        $ref_date = $session->getRefDate();

        $refresh = $session->getRefresh();
        
        // databases 
        $db = $session->getDatabase();
        $database = $db['name'];
        $Databases = $session->getDatabases();
        foreach($Databases as $f) {
            $databases[$f['id']] = $f['name'];
        }        
        
        // filtres
        $db = $session->getFilter();
        $filter = $db['name'];
        $Filters = $session->getFilters();
        foreach($Filters as $f) {
            $filters[$f['id']] = $f['name'];
        }
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Toolbar:global.xml.twig', array( 'refresh' => $refresh, 'ref_date' => $ref_date, 'database' => $database, 'databases' => $databases, 'filter' => $filter, 'filters' => $filters ), $response );
    }
    
    public function defaultAction()
    {
        $response = $this->render('AriiJOCBundle:Toolbar:default.xml.twig');
        $response->headers->set("Content-Type","application/xml");
        return $response;
    }
    
    public function toolbar_schedule_listAction()
    {
        return $this->render('AriiJOCBundle:Toolbar:schedule_list.html.twig');
    }
    public function toolbar_refreshAction()
    {
        return $this->render('AriiJOCBundle:Toolbar:refresh.html.twig');
    }
    
    public function toolbar_start_jobAction()
    {
        return $this->render("AriiJOCBundle:Toolbar:toolbar_start_job.html.twig");
    }
    
    public function toolbar_start_jobsAction()
    {
        return $this->render("AriiJOCBundle:Toolbar:toolbar_start_jobs.html.twig");
    }
    
    public function toolbar_stop_jobAction()
    {
        return $this->render("AriiJOCBundle:Toolbar:toolbar_stop_job.html.twig");
    }
    
    public function toolbar_stop_jobsAction()
    {
        return $this->render("AriiJOCBundle:Toolbar:toolbar_stop_jobs.html.twig");
    }
    
    public function toolbar_unstop_jobAction()
    {
        return $this->render("AriiJOCBundle:Toolbar:toolbar_unstop_job.html.twig");
    }
    
    public function toolbar_unstop_jobsAction()
    {
        return $this->render("AriiJOCBundle:Toolbar:toolbar_unstop_jobs.html.twig");
    }
        
    public function job_listAction()
    {
        $request = Request::createFromGlobals();
        $ids = $request->get('ids');
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('grid');
        
        $qry = "SELECT id,job_name FROM SCHEDULER_HISTORY WHERE id in ($ids) ORDER BY id DESC";
        
        $data->event->attach("beforeRender",array($this,"setStatus"));
        $data->render_sql($qry,"id","job_name,status");
    }
    
    public function setStatus($row)
    {
        $row->set_value("status","ready");
    }
    
    public function start_job_parametersAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $qry = "SELECT parameters FROM SCHEDULER_HISTORY WHERE id='$id'";
        $res = $data->sql->query($qry);
        $line = $data->sql->get_next($res);
        
        $params = $line['parameters'];
        $Parameters = array();
        
        while (($p = strpos($params,'<variable name="'))>0) {
            $begin = $p+16;
            $end = strpos($params,'" value="',$begin);
            $var = substr($params,$begin,$end-$begin);
            $params = substr($params,$end+9);
            $end = strpos($params,'"/>');
            $val = substr($params,0,$end);
            $params = substr($params,$end+2);
            if (strpos(" $val",'password')>0) {
                // a voir avec les connexions
            } 
            else {
                $Parameters[$var] = $val;
            }
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
        
        foreach ($Parameters as $vr => $vl)
        {
            $list .= '<row><cell>'.$vr.'</cell><cell>'.$vl.'</cell></row>';
        }
        $list .= '</rows>';
        
        $response->setContent($list);
        return $response;
        
    }
    
    public function footerAction()
    {
        return $this->render("AriiJOCBundle:Toolbar:footer.xml.twig");
    }

/* CORRECTIONS */
    
    public function start_orderAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Toolbar:start_order.xml.twig', array(), $response);
    }
    
    public function order_paramsAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Toolbar:order_params.xml.twig', array(), $response);
    }

}
