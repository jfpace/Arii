<?php

namespace Arii\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AriiReportBundle:Default:index.html.twig' );
    }
    public function documentAction()
    {
        return $this->render('AriiReportBundle:Default:document.html.twig' );
    }
    public function statusAction()
    {
        return $this->render('AriiReportBundle:Default:status.html.twig' );
    }
    
    public function historyAction()
    {
        return $this->render('AriiReportBundle:Default:history.html.twig' );
    }

    public function ribbonAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        
        return $this->render('AriiReportBundle:Default:ribbon.json.twig',array(), $response );
    }

    public function treeAction($path='report')
    {        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $path = $this->container->getParameter('report_path');
        $xml = "<?xml version='1.0' encoding='utf-8'?>";                
        $xml .= '<tree id="0">';        
        $xml .= $this->TreeXML($path,'');
        $xml .= '</tree>';        
        $response->setContent($xml);
        return $response;
    }

    public function TreeXML($basedir,$dir ) {
        $xml ='';
        if ($dh = @opendir($basedir.'/'.$dir)) {
            $Dir = array();
            $Files = array();
            while (($file = readdir($dh)) !== false) {
                $sub = $basedir.'/'.$dir.'/'.$file;
                if (($file != '.') and ($file != '..')) {
                    if (is_dir($sub)) {
                        array_push($Dir, $file );
                    }
                    else {
                        array_push($Files, $file );                
                    }
                }
            }
            closedir($dh);
            
            sort($Files);
            foreach ($Files as $file) {
                // on ne s'intéresse qu'aux pdfs
                if (substr($file,-4)=='.pdf') {
                    $f = substr($file,0,strlen($file)-4);
                    $xml .= '<item id="'.utf8_encode("$basedir/$dir/$file").'" text="'.utf8_encode($f).'" im0="pdf.png"/>';
                }
            }

            sort($Dir);
            foreach ($Dir as $file) {
                $xml .= '<item id="'.utf8_encode("$dir/$file/").'" text="'.utf8_encode($file).'" im0="folder.gif">';
                $xml .= $this->TreeXML($basedir,"$dir/$file");
                $xml .= '</item>';
            }
            
        }
        else {
            exit();
        }
        return $xml;
    }

    public function docAction()
    {
        $request = Request::createFromGlobals();
        $this->charset = $this->container->getParameter('charset');
        $doc = $this->Decodage($request->query->get( 'doc' ));
        $p = strpos($doc,'.');
        $type = substr($doc,$p+1);
        $response = new Response();
        $content = file_get_contents($doc);
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

    public function infoAction()
    {
        $request = Request::createFromGlobals();
        $this->charset = $this->container->getParameter('charset');
        $doc = $this->Decodage($request->query->get( 'doc' ));
        $stat = stat($doc);
        $size = $stat[7];
        $TM = localtime($stat[9],true);
        $date = sprintf("%04d-%02d-%02d %02d:%02d:%02d",
                $TM['tm_year']+1900, $TM['tm_mon']+1,$TM['tm_mday'],
                $TM['tm_hour'], $TM['tm_min'],$TM['tm_sec'] );
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $form = '<?xml version="1.0" encoding="UTF-8"?>';
        $form .= "<data>\n";
        $form .= "<file>".utf8_encode(basename($doc))."</file>";    
        $form .= "<size>".$size."</size>";    
        $form .= "<date>".$date."</date>";  
        $form .= "</data>\n";
        $response->setContent($form);
        return $response;
    }
}
