<?php

namespace Arii\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ConnectionsController extends Controller
{
   public function indexAction()
    {
        return $this->render('AriiAdminBundle:Connections:index.html.twig');
    }
    
    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Connections:menu.xml.twig", array(), $response );
    }
    
    public function gridAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterpriseId();

        $db = $this->container->get("arii_core.db");
        $data = $db->Connector("data");
        
        # Recuperation des utilisations de connections
        $qry = "SELECT s.name,s.id,s.connection_id,s.supervisor_id,s.primary_id,s.db_id,s.smtp_id 
                FROM ARII_SPOOLER s
                left join ARII_CONNECTION c
                on s.connection_id=c.id
                where c.enterprise_id=$enterprise";
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $name = $line['name'];
            foreach (array('connection_id','supervisor_id','primary_id','db_id','smtp_id') as $k) {
                if ($line[$k]!='') {
                    $cid = $line[$k];
                    if (isset($Use[$cid]))
                        $Use[$cid] .= ','.$name.'|'.$line['id'].'|spooler';
                    else 
                        $Use[$cid] = $name.'|'.$line['id'].'|spooler';
                }
            }
        }
        
        # Tableau des connections
        $qry = "SELECT c.id,c.title,c.host,c.description,n.description as type,cat.name as category 
                FROM ARII_CONNECTION c
                LEFT JOIN ARII_NETWORK n
                ON c.network_id=n.id
                LEFT JOIN ARII_CATEGORY cat
                ON n.category_id=cat.id
                WHERE c.enterprise_id=$enterprise
                ORDER BY cat.name,n.description,c.title";

        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $id = $line['id'];
            $jn = $line['category'].'/'.$line['type'].'/'.$id;  
            $Info[$jn] = $line['id'].'|'.$line['title'].'|'.$line['description'].'|'.$line['host'];
            $key_files[$jn] = $jn;

            # Est il utilisé ?
            if (isset($Use[$id]))  {
                foreach (explode(',',$Use[$id]) as $u) {
                    list($uname,$uid,$utype) = explode('|',$u);
                    $ju = $line['category'].'/'.$line['type'].'/'.$id.'/'.$uid; 
                    $key_files[$ju] = $ju;
                    $Usage[$ju] = "$uname|$uid|$utype|$id";
                }
            } // Sinon en cours de création
/*            else {
                print "((($ju)))";
                $ju = $line['category'].'/'.$line['type'];
                $key_files[$ju] = $ju;
            }
*/        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        if (count($key_files)==0) {
            $response->setContent( $this->NoRecord() );
            return $response;
        }
   
        $tree = $this->explodeTree($key_files, "/");
        // print "<pre>"; print_r($tree); print "</pre>"; exit();
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        $list .= $this->Connections2XML( $tree, '', $Info, $Usage );
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

   function Connections2XML( $leaf, $id = '', $Info, $Usage ) {
        $return = '';
        if (is_array($leaf)) {
            foreach (array_keys($leaf) as $k) {
                $Ids = explode('/',$k);
                $here = array_pop($Ids);
                $i  = substr("$id/$k",1);
                //$return .= "<k>$k</k><i>$i</i><here>$here</here>";
                if (isset($Usage[$i])) {
                    list($name, $uid, $type, $cid ) = explode('|',$Usage[$i]);
                    $return .= '<row id="'.$cid.'#'.$uid.'" open="1">';
                    $return .= '<cell image="'.$type.'.png">'.$name.'</cell>';
                    $return .= '<cell>'.$type.'</cell>';
                    $return .= '<userdata name="type">'.$type.'</userdata>';
                }
                elseif (isset($Info[$i])) {
                    list($dbid, $title, $description, $host ) = explode('|',$Info[$i]);
                    $return .= '<row id="'.$dbid.'">';
                    $return .= '<cell image="bullet_blue.png">'.$title.'</cell>';
                    $return .= '<cell>'.$description.'</cell>';
                    $return .= '<cell>'.$host.'</cell>';
                    $return .= '<userdata name="type">connection</userdata>';
                }
                else {
                    $return .= '<row id="'.$k.'" open="1">';
                    if ($id == '') {
                        $return .= '<cell image="'.$here.'.png"> '.$this->get('translator')->trans($here).'</cell>';
                        $return .= '<userdata name="type">'.$here.'</userdata>';
                    }
                    else {
                        $return .= '<cell image="folder.gif">'.$here.'</cell>';
                    }
                }
               $return .= $this->Connections2XML( $leaf[$k], $id.'/'.$k, $Info, $Usage);
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

}
