<?php

namespace Arii\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DocumentsController extends Controller
{
    private $charset='UTF-8';
    
    public function indexAction()
    {
  //      $url = $this->container->getParameter('report_url');
        $url = "http://google.com";
        return $this->render('AriiReportBundle:Documents:index.html.twig', array('report_url'=>$url) );
    }
    
    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiReportBundle:Documents:toolbar.xml.twig',array(),$response );
    }

    public function gridAction()
    {
        $request = Request::createFromGlobals();
        $images = $request->getUriForPath('/../arii/images');
       // lecture du repertoire des documents
        $dir = $this->container->getParameter('report_path');
        $this->charset = $this->container->getParameter('charset');
        $lang =  strtoupper(substr($this->get('request')->getLocale(),-2));
        $Docs = $this->TreeDir("$dir/".$lang);
        $Keys = array_keys($Docs);
        sort($Keys);
        $key_files = array();
        foreach ($Keys as $k ) {
            $i =substr($k,1);
            $key_files[$i] = $i;
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><rows>
<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        
        $tools = $this->container->get('arii_core.tools');
        $tree = $tools->explodeTree($key_files, "/");

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        /*implode(' ',$status)
        SUCCESS FAILURE
        strpos(SUCCESS )*/
        $list .= $this->Dir2XML( $tree, '', $Docs);
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

    function Dir2XML( $leaf, $id = '', $Docs ) {
            $return = '';
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                        $i  = "$id/$k";
                        if (isset($Docs[$i])){
                            $type = $Docs[$i]['type'];
                            $return .=  '<row id="'.$this->Codage($i).'" open="1">';
                            $return .=  '<userdata name="type">'.$type.'</userdata>';
                            $return .=  '<cell image="'.$type.'.png"> '.$this->Codage($Docs[$i]['name']).'</cell>';
                            $return .=  '<cell>'.$Docs[$i]['date'].'</cell>';
                            $return .=  '<cell>'.$Docs[$i]['size'].'</cell>';
                        }
                        else {
                            $return .=  '<row id="'.$this->Codage($i).'" open="1">';
                            $return .=  '<userdata name="type">folder</userdata>';
                            $return .=  '<cell image="folder.gif">'.$this->Codage($k).'</cell>';
                        }
                        $return .= $this->Dir2XML( $leaf[$k], $id.'/'.$k, $Docs);
                        $return .= '</row>';
                    }
            }
            return $return;
    }

    protected function Codage($text) {
        if ($this->charset != 'UTF-8')
            return utf8_encode($text);
        return $text;
    }
    
    protected function TreeDir($base, $dir='',$Result=array()) {
        if ($dh = opendir("$base/$dir")) {
            while (($file = readdir($dh)) !== false) {
                $d = "$base/$dir/$file";
                if (is_file($d)) {
                    $type = substr($file,-3);
                    $stat = stat($d);
                    $size = $stat[7];
                    $TM = localtime($stat[9],true);
                    $date = sprintf("%04d-%02d-%02d %02d:%02d:%02d",
                            $TM['tm_year']+1900, $TM['tm_mon']+1,$TM['tm_mday'],
                            $TM['tm_hour'], $TM['tm_min'],$TM['tm_sec'] );
                    $id = "$dir/$file";
                    $Result[$id]=
                    array(
                        'name'=>substr($file,0,strlen($file)-4),
                        'type'=>$type,
                        'size'=>$size,
                        'date'=>$date);
                }
                elseif (is_dir($d) && (substr($file,0,1)!='.')) {
                    $Result =  array_merge($Result,$this->TreeDir("$base","$dir/$file",$Result));
                }
            }
            closedir($dh);
        }
        return $Result;
    }
    
    function size($s) {
        if ($s>1048576) {
            return round($s/1048576)." ".$this->get('translator')->trans('size.Mb');
        }
        if ($s>1024) {
            return round($s/1024)." ".$this->get('translator')->trans('size.Kb');
        }
        return $s." ".$translator->trans('size.b');
    }
}
