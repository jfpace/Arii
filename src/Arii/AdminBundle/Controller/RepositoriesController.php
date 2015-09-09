<?php

namespace Arii\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RepositoriesController extends Controller {
    
    public function indexAction()
    {
        return $this->render('AriiAdminBundle:Repositories:index.html.twig', array('id'=>''));
    }

    public function gridAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterpriseId();
        
        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('data');
        
        $qry = "SELECT ar.id,ar.name,ar.timezone,ar.description,sp.id as spooler_id,sp.scheduler as spooler
                FROM ARII_REPOSITORY ar
                LEFT JOIN ARII_SPOOLER sp
                ON ar.db_id=sp.db_id
                WHERE ar.enterprise_id = $enterprise
                ORDER BY ar.name,sp.scheduler";
        
        // $grid->render_sql($qry,"id","name,title,timezone,enterprise,description");
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $rep = $line['name'];
            $jn = $rep.'/'.$line['spooler'];  
            $Info[$rep] = $line['id'].'|'.$line['description'].'|'.$line['timezone'];
            $InfoSpooler[$jn] = $line['spooler_id'].'|'.$line['spooler'];
            $key_files[$jn] = $rep;
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        if (count($key_files)==0) {
            $response->setContent( $this->NoRecord() );
            return $response;
        }
   
        $tree = $this->explodeTree($key_files, "/");
        //print_r($tree);
        //exit();
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        $list .= $this->Repositories2XML( $tree, '', $Info, $InfoSpooler );
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;

    }

   function Repositories2XML( $leaf, $id = '', $Info, $InfoSpooler ) {
        $return = '';
        if (is_array($leaf)) {
            foreach (array_keys($leaf) as $k) {
                $Ids = explode('/',$k);
                $here = array_pop($Ids);
                $i  = substr("$id/$k",1);
                // $return .= "<k>$k</k><i>$i</i><here>$here</here>";
                if (isset($InfoSpooler[$i])) {
                    list($dbid, $spooler ) = explode('|',$InfoSpooler[$i]);
                    $return .= '<row id="S#'.$dbid.'">';
                    $return .= '<cell image="spooler.png">'.$here.'</cell>';
                    $return .= '<userdata name="type">spooler</userdata>';
                }
                elseif (isset($Info[$i])) {
                    list($dbid, $description, $host ) = explode('|',$Info[$i]);
                    $return .= '<row id="'.$dbid.'">';
                    $return .= '<cell image="database.png">'.$here.'</cell>';
                    $return .= '<cell>'.$description.'</cell>';
                    $return .= '<cell>'.$host.'</cell>';
                    $return .= '<userdata name="type">repository</userdata>';
                }
                else {
                    $return .= '<row id="'.$k.'" open="1">';
                    $return .= '<cell image="folder.gif">'.$here.'</cell>';
                }
               $return .= $this->Repositories2XML( $leaf[$k], $id.'/'.$k, $Info, $InfoSpooler);
               $return .= '</row>';
            }
        }
        return $return;
    }

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

    public function NoRecord()
    {
        $no = '<?xml version="1.0" encoding="UTF-8"?>';
        $no .= '
    <rows><head><afterInit><call command="clearAll"/></afterInit></head>
<row id="scheduler" open="1"><cell image="wa/spooler.png"><b>No record </b></cell>
</row></rows>';
        return $no;
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Repositories:menu.xml.twig", array(), $response);
    }

    public function connectionsAction()
    {
        $dhtmlx = $this->container->get('arii_core.db');
        $select = $dhtmlx->Connector('select');
        
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterpriseId();
        
        $qry = "SELECT ac.id,ac.title 
                FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id=1 and ac.enterprise_id=".$enterprise;
        $select->render_sql($qry,"id","id,title");
    }
    

}

?>
