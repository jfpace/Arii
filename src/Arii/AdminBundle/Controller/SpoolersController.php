<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SpoolersController extends Controller {
    
    protected $images;
    protected $CurrentDate;
     
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
          
          $this->CurrentDate = date('Y-m-d');
    }

    public function indexAction()
    {
        return $this->render('AriiAdminBundle:Spoolers:index.html.twig');
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiAdminBundle:Spoolers:menu.xml.twig', array(), $response);
    }

    public function treegridAction()
    {     
        
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterprise();
        $enterprise_id  = $session->getEnterpriseId();

        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $Infos = array();
        $key_files = array();
        
        $qry = "SELECT s.name as site,sp.id as spooler_id,sp.name as spooler,sp.supervisor_id,sp.db_id,sp.events,sp.status,sp.version,sp.remote,c.host,c.port,c1.title as db,c1.id
                FROM ARII_SPOOLER sp
                LEFT JOIN ARII_SITE s
                ON sp.site_id=s.id
                LEFT JOIN ARII_CONNECTION c
                ON sp.connection_id=c.id        
                LEFT JOIN ARII_CONNECTION c1
                ON sp.db_id=c1.id 
                WHERE s.enterprise_id=".$enterprise_id." order by s.name,sp.id ";
        
        $res = $data->sql->query($qry);
        $Spooler_info = array();
        while ($line = $data->sql->get_next($res))
        {
            $sun = $enterprise.'/'.$line['site'].'/'.$line['db'].'/'.$line['spooler_id'];
            if ($line['db'] == "" && $line['spooler_id'] != "")
            {
                $sun = $enterprise.'/'.$line['site'].'/'."NO DB".'/'.$line['spooler_id'];
            }
                   
            $Spooler_info[$line['spooler_id']]= $line['spooler'].'|'.$line['events'].'|'.$line['status'].'|'.$line['version'].'|'.$line['host'].'|'.$line['port'].'|'.$line['site'].'|'.$line['remote'];
            $Infos[$sun] = $line['spooler'].'|'.$line['supervisor_id'];
            $key_files[$sun] = $sun;
        }
        
        $tree = $this->explodeTree($key_files,'/');
        
        header("Content-type: text/xml");
        if (count($key_files)==0) {
            $this->NoRecord();
        }


        print '<?xml version="1.0" encoding="UTF-8"?>';
        print "<rows>\n";
        print '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        print $this->show_spoolerXML( $tree,'', $Infos, $Spooler_info, $level = 0 );
        print "</rows>\n";
        exit();
    }
    
    public function show_spoolerXML($tree, $id ="",$Infos, $Spooler_info, $level )
    {
        if(is_array($tree)){
            foreach (array_keys($tree) as $k)
            {
                $ids = explode('/', $k);
                $here = array_pop($ids);
                $i = substr("$id/$k",1);
                if(isset($Infos[$i]))
                {
                    $cell = "";
                    list($spooler,$supervisor_id) = explode('|', $Infos[$i]);
                    list($sp,$events,$status,$version,$host,$port,$site,$remote) = explode('|', $Spooler_info[$here]);
                    if($supervisor_id == null)
                    {
                        $supervisor = null;
                    } else{
                        list($supervisor) = explode('|', $Spooler_info[$supervisor_id]);
                    }
                    print '<row id="'.$here.'">';
                    $cell .= '<cell image="spooler.png"> '.$spooler.'</cell>';
                    $cell .= '<cell>'.$host.'</cell>';
                    $cell .= '<cell>'.$supervisor.'</cell>';
                    $cell .= '<cell>'.$version.'</cell>';
                    if ($remote=='1') {
                        $img_remote = 'logout';
                    }
                    else {
                        $img_remote = 'login';
                    }
                    $cell .= '<cell><![CDATA[<img src="'.$this->images.'/'.$img_remote.'.png"/>]]></cell>';
                    $img_status = 'active';
                    $cell .= '<cell><![CDATA[<img src="'.$this->images.'/'.$img_status.'.png"/>]]></cell>';
                    $cell .= '<cell>'.$events.'</cell>';
                    $cell .= '<userdata name="type">spooler</userdata>';
                    print $cell;
                } else {
                    print '<row id="'.$i.'" open="1">';
                    if ($id == "")
                    {
                        print '<cell image="enterprise.png"> '.$here.'</cell>';
                        print '<userdata name="type">enterprise</userdata>';
                        $level = 0;
                    }
                    else {
                        $level++;
                        if ($level == 1) {
                            print '<cell image="site.png">'.$here.'</cell>';
                            print '<userdata name="type">site</userdata>';
                        }
                        else {
                            print '<cell image="database.png">'.$here.'</cell>';
                            print '<userdata name="type">database</userdata>';
                        }
                    }
                }
                $this->show_spoolerXML($tree[$k],$id.'/'.$k, $Infos, $Spooler_info, $level);
                print '</row>';
            }
        }
    }
    
    public function NoRecord()
    {
        print '<?xml version="1.0" encoding="UTF-8"?>';
        print '
          <rows><head><afterInit><call command="clearAll"/></afterInit></head>
          <row id="scheduler" open="1"><cell image="wa/spooler.png"><b>No record </b></cell>
          </row></rows>';
        exit();
    }
    public function explodeTree($array, $delimiter = '_', $baseval = false)
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

?>
