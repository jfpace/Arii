<?php

namespace Arii\ATSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $Colors = $this->container->getParameter('color_status');
        foreach ($Colors as $k=>$v) {
            if (($p=strpos($v,"/"))>0) $Colors[$k] = substr($Colors[$k],0,$p);                    
        }
        return $this->render('AriiATSBundle:Default:index.html.twig', array("color" => $Colors));
    }

    public function readmeAction()
    {
        return $this->render('AriiATSBundle:Default:readme.html.twig');
    }

    public function sendevent_toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiATSBundle:Default:sendevent_toolbar.xml.twig',array(), $response );
    }

    public function ribbonAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        
        return $this->render('AriiATSBundle:Default:ribbon.json.twig',array(), $response );
    }

    public function docAction() {
        $request = Request::createFromGlobals();
        $lang = $this->getRequest()->getLocale();
        $template = $this->container->getParameter('ats_doc');

        $doc = $this->container->get('arii_core.doc');
        $url = $doc->Url($template);
        
        header("Location: $url");
        die();
    }

    public function sendeventAction() {
        $request = Request::createFromGlobals();
        $job = $request->request->get( 'JOB' );
        $action = $request->request->get( 'ACTION' );
        $comment = $request->request->get( 'COMMENT' );
        
        $exec = $this->container->get('arii_ats.exec');
        
        $sendevent = 'sendevent -E '.$action.' -J '.$job.' -c "'.$comment.'"';
        print "$sendevent";
        exit();
        print $exec->Exec($sendevent);
        
        // On recupere l'evenement immediatement
        print "$sendevent";
        exit();
    }

    public function autorepAction() {
        $request = Request::createFromGlobals();
        $job = $request->query->get( 'job' );
        $options = $request->query->get( 'options' );
        
        $exec = $this->container->get('arii_ats.exec');
        
        header('Content-Type: text/html; charset=utf-8');        
        print "<pre>";
        print $exec->Exec("autorep -J $job $options");
        print "</pre>";
        
        exit();
    }

    public function autosyslogAction() {
        $request = Request::createFromGlobals();
        $job = $request->query->get( 'job' );
        $options = $request->query->get( 'options' );
        
        $exec = $this->container->get('arii_ats.exec');
        
        header('Content-Type: text/html; charset=utf-8');        
        print "<pre>";
        print $exec->Exec("autosyslog -J $job $options");
        print "</pre>";
        
        exit();
    }
/*
File Name  File Num Status     Size (KB) Date   Time   User Data  Queue Name 
---------- -------- ---------- --------- ------ ------ ---------- ---------- 
QPJOBLOG   1        *READY     24        150911 093940 EJOBOTOSY1 QEZJOBLOG  
 */
    public function autosyslog_gridAction() {
        $request = Request::createFromGlobals();
        $job = $request->query->get( 'job' );
        $options = $request->query->get( 'options' );
        
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "<rows>\n";
        $xml .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        
        $exec = $this->container->get('arii_ats.exec');
        $result = $exec->Exec("autosyslog -J $job $options");
        foreach (explode("\n",$result) as $l) {
            if (preg_match("/^(.{10}) (.{8}) (.{10}) (.{9}) (\d{6}) (\d{6}) (.{10}) (.*)/",$l,$matches)) {
                $xml .= "<row>";
                array_shift($matches);
                foreach($matches as $m) {
                    $xml .= "<cell>".trim($m)."</cell>";
                }
                $xml .= "</row>";                
            }
        }
        $xml .= "</rows>\n";
        $response->setContent( $xml );
        return $response;
    }

    public function chk_auto_upAction() {
        $request = Request::createFromGlobals();
        $job = $request->query->get( 'job' );
        $options = $request->query->get( 'options' );
        
        $exec = $this->container->get('arii_ats.exec');
        $Check = array();
        foreach (explode("\n",$exec->Exec("chk_auto_up")) as $line) {
            if (strpos($line, "Connected with")) {
                $line = '<font color="green">'.$line.'</font>';
            }
            elseif (strpos($line, "is RUNNING")) {
                $line = '<font color="green">'.$line.'</font>';
            }
            elseif (strpos($line, "not RUNNING")) {
                $line = '<font color="red">'.$line.'</font>';
            }
            elseif (strpos($line, "***")) {
                $line = '<strong>'.$line.'</strong>';
            }
            array_push($Check, $line);            
        }
        header('Content-Type: text/html; charset=utf-8');        
        print "<pre>";
        print implode("\n",$Check);
        print "</pre>";
        
        exit();
    }

}
