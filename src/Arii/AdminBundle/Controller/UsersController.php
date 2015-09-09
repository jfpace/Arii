<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Arii\CoreBundle\Entity\TeamFilter;

class UsersController extends Controller {
    
    public function indexAction()
    {
        return $this->render('AriiAdminBundle:Users:index.html.twig');
    }
    
    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Users:menu.xml.twig", array(), $response);
    }
    
    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiAdminBundle:Users:toolbar.xml.twig", array(), $response);
    }

    public function treegridAction(){
      
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterprise();
        
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $Infos = array(); // array of users who have a team
        $team_infos = array();
        //$Infos_u = array(); // array of users who don't have a team
        $key_files = array();
        
        $sql = $this->container->get('arii_core.sql');
        
        $qry = $sql->Select(array("u.id as user_id","u.username","u.email","u.last_login","u.last_name","u.first_name","u.roles",
                                    "t.id as team_id","t.name as team_name",
                                    "e.enterprise"))
                .$sql->From(array('ARII_TEAM t'))
                .$sql->LeftJoin('ARII_USER u',array('u.team_id','t.id'))
                .$sql->LeftJoin('ARII_ENTERPRISE e',array('t.enterprise_id','e.id'))
                .$sql->Where(array('{enterprise}' => 'e.enterprise'))
                .' UNION '
                .$sql->Select(array("u.id as user_id","u.username","u.email","u.last_login","u.last_name","u.first_name","u.roles",
                                    "t.id as team_id","t.name as team_name",
                                    "e.enterprise"))
                .$sql->From(array('ARII_USER u'))
                .$sql->LeftJoin('ARII_TEAM t',array('u.team_id','t.id'))
                .$sql->LeftJoin('ARII_ENTERPRISE e',array('u.enterprise_id','e.id'))
                .$sql->Where(array('{enterprise}' => 'e.enterprise'));

     
        $res = $data->sql->query($qry);
        while ($line = $data->sql->get_next($res)){
            if ($line['enterprise'] != '') {
                $enterprise =  $line['enterprise'];
            }
            else {
                $enterprise = 'local';
            }
            if( strpos((string)$line['roles'],"ROLE_USER")) 
                $roles = "ROLE_USER";
            if( strpos((string)$line['roles'],"ROLE_ADMIN")) 
                $roles = "ROLE_ADMIN";
            elseif (strpos((string)$line['roles'],"ROLE_SUPER_ADMIN"))
                $roles = "ROLE_SUPER_ADMIN";
            elseif (strpos((string)$line['roles'],"ROLE_OPERATOR"))
                $roles = "ROLE_OPERATOR";
            elseif (strpos((string)$line['roles'],"ROLE_DEVELOPER"))
                $roles = "ROLE_DEVELOPER";
            else
                $roles = "";                

            if ($line['team_name'] == "" && $line['team_id'] == "" && $line['username'] != "")
            {
                $un =  $enterprise.'/'.$line['username'];
                $Infos[$tun]['roles'] = $roles;
                foreach (array('username','first_name','last_name','email','last_login','team_name') as $k) {
                    $Infos[$tun][$k] = $line[$k];
                }   
                $key_files[$un] = $un;
            }
            $tn =  $enterprise.'/'.$line['team_id'];
            $team_infos[$tn]['team_name'] = $line['team_name'];
            $key_files[$tn] = $tn;
            $tun =  $enterprise.'/'.$line['team_id'].'/'.$line['username'];//the key of users enterprise/team/username  
            $Infos[$tun]['roles'] = $roles;
            foreach (array('username','user_id','first_name','last_name','email','last_login','team_name') as $k) {
                $Infos[$tun][$k] = $line[$k];
            }
            $key_files[$tun] = $tun;
        }
        
        $tree = $this->explodeTree($key_files, "/");
        
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
        print $this->show_allXML( $tree,'', $Infos, $team_infos );
        print "</rows>\n";
        exit();
    }
    
    function show_allXML($tree,$id='', $Infos, $team_infos)
    {
        if(is_array($tree)){
            foreach (array_keys($tree) as $k)
            {
                $Ids = explode('/', $k);
                $here = array_pop($Ids);
                $i = substr("$id/$k",1);
                if(isset($Infos[$i]))
                {
                    $cell = "";
                    $info = $Infos[$i];
                    print '<row id="U#'.$info['user_id'].'">';
                    $cell .= '<userdata name="type">user</userdata>';
                    $cell .= '<userdata name="team">'.$info['team_name'].'</userdata>';
                    $roles = $info['roles'];
                    if ($roles=='')
                        $icon = 'role_unknown';
                    else 
                        $icon = strtolower($roles);
                    $cell .= '<cell image="'.$icon.'.png"> '.$info['username'].'</cell>';
                    $cell .= '<cell>'.$info['last_name'].'</cell>';
                    $cell .= '<cell>'.$info['first_name'].'</cell>';
                    $cell .= '<cell>'.$info['email'].'</cell>';
                    $cell .= '<cell>'.$info['last_login'].'</cell>';
                    $cell .= '<cell>'.$this->get('translator')->trans($roles).'</cell>';
                    print $cell;
                }  elseif (isset($team_infos[$i]))
                {
                    $cell = "";
                    $info = $team_infos[$i];
                    print '<row id="T#'.$here.'" open="1">';
                    $cell .= '<userdata name="type">team</userdata>';
                    $cell .= '<cell image="team.png">'.$info['team_name'].'</cell>';
                    print $cell;
                }
                else{
                    print '<row id="'.$i.'" open="1">';
                    if($id == '')
                    {
                        print '<userdata name="type">enterprise</userdata>';
                        print '<cell image="enterprise.png"> '.$here.'</cell>';
                    }
                }
                $this->show_allXML($tree[$k],$id.'/'.$k,$Infos,$team_infos);
                print '</row>';
            }
        }
    }

    public function NoRecord()
    {
        print '<?xml version="1.0" encoding="UTF-8"?>';
        print '
          <rows><head><afterInit><call command="clearAll"/></afterInit></head>
          <row id="scheduler" open="1"><cell><b>No record </b></cell>
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
