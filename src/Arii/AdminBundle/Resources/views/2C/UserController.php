<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Arii\CoreBundle\Entity\TeamFilter;


class UserController extends Controller {
    public function toolbar_indexAction()
    {
        return $this->render("AriiAdminBundle:User:index_toolbar.html.twig");
    }
    public function toolbar_managementAction()
    {
        return $this->render("AriiAdminBundle:User:management_toolbar.html.twig");
    }
    
    public function toolbar_management_addAction()
    {
        return $this->render("AriiAdminBundle:User:management_toolbar_add.html.twig");
    }
    
    public function user_managementAction(){
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterprise();
        return $this->render("AriiAdminBundle:User:user_management.html.twig",array('enterprise'=>$enterprise));
    }
    
    public function edit_userAction()
    {   
        $db = $this->container->get('arii_core.db');
        $form = $db->Connector('form');
        $form->render_table("ARII_USER","username","id,username,email,first_name,last_name,team_id,enterprise_id");  
    }
    
    public function select_enterpriseAction()
    {
        $db = $this->container->get("arii_core.db");
        $select = $db->Connector('select');
        $select->render_table("ARII_ENTERPRISE",'id',"id,enterprise");
    }
    
    public function select_teamAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise_name = $session->getEnterprise();
        $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->findOneBy(array('enterprise'=>$enterprise_name));
        $enterprise_id = $enterprise->getId();
        
        $db = $this->container->get("arii_core.db");
        $select = $db->Connector('select');
        $qry = "SELECT id,name FROM ARII_TEAM WHERE enterprise_id=$enterprise_id";
        $select->render_sql($qry,'id','id,name');
    }
    
    public function edit_teamAction()
    {
        $db = $this->container->get('arii_core.db');
        $form = $db->Connector('form');
        $form->render_table("ARII_TEAM","id","id,name,description");
    }
    
    public function attach_filterAction()
    {
        return $this->render("AriiAdminBundle:User:attach_filter.html.twig");
    }
    
    public function attach_filter_toolbarAction()
    {
        return $this->render("AriiAdminBundle:User:attach_filter_toolbar.html.twig");
    }
    
    public function ajax_addFilterAction()
    {
        $request = $this->getRequest();
        $team_id = $request->get('team_id');
        $filter_id = $request->get('filter_id');
        
        $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($team_id);
        $filter = $this->getDoctrine()->getRepository("AriiCoreBundle:Filter")->find($filter_id);

        $team_filter = new TeamFilter();
        $team_filter->setTeam($team);
        $team_filter->setFilter($filter);
        $team_filter->setName($filter->getFilter());
        $team_filter->setDescription("");
        $team_filter->setR(false);
        $team_filter->setW(false);
        $team_filter->setX(false);
        
        $em = $this->getDoctrine()->getManager();
        try {
            $em->persist($team_filter);
        } catch (Exception $e)
        {
            throw new Exception("Something wrong", 0, $e);
            return new Response("error");
        }
        
        $em->flush();       
        return new Response("success");
    }
    
    public function edit_filterAction()
    {
        $request = $this->getRequest();
        $filter_id = $request->get('filter_id');

        $db = $this->container->get('arii_core.db');
        
        $qry = "SELECT '0' as id,team_id,filter_id,name,description,R,W,X FROM ARII_TEAM_FILTER where filter_id='$filter_id'";
        $form = $db->Connector('form');
        $form->render_sql($qry,"team_id","team_id,filter_id,name,description,R,W,X");
    }
    
    public function delete_filterAction()
    {
        $request = $this->getRequest();
        $filter_id = $request->get('filter_id');
        $team_id = $request->get('team_id');
        $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($team_id);
        $filter = $this->getDoctrine()->getRepository("AriiCoreBundle:Filter")->find($filter_id);
        
        $team_filter = $this->getDoctrine()->getRepository("AriiCoreBundle:TeamFilter")->findOneBy(array('filter'=>$filter,'team'=>$team));
        
        $em = $this->getDoctrine()->getManager();
        $em->remove($team_filter);
        $em->flush();
        
        return new Response("success");
    }
    
    public function save_filterAction()
    {
        $request = Request::createFromGlobals();
        $team_id = $request->get('team_id');
        $filter_id = $request->get('filter_id');
        
        $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($team_id);
        $filter = $this->getDoctrine()->getRepository("AriiCoreBundle:Filter")->find($filter_id);
        
        $team_filter = $this->getDoctrine()->getRepository("AriiCoreBundle:TeamFilter")->findOneBy(array('team'=>$team,'filter'=>$filter));
        
        $team_filter->setName($request->get('name'));
        $team_filter->setDescription($request->get('description'));
        $team_filter->setR($request->get('R'));
        $team_filter->setW($request->get('W'));
        $team_filter->setX($request->get('X'));
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($team_filter);
        $em->flush();
        
        return new Response("success");
    }
    
    public function pre_insert($data)
    {
        $data->setValue("team_filter",$data->getValue('team_id').'/'.$data->getValue('filter_id'));
    }
    
    
    public function userProcessorAction()
    {
        $db = $this->container->get('arii_core.db');
        $grid = $db->Connector('grid');
        $grid->render_table('ARII_USER');
    }
    
    public function delete_teamAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $db = $this->container->get("arii_core.db");
        $data = $db->Connector('data');
        $qry = "DELETE FROM ARII_TEAM_FILTER WHERE team_id=$id";
        $res = $data->sql->query($qry);
        
        $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($id);
        $em = $this->getDoctrine()->getManager();
        $em->remove($team);
        $em->flush();
        
        $qry = "UPDATE ARII_USER SET team_id='' WHERE team_id=$id";
        $res = $data->sql->query($qry);
        
        return new Response("success");
    }
    
    public function save_teamAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $session = $this->container->get('arii_core.session');
        $enterprise_name = $session->getEnterprise();
        $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->findOneBy(array('enterprise'=>$enterprise_name));
        
        $team = new \Arii\CoreBundle\Entity\Team();
        if ($id!="")
        {
            $team = $this->getDoctrine()->getRepository("AriiCoreBundle:Team")->find($id);
        }
        $team->setName($request->get('name'));
        $team->setDescription($request->get('description'));
        $team->setEnterprise($enterprise);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($team);
        $em->flush();
        
        return new Response("success");
    }
    
    public function delete_userAction()
    {
        $request = Request::createFromGlobals();
        $username = $request->get('id');
        
        $user_manager = $this->container->get('fos_user.user_manager');
        $user = $user_manager->findUserByUsername($username);
        
        $user_manager->deleteUser($user);
        
        return new Response("success");
    }
    
    public function save_userAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $role = $request->get('roles');
        
        //$enterprise = $this->getDoctrine()->getRepository('AriiCoreBundle:Enterprise')->findOneBy(array('enterprise'=>$enterprise_name));
        //$enterprise_id = $enterprise->getId();
        
        $team_id = $request->get('team_id');
        if ($team_id != "")
        {
            //$team = $this->getDoctrine()->getRepository('AriiCoreBundle:Team')->findOneBy(array('enterprise'=>$enterprise_id,'id'=>$team_id));
            $team = $this->getDoctrine()->getRepository('AriiCoreBundle:Team')->findOneBy(array('id'=>$team_id));
        } else {
            $team = null;
        }
        
        $enterprise_id = $request->get('enterprise_id');
        if ($enterprise_id != "")
        {
            $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->find($enterprise_id);
        } else
        {   
            $session = $this->container->get('arii_core.session');
            $enterprise_name = $session->getEnterprise();    
            $enterprise = $this->getDoctrine()->getRepository('AriiCoreBundle:Enterprise')->findOneBy(array('enterprise'=>$enterprise_name));
        }
        
        $user_manager = $this->container->get('fos_user.user_manager');
        $user = $user_manager->createUser();
        if ($id!="")
        {
            $user = $user_manager->findUserBy(array('id'=>$id));
        }

        $manipulator = $this->container->get('fos_user.util.user_manipulator');
 
;       $user->setUsername($request->get('username'));
        $user->setEmail($request->get('email'));
        if ($id == "")
        {
            $user->setPlainPassword("123456");
        }
        $user->setFirstName($request->get('first_name'));
        $user->setLastName($request->get('last_name'));
        $user->setTeam($team);
        $user->setEnterprise($enterprise);
        //$user->setEnterprise($enterprise);
        //### check the roles, if role is SUPER_ADMIN, then no matter $role is, we do nothing
        //### if role is ADMIN, then if $role is SUPER_ADMIN, change it from ADMIN to SUPER_ADMIN; if $role is ADMIN or OTHER, then we do nothing
        //### if role is OTHER, then if $role is OTHER, we do nothing

        $roles = array($role);
        $user->setRoles($roles);

        try {
            $user_manager->updateUser($user);
        } catch(Exception $e)
        {
            return new Response("something");
        }
        $manipulator->activate($request->get('username'));
                       
        return new Response("success");
    }
    
    public function dragAction()
    {
        $request = Request::createFromGlobals();
        $team_id = $request->get('team_id');
        $username = $request->get('username');
     
        $user_manager = $this->container->get('fos_user.user_manager');
        $user = $user_manager->findUserByUsername($username);
        
        $team = $this->getDoctrine()->getRepository('AriiCoreBundle:Team')->find($team_id);
        $enterprise = $team->getEnterprise();
        $user->setTeam($team);
        $user->setEnterprise($enterprise);
        
        $user_manager->updateUser($user);
        return new Response("success");
    }
    
    public function show_allAction(){
        
      
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterprise();
        
        $roles = "";
        if ($this->get('security.context')->isGranted("ROLE_SUPER_ADMIN"))
        {
            $roles = "ROLE_SUPER_ADMIN";
        }
        $filter = "e.enterprise='$enterprise'"; // if user is admin of one company, then it can show all the users according to the enterprise
        if ($roles == "ROLE_SUPER_ADMIN") // && $enterprise == "SOS-PARIS"
        {
            $filter = "1"; // if user is super admin, then it can show all the users of all the companies
        }
        
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $Infos = array(); // array of users who have a team
        $team_infos = array();
        //$Infos_u = array(); // array of users who don't have a team
        $key_files = array();
        /*
        $qry = "SELECT u.username,u.email,u.last_login,u.last_name,u.first_name,u.roles,u.team_id,t.name as team_name,e.enterprise
                FROM ARII_USER u
                LEFT JOIN ARII_TEAM t
                ON u.team_id=t.id
                LEFT JOIN ARII_ENTERPRISE e
                ON u.enterprise_id=e.id
                WHERE ".$filter;
        
         * 
         */
        
        $qry = "SELECT u.id as user_id,u.username,u.email,u.last_login,u.last_name,u.first_name,u.roles,t.id as team_id,t.name as team_name,e.enterprise
                FROM ARII_TEAM t
                LEFT JOIN ARII_USER u
                ON u.team_id=t.id
                LEFT JOIN ARII_ENTERPRISE e
                ON t.enterprise_id=e.id
                WHERE $filter
                UNION
                SELECT u.id as user_id,u.username,u.email,u.last_login,u.last_name,u.first_name,u.roles,t.id as team_id,t.name as team_name,e.enterprise
                FROM ARII_USER u
                LEFT JOIN ARII_TEAM t
                ON u.team_id=t.id
                LEFT JOIN ARII_ENTERPRISE e
                ON u.enterprise_id=e.id
                WHERE $filter ";
        
        
        $res = $data->sql->query($qry);
        while ($line = $data->sql->get_next($res)){
            $roles = "OTHER";
            if(strpos((string)$line['roles'],"ROLE_ADMIN"))
            {
                $roles = "ADMIN";
            } elseif(strpos((string)$line['roles'],"ROLE_SUPER_ADMIN"))
            {
                $roles = "SUPER_ADMIN";
            }
            if ($line['team_name'] == "" && $line['team_id'] == "" && $line['username'] != "")
            {
                $un = $line['enterprise'].'/'.$line['username'];
                $Infos[$un] = $line['username'].'|'.$line['email'].'|'.$line['last_login'].'|'.$roles;
                $key_files[$un] = $un;
            }
            $tn = $line['enterprise'].'/'.$line['team_id'];
            $team_infos[$tn] = $line['team_name'];
            $key_files[$tn] = $tn;
            $tun = $line['enterprise'].'/'.$line['team_id'].'/'.$line['username'];//the key of users enterprise/team/username  
            $Infos[$tun] = $line['username'].'|'.$line['email'].'|'.$line['last_login'].'|'.$roles;
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
    
    function show_allXML($tree,$id='', $Infos,$team_infos)
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
                    list($username,$email,$last_login,$roles) = explode('|',$Infos[$i]);
                    print '<row id="U#'.$here.'">';
                    $cell .= '<userdata name="type">user</userdata>';
                    $cell .= '<cell image="user.png"> '.$username.'</cell>';
                    $cell .= '<cell>'.$email.'</cell>';
                    $cell .= '<cell>'.$last_login.'</cell>';
                    $cell .= '<cell>'.$roles.'</cell>';
                    print $cell;
                }  elseif (isset($team_infos[$i]))
                {
                    $cell = "";
                    list($teamname) = explode('|',$team_infos[$i]);
                    print '<row id="T#'.$here.'" open="1">';
                    $cell .= '<userdata name="type">team</userdata>';
                    $cell .= '<cell image="team.png">'.$teamname.'</cell>';
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
    
    public function show_team_filterAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterprise();
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $qry = "SELECT t.id,t.name as team,tf.name as filter_name,tf.R,tf.W,tf.X,tf.team_id,tf.filter_id
                FROM ARII_TEAM t
                LEFT JOIN ARII_TEAM_FILTER tf
                ON t.id=tf.team_id
                LEFT JOIN ARII_ENTERPRISE e
                ON t.enterprise_id=e.id
                WHERE e.enterprise='$enterprise'";
        
        
        $res = $data->sql->query($qry);
        $Infos = array();
        $key_files = array();
        while ($line = $data->sql->get_next($res))
        {
            $tf = $line['team'].'/'.$line['filter_name'];
            $Infos[$tf] = $line['filter_name'].'|'.$line['R'].'|'.$line['W'].'|'.$line['X'].'|'.$line['team_id'].'|'.$line['filter_id'];
            $key_files[$tf] = $tf;
        }
       
        header("Content-type: text/xml");
        if (count($key_files)==0) {
            $this->NoRecord();
        }
        $tree = $this->explodeTree($key_files,'/');

        print '<?xml version="1.0" encoding="UTF-8"?>';
        print "<rows>\n";
        print '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        print $this->show_team_filterXML( $tree,'', $Infos);
        print "</rows>\n";
        exit();
        
    }
    
    public function show_team_filterXML($tree,$id="",$Infos)
    {
        if(is_array($tree)){
            foreach(array_keys($tree) as $k)
            {
                $ids = explode('/',$k);
                $here = array_pop($ids);
                $i = substr("$id/$k",1);
                if (isset($Infos[$i])){
                    $cell = "";
                    list($filter,$r,$w,$x,$team_id,$filter_id) = explode('|', $Infos[$i]);
                    print '<row id="F#'.$team_id.'/'.$filter_id.'">';
                    $cell .= '<cell> '.$filter.'</cell>';
                    $cell .= '<cell>'.$r.'</cell>';
                    $cell .= '<cell>'.$w.'</cell>';
                    $cell .= '<cell>'.$x.'</cell>';
                    print $cell;
                } 
               
                else {
                   if($id=='')
                    {
                        print '<row id="T#'.$here.'" open="1">';
                        print '<cell image="team.png"> '.$here.'</cell>';
                    }
                }
                 
                $this->show_team_filterXML($tree[$k],$id.'/'.$k,$Infos);
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
