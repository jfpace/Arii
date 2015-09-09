<?php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ActionsController extends Controller
{
    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Actions:index.html.twig');
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOEBundle:Actions:toolbar.xml.twig', array(), $response );
    }

   public function treemenuAction()
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
    
/******************************************************************/
    // http://kevin.vanzonneveld.net/techblog/article/convert_anything_to_tree_structures_in_php/
    function explodeTree($array, $delimiter = '_', $baseval = false)
    {
      if(!is_array($array)) return false;
      $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
      $returnArr = array();
      foreach ($array as $key => $val) {
        // Get parent parts and the current leaf
        $parts  = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);

        // Build parent structure
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
          if (!isset($parentArr[$part])) {
            $parentArr[$part] = array();
          } elseif (!is_array($parentArr[$part])) {
            if ($baseval) {
              $parentArr[$part] = array('__base_val' => $parentArr[$part]);
            } else {
              $parentArr[$part] = array();
            }
          }
          $parentArr = &$parentArr[$part];
        }

        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
          $parentArr[$leafPart] = $val;
        } elseif ($baseval && is_array($parentArr[$leafPart])) {
          $parentArr[$leafPart]['__base_val'] = $val;
        }
      }
      return $returnArr;
    }

}
