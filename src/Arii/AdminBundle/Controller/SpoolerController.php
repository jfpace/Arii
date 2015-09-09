<?php

namespace Arii\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class SpoolerController extends Controller {

    public function indexAction()
    {
        return $this->render('AriiAdminBundle:Spooler:index.html.twig');
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiAdminBundle:Spooler:toolbar.xml.twig', array(), $response);
    }

    
    public function supervisorsAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterpriseId();
        $qry = "SELECT spooler.id,spooler.name 
                FROM ARII_SPOOLER spooler
                LEFT JOIN ARII_SITE site
                ON spooler.site_id=site.id
                WHERE site.enterprise_id=".$enterprise." order by spooler.name"  ;
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,name");
    }
    
    public function sitesAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterpriseId();
        
        $qry = "SELECT site.id,site.name 
                FROM ARII_SITE site
                WHERE site.enterprise_id=".$enterprise." order by site.name";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,name");
    }
    
    public function transferAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterpriseId();
        
        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='2' and ac.enterprise_id=".$enterprise." order by ac.title";
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,title");
    }
    
    public function backupAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterpriseId();
        
        $qry = "SELECT asp.id,asp.name FROM ARII_SPOOLER asp
                WHERE asp.site_id in (SELECT site.id FROM ARII_SITE site WHERE site.enterprise_id='$enterprise')";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,'id',"id, name");
    }
    
    public function mailAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterpriseId();

        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='4' and ac.enterprise_id=".$enterprise." order by ac.title";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,title");
    }
    
    public function repositoriesAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise =$session->getEnterpriseId();

        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='1' and ac.enterprise_id=".$enterprise." order by ac.title";
        
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
    
     public function httpAction()
    {
        $qry = "SELECT ac.id,ac.title FROM ARII_CONNECTION ac
                LEFT JOIN ARII_NETWORK an
                ON ac.network_id=an.id
                WHERE an.category_id='5'";
        
        $db = $this->container->get('arii_core.db');
        $select = $db->Connector('select');
        $select->render_sql($qry,"id","id,title");
    }
    
	 public function deleteAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        /*
        $em = $this->getDoctrine()->getManager();
        if( $id!="" )
        {
            $spooler = $this->getDoctrine()->getRepository("AriiCoreBundle:Spooler")->find($id);
            $connection = $spooler->getConnection();
            $em->remove($spooler);
            $em->flush();
            if($connection != null){
                $em->remove($connection);
                $em->flush();
            }
            return new Response('success');
        } else 
        {
            return new Response("Error: Can not find a id for deleting!");
        }
         * 
         */
        
        if ($id != ""){
            $db = $this->container->get('arii_core.db');
            $data = $db->Connector('data');
            $qry = "UPDATE ARII_SPOOLER SET supervisor_id=null WHERE supervisor_id='$id'";
            $data->sql->query($qry);
            
            $qry = "UPDATE ARII_SPOOLER SET primary_id=null WHERE primary_id='$id'";
            $data->sql->query($qry);
            
            $em = $this->getDoctrine()->getManager();
            $spooler = $this->getDoctrine()->getRepository("AriiCoreBundle:Spooler")->find($id);
            $connection = $spooler->getConnection();
            $em->remove($spooler);
            $em->flush();
            if($connection != null){
                $em->remove($connection);
                $em->flush();
            }
            
            return new Response('success');
        }
        else 
        {
            return new Response("Error: Can not find a id for deleting!");
        }
    }
	
    public function saveAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $session = $this->container->get('arii_core.session');
        $enterprise_id = $session->getEnterpriseId();
        $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->find($enterprise_id);
        $em = $this->getDoctrine()->getManager();
        
        if( $id!="" )
        {
            $spooler = $this->getDoctrine()->getRepository("AriiCoreBundle:Spooler")->find($id);
            $connection = $spooler->getConnection();
            $connection->setTitle($request->get('scheduler'));
            $connection->setDescription($request->get('spooler_description'));
            $connection->setHost($request->get('host'));
            $connection->setInterface($request->get('ip'));
            $connection->setPort($request->get('port'));
            $em->persist($connection);
            $em->flush();
            $spooler->setConnection($connection);
        } else 
        {
            $spooler = new \Arii\CoreBundle\Entity\Spooler();
            $connection = new \Arii\CoreBundle\Entity\Connection();
            $connection->setEnterprise($enterprise);
            $connection->setTitle($request->get('scheduler'));
            $connection->setDescription($request->get('spooler_description'));
            $connection->setHost($request->get('host'));
            $connection->setInterface($request->get('ip'));
            $connection->setPort($request->get('port'));
            $network_spooler = $this->getDoctrine()->getRepository("AriiCoreBundle:Network")->findOneBy(array('protocol'=>'osjs'));
            $connection->setNetwork($network_spooler);
            $em->persist($connection);
            $em->flush();
            $spooler->setConnection($connection);
            $spooler->setClusterOptions('-exclusive');
            $spooler->setOsTarget(\php_uname('s'));
            $spooler->setLicence('');
            $spooler->setInstallDate(new \DateTime());
            $spooler->setStatusDate(new \DateTime());
        }
        if ($request->get('name')=="")
        {
            $spooler->setName($request->get('scheduler'));
        } else 
        {
            $spooler->setName($request->get('name'));
        }
        $spooler->setDescription($request->get('spooler_description'));
        $spooler->setScheduler($request->get('scheduler'));
        
        if ($request->get('supervisor_id')!="")
        {
            $supervisor = $this->getDoctrine()->getRepository("AriiCoreBundle:Spooler")->find($request->get('supervisor_id'));
            $spooler->setSupervisor($supervisor);
        }
        
        if ($request->get('primary_id')!="")
        {
            $backup = $this->getDoctrine()->getRepository("AriiCoreBundle:Spooler")->find($request->get('primary_id'));
            $spooler->setPrimary($backup);
        }
        
        if ($request->get('site_id')!="")
        {
            $site = $this->getDoctrine()->getRepository("AriiCoreBundle:Site")->find($request->get('site_id'));
            $spooler->setSite($site);
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
        
        $spooler->setTimezone($request->get('timezone'));
        $spooler->setInstallPath($request->get('install_path'));
        $spooler->setUserPath($request->get('user_path'));
        $spooler->setVersion($this->container->getParameter('osjs_version'));
        $spooler->setEvents($request->get('events'));
        $spooler->setRemote($request->get('remote'));
        $em->persist($spooler);
        $em->flush();
        
        return new Response("success");
        
    }
    
    public function formAction()
    {
        $db = $this->container->get('arii_core.db');
        $form = $db->Connector('form');
        $qry = "SELECT sp.id,sp.scheduler,sp.name,sp.description as spooler_description,sp.supervisor_id,sp.site_id,sp.smtp_id,sp.db_id,sp.primary_id,sp.connection_id,sp.events,sp.status,sp.timezone,sp.install_path,sp.user_path,sp.remote,ac.host,ac.interface as ip,ac.port
                FROM ARII_SPOOLER sp
                LEFT JOIN ARII_CONNECTION ac
                ON sp.connection_id=ac.id";
        $form->render_sql($qry,"sp.id","id,scheduler,name,spooler_description,supervisor_id,site_id,smtp_id,primary_id,db_id,events,status,timezone,install_path,user_path,remote,host,ip,port");
    }
    
    
}

?>
