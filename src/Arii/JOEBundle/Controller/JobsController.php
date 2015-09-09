<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Entity\ParamJob;
use Arii\JOEBundle\Entity\Script;

class JobsController extends Controller
{

    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Jobs:index.html.twig');
    }

    public function treegridAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $qry = "select ID,CATEGORY,NAME,TITLE,JOB_TYPE,TRIGMODE from joe_job order by NAME"; 
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
                $jn = $line['CATEGORY'].'/'.$line['NAME'];
                if (substr($jn,0,1)=='/')
                        $jn =  substr($jn,1);
                
                $Info[$jn]= $line['ID'].'|'.$line['NAME'].'|'.$line['TITLE'].'|'.$line['TRIGMODE'];
                $key_files[$jn] = $jn;
        }
        
        header("Content-type: text/xml");
        $tree = $this->explodeTree($key_files, "/");
        print '<?xml version="1.0" encoding="UTF-8"?>';
        print "<rows>\n";
        print '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        print $this->Job2XML( $tree, '', $Info );
        print "</rows>\n";
        exit();
    }

    function Job2XML( $leaf, $id = '', $Info ) {
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $i = substr("$id/$k",1);
                            if (isset($Info[$i])) {
                                $cell = '';
                                list($dbid, $name, $title, $trigger ) = explode('|',$Info[$i]);
                                print '<row id="'.$dbid.'">';
                                $cell .= '<cell image="queue.png"> '.$k.'</cell>';
                                $cell .= '<cell>'.$title.'</cell>';
                                $cell .= '<cell>'.$trigger.'</cell>';
                                print $cell;
                            }
                            else {
                                    print '<row id="__'.$k.'" open="1">';
                                    print '<cell image="folder.gif">'.$k.'</cell>';
                            }
                           $this->Job2XML( $leaf[$k], $id.'/'.$k, $Info );
                           print '</row>';
                    }
            }
    }

}
