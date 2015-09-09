<?php

namespace Arii\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DocumentController extends Controller
{
    private $charset='UTF-8';
    
    public function docAction()
    {
        $request = Request::createFromGlobals();
        $dir = $this->container->getParameter('report_path');
        $this->charset = $this->container->getParameter('charset');
        $doc = $this->Decodage($request->query->get( 'doc' ));
        $p = strpos($doc,'.');
        $type = substr($doc,$p+1);
        $response = new Response();
        $lang =  strtoupper(substr($this->get('request')->getLocale(),-2));
        $content = file_get_contents("$dir/$lang/$doc");
        switch($type) {
            case 'pdf':
                $response->headers->set('Content-Type', 'application/pdf');
                break;
            case 'rtf':
                $response->headers->set('Content-Type', 'application/msword');
                break;
            case 'xls':
                $response->headers->set('Content-Type', 'application/xls');
                break;
            case 'html':
                $response->headers->set('Content-Type', 'text/html');
                break;
            case 'xml':
                $content = "<pre>".str_replace('<','&lt;',$content)."</pre>";
                break;
            default:
               $content = $doc;
        }    
        $length = strlen($content);        
        $response->headers->set('Content-Length',$length);
        $response->headers->set('Content-Disposition', 'inline; filename="'.$doc.'"');
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Expires', 0);
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');
        $response->setContent($content);
        return $response;
    }
    
    protected function Decodage($text) {
        if ($this->charset != 'UTF-8')
            return utf8_decode($text);
        return $text;
    }
    
    public function getAction()
    {
        $request = Request::createFromGlobals();
        $dir = $this->container->getParameter('report_path');
        $doc = $this->Decodage($request->query->get( 'doc' ));
        $p = strpos($doc,'.');
        $type = substr($doc,$p+1);
        $response = new Response();
        $lang =  strtoupper(substr($this->get('request')->getLocale(),-2));
        $content = file_get_contents("$dir/$lang/$doc");
        switch($type) {
            case 'pdf':
                $response->headers->set('Content-Type', 'application/pdf');
                break;
            case 'rtf':
                $response->headers->set('Content-Type', 'application/rtf');
                break;
            case 'xls':
                $response->headers->set('Content-Type', 'application/xls');
                break;
            case 'html':
                $response->headers->set('Content-Type', 'text/html');
                break;
            case 'xml':
                $response->headers->set('Content-Type', 'text/xml');
                break;
            default:
               $content = $doc;
        }    
        $length = strlen($content);        
        $response->headers->set('Content-Length',$length);
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$doc.'"');
        $response->setContent($content);
        return $response;
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiReportBundle:Document:toolbar.xml.twig',array(),$response  );
    }
}
