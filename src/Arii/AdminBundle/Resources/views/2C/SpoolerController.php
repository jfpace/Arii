<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class SpoolerController extends Controller {
    
    public function supervisor_listAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterprise();
        $qry = "SELECT spooler.id,spooler.scheduler FROM ARII_ENTERPRISE enterprise
                LEFT JOIN ARII_SITE site
                ON enterprise.id=site.enterprise_id
                LEFT JOIN ARII_SPOOLER spooler
                ON site.id=spooler.site_id
                WHERE enterprise.enterprise='$enterprise'";
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,scheduler");
    }
    
	public function menuAction()
    {
        return $this->render("AriiAdminBundle:Spooler:menu.html.twig");
    }
	
    public function site_listAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterprise();
        
        $qry = "SELECT site.id,site.enterprise_id,site.name FROM ARII_SITE site
                LEFT JOIN ARII_ENTERPRISE e
                ON e.id = site.enterprise_id
                WHERE e.enterprise='$enterprise'";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,name");
    }
    
    public function transfer_listAction()
    {
        
        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='2'";
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,title");
    }
    
    public function mail_listAction()
    {
        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='4'";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,title");
    }
    
    public function db_listAction()
    {
        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='1'";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,title");
    }
    
    public function show_informationAction()
    { //# this is a test for rendering a whole form directly from controller to template, not use a select connector every time
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterprise();
        
        $qry = "SELECT site.id,site.enterprise_id,site.name FROM ARII_SITE site
                LEFT JOIN ARII_ENTERPRISE e
                ON e.id = site.enterprise_id
                WHERE e.enterprise='$enterprise'";
        $res = $data->sql->query($qry);
        $site = array();
        while ($line = $data->sql->get_next($res))
        {
            $site_info = array(
                'text'=>$line['name'],
                'value'=>$line['id']
            );
            array_push($site,$site_info);
        }
        
        $form = "";
        $form .= '{
                    type: "select",
                    name: "select",
                    label: "Select",
                    options: [ { text: "", value: "" },';
        foreach ($site as $s)
        {
            $text = $s['text'];
            $value = $s['value'];
            $form .= '{ text: "'.$text.'", value: "'.$value.'" },';
        }
        $form .= ']}';
        
        return new Response($form);
    }
    
    public function connection_listAction()
    {
        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='3'";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,title");
    }
    
     public function http_listAction()
    {
        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='5'";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,title");
    }
    
    public function save_spoolerAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $spooler = new \Arii\CoreBundle\Entity\Spooler();
        
        if( $id!="" )
        {
            $spooler = $this->getDoctrine()->getRepository("AriiCoreBundle:Spooler")->find($id);
        }
        /*
        $supervisor = $this->getDoctrine()->getRepository("AriiCoreBundle:Spooler")->find($request->get('supervisor_id'));
        $site = $this->getDoctrine()->getRepository("AriiCoreBundle:Site")->find($request->get('site_id'));
        $transfer = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('transfer_id'));
        $smtp = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('smtp_id'));
        $db = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('db_id'));
        $connection = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('connection_id'));
        $http = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('http_id'));
        $https = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('https_id'));
        
        $spooler->setSchedulerId($request->get('scheduler'));
        $spooler->setDb($db);
        $spooler->setSmtp($smtp);
        $spooler->setEvents($request->get('events'));
        $spooler->setConnection($connection);
        $spooler->setTransfer($transfer);
        $spooler->setSupervisor($supervisor);
        $spooler->setVersion($request->get('version'));
        $spooler->setStatus($request->get('status'));
        $spooler->setHttp($http);
        $spooler->setSite($site);
        $spooler->setHttps($https);
        
         * 
         */
        
        if ($request->get('supervisor_id')!="")
        {
            $supervisor = $this->getDoctrine()->getRepository("AriiCoreBundle:Spooler")->find($request->get('supervisor_id'));
            $spooler->setSupervisor($supervisor);
        }
        
        if ($request->get('site_id')!="")
        {
            $site = $this->getDoctrine()->getRepository("AriiCoreBundle:Site")->find($request->get('site_id'));
            $spooler->setSite($site);
        }
        
        if ($request->get('transfer_id')!="")
        {
            $transfer = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('transfer_id'));
            $spooler->setTransfer($transfer);
        }
        
        if ($request->get('smtp_id')!="")
        {
            $smtp = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('smtp_id'));
            $spooler->setSmtp($smtp);
        }
        
        if ($request->get('db_id')!="")
        {
            $db = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('db_id'));
            $spooler->setDb($db);
        }
        
        if ($request->get('connection_id')!="")
        {
            $connection = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('connection_id'));
            $spooler->setConnection($connection);
        }
        if ($request->get('http_id')!="")
        {
            $http = $this->getDoctrine()->getRepository("AriiCoreBundle:Connection")->find($request->get('http_id'));
            $spooler->setHttp($http);
        }
        
        $spooler->setStatus($request->get('status'));
        $spooler->setVersion($request->get('version'));
        $spooler->setEvents($request->get('events'));
        $spooler->setSchedulerId($request->get('scheduler'));
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($spooler);
        $em->flush();
        
        return new Response("success");
        
    }
    
    public function edit_spoolerAction()
    {
        $db = $this->container->get('arii_core.db');
        $form = $db->Connector('form');
        $form->render_table("ARII_SPOOLER","id","id,scheduler,supervisor_id,site_id,transfer_id,smtp_id,db_id,connection_id,http_id,events,version,status");
    }
    
    public function show_spoolerAction()
    {     
        
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterprise();
        
        $roles = "";
        if ($this->get('security.context')->isGranted("ROLE_SUPER_ADMIN"))
        {
            $roles = "ROLE_SUPER_ADMIN";
        }
        $filter = "e.enterprise='$enterprise'";
        if($roles == "ROLE_SUPER_ADMIN") //  && $enterprise == "SOS-PARIS"
        {
            $filter = "1";
        }
        
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $Infos = array();
        $key_files = array();
        
        $qry = "SELECT e.enterprise,s.name as site,sp.id as spooler_id,sp.scheduler as spooler,sp.supervisor_id,sp.db_id,sp.events,sp.status,sp.version,c.host,c.port,c1.title as db,c1.id
                FROM ARII_ENTERPRISE e
                LEFT JOIN ARII_SITE s
                ON s.enterprise_id=e.id
                LEFT JOIN ARII_SPOOLER sp
                ON sp.site_id=s.id
                LEFT JOIN ARII_CONNECTION c
                ON sp.connection_id=c.id        
                LEFT JOIN ARII_CONNECTION c1
                ON sp.db_id=c1.id                 
                WHERE ".$filter;
        
        
        $res = $data->sql->query($qry);
        $Spooler_info = array();
        while ($line = $data->sql->get_next($res))
        {
            $sun = $line['enterprise'].'/'.$line['site'].'/'.$line['db'].'/'.$line['spooler_id'];
            if ($line['db'] == "" && $line['spooler_id'] != "")
            {
                $sun = $line['enterprise'].'/'.$line['site'].'/'."NO DB".'/'.$line['spooler_id'];
            }
                   
            $Spooler_info[$line['spooler_id']]= $line['spooler'].'|'.$line['events'].'|'.$line['status'].'|'.$line['version'].'|'.$line['host'].'|'.$line['port'];
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
        print $this->show_spoolerXML( $tree,'', $Infos, $Spooler_info );
        print "</rows>\n";
        exit();
    }
    
    public function show_spoolerXML($tree, $id ="",$Infos, $Spooler_info )
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
                    list($sp,$events,$status,$version,$host,$port) = explode('|', $Spooler_info[$here]);
                    if($supervisor_id == null)
                    {
                        $supervisor = null;
                    } else{
                        list($supervisor) = explode('|', $Spooler_info[$supervisor_id]);
                    }
                    print '<row id="'.$here.'">';
					$cell .= '<userdata name="type">spooler</userdata>';
                    $cell .= '<cell image="user.png"> '.$spooler.'</cell>';
                    $cell .= '<cell>'.$supervisor.'</cell>';
                    $cell .= '<cell>'.$host.'</cell>';
                    $cell .= '<cell>'.$port.'</cell>';
                    $cell .= '<cell>'.$events.'</cell>';
                    $cell .= '<cell>'.$version.'</cell>';
                    $cell .= '<cell>'.$status.'</cell>';
                    print $cell;
                } else {
                    print '<row id="'.$i.'" open="1">';
                    if ($id == "")
                    {
						print '<userdata name="type">enterprise</userdata>'; 
                        print '<cell image="enterprise.png"> '.$here.'</cell>';
                    } else {
                        print '<cell image="team.png">  '.$here.'</cell>';
                    }
                }
                $this->show_spoolerXML($tree[$k],$id.'/'.$k, $Infos, $Spooler_info);
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
