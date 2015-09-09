<?php

namespace Arii\ConfigBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

class EnterpriseController extends Controller {
    
    public function newAction()
    {
        $os_target =  php_uname('s');
        $rootdir = $this->get('kernel')->getRootDir();
        $package = $rootdir;
        $Path = explode('/',$rootdir);
        array_pop($Path);
        array_pop($Path);
        $user_path = implode('/',$Path).'/jobschedulers';
        $install_path = $user_path;
        return $this->render("AriiConfigBundle:Enterprise:new.html.twig", 
                array( 
                    'rootdir'           => $rootdir,
                    'os_target'         =>  $os_target,
                    'user_path'         =>  $user_path,
                    'install_path'      =>  $install_path,
                    'timezone'          =>  date_default_timezone_get(),
                    'host'              =>  gethostname(),
                    'db_driver'         =>  $this->container->getParameter('database_driver'),
                    'db_host'           =>  $this->container->getParameter('database_host'),
                    'db_port'           =>  $this->container->getParameter('database_port'),
                    'db_name'           =>  $this->container->getParameter('database_name'),
                    'db_user'           =>  $this->container->getParameter('database_user'),
                    'db_password'       =>  $this->container->getParameter('database_password'),
                    'mailer_transport'  =>  $this->container->getParameter('mailer_transport'),
                    'mailer_host'       =>  $this->container->getParameter('mailer_host'),
                    'mailer_user'       =>  $this->container->getParameter('mailer_user'),
                    'mailer_password'   =>  $this->container->getParameter('mailer_password'),
                    'latitude'          =>  ini_get('date.default_latitude'),
                    'longitude'         =>  ini_get('date.default_longitude')
                    
                )
        );
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render("AriiConfigBundle:Enterprise:toolbar.xml.twig", array(), $response);
    }
        
    public function formAction()
    {
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('form');
        $data->render_table("ARII_ENTERPRISE","id","id,enterprise,modules");
    }
    
    public function deleteAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
       
        $db = $this->container->get("arii_core.db");
        $data = $db->Connector('data');
        
        $qry = "DELETE FROM ARII_TEAM_FILTER WHERE filter_id in ";
        $qry .= "(select id from ARII_FILTER where enterprise_id=$id)";
        $res = $data->sql->query($qry);

        $qry2 = "DELETE FROM ARII_TEAM_FILTER WHERE team_id in ";
        $qry2 .= "(select id from ARII_TEAM where enterprise_id=$id)";
        $res = $data->sql->query($qry2);
        
        $qry4 = "DELETE FROM ARII_USER_FILTER WHERE user_id in ";
        $qry4 .= "(select id from ARII_USER where enterprise_id=$id)";
        $res = $data->sql->query($qry4);

        $qry5 = "DELETE FROM ARII_USER_FILTER WHERE filter_id in ";
        $qry5 .= "(select id from ARII_FILTER where enterprise_id=$id)";
        $res = $data->sql->query($qry5);

        $qry18 = "DELETE FROM ARII_FILTER WHERE enterprise_id=$id";        
        $res = $data->sql->query($qry18);

        $qry6 = "DELETE FROM ARII_USER WHERE enterprise_id=$id";        
        $res = $data->sql->query($qry6);

        $qry3 = "DELETE FROM ARII_TEAM WHERE enterprise_id=$id";        
        $res = $data->sql->query($qry3);
        
        $qry7 = "DELETE FROM ARII_NOTIFICATION_NOTIFIER WHERE notification_id in ";        
        $qry7 .= "( select an.id from ARII_NOTIFICATION an left join ARII_TEAM at on an.team_id=at.id where at.enterprise_id=$id)";        
        $res = $data->sql->query($qry7);

        $qry8 = "DELETE FROM ARII_NOTIFICATION WHERE team_id in ";        
        $qry8 .= "( select id from ARII_TEAM where enterprise_id=$id)";        
        $res = $data->sql->query($qry8);

        $qry9 = "DELETE FROM ARII_NOTIFIER WHERE connection_id in ";        
        $qry9 .= "( select id from ARII_CONNECTION where enterprise_id=$id)";        
        $res = $data->sql->query($qry9);
        
        $qry10 = "DELETE FROM ARII_TEAM WHERE enterprise_id=$id";        
        $res = $data->sql->query($qry10);

        $qry17 = "DELETE FROM ARII_SPOOLER WHERE (not(isnull(supervisor_id)) and not(isnull(primary_id))) and site_id in ";        
        $qry17 .= "( select id from ARII_SITE site where enterprise_id=$id )";        
        $res = $data->sql->query($qry17);

        $qry11 = "DELETE FROM ARII_SPOOLER WHERE (not(isnull(supervisor_id)) or not(isnull(primary_id))) and site_id in ";        
        $qry11 .= "( select id from ARII_SITE site where enterprise_id=$id )";        
        $res = $data->sql->query($qry11);

        $qry17 = "DELETE FROM ARII_SPOOLER WHERE site_id in ";        
        $qry17 .= "( select id from ARII_SITE where enterprise_id=$id)";        
        $res = $data->sql->query($qry17);

        $qry12 = "DELETE FROM ARII_SITE WHERE enterprise_id=$id";        
        $res = $data->sql->query($qry12);

        $qry13 = "DELETE FROM ARII_REPOSITORY WHERE enterprise_id=$id";        
        $res = $data->sql->query($qry13);
        
        $qry14 = "DELETE FROM ARII_CONNECTION WHERE enterprise_id=$id";        
        $res = $data->sql->query($qry14);
        
        $qry15 = "DELETE FROM ARII_CONNECTION WHERE enterprise_id=$id";        
        $res = $data->sql->query($qry15);

        $qry16 = "DELETE FROM ARII_ENTERPRISE WHERE id=$id";        
        $res = $data->sql->query($qry16);
        
        return new Response("success");
    }
    
    public function saveAction()
    {
        Debug::enable();
        
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        
        $enterprise = new \Arii\CoreBundle\Entity\Enterprise();
        if($id!="")
        {
            $enterprise = $this->getDoctrine()->getRepository("AriiCoreBundle:Enterprise")->find($id);
        }
        $enterprise->setEnterprise($request->get('enterprise'));
        $enterprise->setModules($request->get('modules'));
        
        $em = $this->getDoctrine()->getManager();
        
        $em->persist($enterprise);
        $em->flush();
        
        return new Response("success");
    }
    
    public function createAction()
    {

        $request = Request::createFromGlobals();
        $em = $this->getDoctrine()->getManager();
        // $em->getConnection()->getConfiguration()->setSQLLogger( new \Doctrine\DBAL\Logging\EchoSQLLogger()); 
        // Complement
        $host = gethostname();
        
        // Entreprise
        $enterprise = new \Arii\CoreBundle\Entity\Enterprise();
        $enterprise->setEnterprise( $request->get('enterprise') );
        $enterprise->setModules( $request->get('modules') );              
        $enterprise->setTitle( $request->get('title') );              
        $enterprise->setMaildomain( $request->get('maildomain') );              
        $em->persist($enterprise);

        // Nouveau site
        $site = new \Arii\CoreBundle\Entity\Site();
        $site->setEnterprise($enterprise);
        $site->setName($request->get('site_name'));
        $site->setDescription($request->get('site_desc'));
        $site->setCountryCode($request->get('country_code'));
        $site->setAddress($request->get('address'));
        $site->setCity($request->get('city'));
        $site->setZipcode($request->get('zipcode'));
        $site->setLatitude($request->get('latitude'));
        $site->setLongitude($request->get('longitude'));
        $site->setTimezone($request->get('timezone'));
        $em->persist($site);

        // Nouvelle base de données
        $connection_db = new \Arii\CoreBundle\Entity\Connection();
        $connection_db->setEnterprise($enterprise);
        $connection_db->setTitle($request->get('db'));
        $connection_db->setDescription($request->get('db'));
        $connection_db->setHost($request->get('db_host'));
        $connection_db->setPort($request->get('db_port'));        
        $connection_db->setLogin($request->get('db_user'));        
        $connection_db->setPassword($request->get('db_password'));  
        $connection_db->setDatabase($request->get('db_name'));        
        // jointure
        //$network_db = new \Arii\CoreBundle\Entity\Network();
        $db_type = str_replace('pdo_','',$request->get('db_driver'));
        $network_db = $this->getDoctrine()->getRepository("AriiCoreBundle:Network")->findOneBy(array('protocol'=>$db_type));
        $connection_db->setNetwork($network_db);         
        $em->persist($connection_db);

        // Arii devient le repository de l'entreprise
        $repository = new \Arii\CoreBundle\Entity\Repository();
        $repository->setEnterprise($enterprise);
        $repository->setDb($connection_db);
        $repository->setName($request->get('db'));
        $repository->setDescription($request->get('db'));
        $repository->setTimezone('GMT');
        $em->persist($repository);
        
        // Arii est le supervisor mutualisé
        $connection_mail = new \Arii\CoreBundle\Entity\Connection();
        $connection_mail->setEnterprise($enterprise);
        $connection_mail->setTitle($request->get('mailer'));
        $connection_mail->setDescription('Mail');
        $connection_mail->setHost($request->get('mailer_host'));
        // Arii DB
        $connection_mail->setPort($request->get('mailer_port')+1);        
        $connection_mail->setLogin('');
        $connection_mail->setPassword(''); 

        // jointure
        $network_mail = $this->getDoctrine()->getRepository("AriiCoreBundle:Network")->findOneBy(array('protocol'=>'smtp'));
        $connection_mail->setNetwork($network_mail);
        $em->persist($connection_mail);

        // Nouveau jobscheduler
        $connection_supervisor = new \Arii\CoreBundle\Entity\Connection();
        $connection_supervisor->setEnterprise($enterprise);
        $connection_supervisor->setTitle($request->get('supervisor'));
        $connection_supervisor->setDescription($request->get('supervisor'));
        $connection_supervisor->setHost($request->get('spooler_ip'));
        // Arii DB
        $connection_supervisor->setPort($request->get('supervisor_port'));        
        $connection_supervisor->setLogin('');        
        $connection_supervisor->setPassword('');  
        
        // jointure
        //$network_db = new \Arii\CoreBundle\Entity\Network();
        $network_supervisor = $this->getDoctrine()->getRepository("AriiCoreBundle:Network")->findOneBy(array('protocol'=>'osjs'));
        $connection_supervisor->setNetwork($network_supervisor);
        $em->persist($connection_supervisor);

        $supervisor = new \Arii\CoreBundle\Entity\Spooler();
//        $supervisor->setScheduler($request->get('supervisor'));
//        $supervisor->setName($request->get('supervisor'));
//        $supervisor->setDescription($request->get('supervisor'));
        $supervisor->setSite($site);
        $supervisor->setConnection($connection_supervisor);
        $supervisor->setSmtp($connection_mail);
        $supervisor->setDb($connection_db);
        $supervisor->setInstallPath($request->get('install_path'));
        $supervisor->setUserPath($request->get('user_path'));         
        $supervisor->setEvents(false); 
        $supervisor->setClusterOptions(''); 
        $supervisor->setLicence(''); 
        $supervisor->setOsTarget(php_uname('s'));
        $supervisor->setInstallDate(new \DateTime());
        $supervisor->setStatus('INSTALLATION');
        $supervisor->setStatusDate(new \DateTime());
        $supervisor->setTimezone('GMT');
        $supervisor->setVersion($this->container->getParameter('osjs_version'));
        $supervisor->setName("Supervisor");// ModifyForm
        $supervisor->setDescription("this is the Supervisor spooler");// ModifyForm
        $supervisor->setScheduler("JobScheduler");// ModifyForm
        $em->persist($supervisor);
        // on peut faire un flush car tout ce qui est indique est deja installé
        $em->flush();

        //$em->flush();
        //return $this->render("AriiAdminBundle:Enterprise:dashboard.html.twig" );
        // Nouveau jobscheduler
        $connection_spooler = new \Arii\CoreBundle\Entity\Connection();
        $connection_spooler->setEnterprise($enterprise);
        $connection_spooler->setTitle($request->get('spooler'));
        $connection_spooler->setDescription($request->get('spooler'));
        $connection_spooler->setHost($request->get('spooler_ip'));
        // Arii DB
        $connection_spooler->setPort($request->get('spooler_port'));        
        $connection_spooler->setLogin('');        
        $connection_spooler->setPassword('');  
        // jointure
        //$network_db = new \Arii\CoreBundle\Entity\Network();
        $network_spooler = $this->getDoctrine()->getRepository("AriiCoreBundle:Network")->findOneBy(array('protocol'=>'osjs'));
        $connection_spooler->setNetwork($network_spooler);
        $em->persist($connection_spooler);
        $em->flush();
     
        $spooler = new \Arii\CoreBundle\Entity\Spooler();
        $spooler->setScheduler($request->get('spooler'));
        $spooler->setSite($site);
        $spooler->setConnection($connection_spooler);
        $spooler->setSupervisor($supervisor);
        $spooler->setSmtp($connection_mail);
        $spooler->setDb($connection_db);
        $spooler->setInstallPath($request->get('install_path'));
        $spooler->setUserPath($request->get('user_path'));         
        $spooler->setEvents(false); 
        $spooler->setClusterOptions('-exclusive'); 
        $spooler->setLicence(''); 
        $spooler->setOsTarget(php_uname('s'));
        $spooler->setInstallDate(new \DateTime());
        $spooler->setStatus('INSTALLATION');
        $spooler->setStatusDate(new \DateTime());
        $spooler->setTimezone('GMT');
        $spooler->setVersion($this->container->getParameter('osjs_version'));
        $spooler->setName("New Spooler"); // ModifyForm
        $spooler->setDescription("this is the new spooler");// ModifyForm
	$spooler->setScheduler("JobScheduler");// ModifyForm
        $em->persist($spooler);
        $em->flush();
       
        // Nouveau jobscheduler
        $connection_backup = new \Arii\CoreBundle\Entity\Connection();
        $connection_backup->setEnterprise($enterprise);
        $connection_backup->setTitle($request->get('spooler'));
        $connection_backup->setDescription($request->get('spooler'));
        $connection_backup->setHost($request->get('spooler_ip'));
        // Arii DB
        $connection_backup->setPort($request->get('spooler_port')+1);        
        $connection_backup->setLogin('');        
        $connection_backup->setPassword('');  
        // jointure
        //$network_db = new \Arii\CoreBundle\Entity\Network();
        $network_backup = $this->getDoctrine()->getRepository("AriiCoreBundle:Network")->findOneBy(array('protocol'=>'osjs'));
        $connection_backup->setNetwork($network_backup);
        $em->persist($connection_backup);
        $em->flush();
        
        $backup = new \Arii\CoreBundle\Entity\Spooler();
        $backup->setScheduler($request->get('spooler'));
        $backup->setPrimary($spooler);
        $backup->setSite($site);
        $backup->setConnection($connection_backup);
        $backup->setSupervisor($supervisor);
        $backup->setSmtp($connection_mail);
        $backup->setDb($connection_db);
        $backup->setInstallPath($request->get('install_path'));
        $backup->setUserPath($request->get('user_path'));         
        $backup->setEvents(false); 
        $backup->setClusterOptions(''); 
        $backup->setLicence(''); 
        $backup->setOsTarget(php_uname('s'));
        $backup->setInstallDate(new \DateTime());
        $backup->setStatus('INSTALLATION');
        $backup->setStatusDate(new \DateTime());
        $backup->setTimezone('GMT');
        $backup->setVersion($this->container->getParameter('osjs_version'));
        $backup->setName("New Spooler"); // ModifyForm
        $backup->setDescription("this is the new spooler");// ModifyForm
	$backup->setScheduler("JobScheduler");	// ModifyForm	
        $em->persist($backup);
        $em->flush();

        $team_admins = new \Arii\CoreBundle\Entity\Team();
        $team_admins->setEnterprise($enterprise);
        $team_admins->setName($request->get('administrators'));
        $team_admins->setDescription($request->get('administrators'));
        $em->persist($team_admins);

        $team_managers = new \Arii\CoreBundle\Entity\Team();
        $team_managers->setEnterprise($enterprise);
        $team_managers->setName($request->get('managers'));
        $team_managers->setDescription($request->get('managers'));
        $em->persist($team_managers);

        $team_users = new \Arii\CoreBundle\Entity\Team();
        $team_users->setEnterprise($enterprise);
        $team_users->setName($request->get('users'));
        $team_users->setDescription($request->get('users'));
        $em->persist($team_users);

        $team_ops = new \Arii\CoreBundle\Entity\Team();
        $team_ops->setEnterprise($enterprise);
        $team_ops->setName($request->get('operators'));
        $team_ops->setDescription($request->get('operators'));
        $em->persist($team_ops);

        $team_devs = new \Arii\CoreBundle\Entity\Team();
        $team_devs->setEnterprise($enterprise);
        $team_devs->setName($request->get('developpers'));
        $team_devs->setDescription($request->get('developpers'));
        $em->persist($team_devs);

        $user_admin = new \Arii\UserBundle\Entity\User();
        $user_admin->setEnterprise($enterprise);
        $user_admin->setTeam($team_admins);        
        $user_admin->setRoles(array('ROLE_ADMIN'));        
        $user_admin->setUsername($request->get('administrator').'@'.$request->get('maildomain'));
        $user_admin->setFirstname($request->get('administrator'));
        $user_admin->setLastname('');
        $user_admin->setEmail($request->get('administrator').'@'.$request->get('maildomain'));
        $user_admin->setPlainPassword($request->get('administrator'));
        $user_admin->setEnabled(true);
        $em->persist($user_admin);

        $user_manag = new \Arii\UserBundle\Entity\User();
        $user_manag->setEnterprise($enterprise);
        $user_manag->setTeam($team_managers);        
        $user_manag->setRoles(array('ROLE_MANAGER'));        
        $user_manag->setUsername($request->get('manager').'@'.$request->get('maildomain'));
        $user_manag->setFirstname($request->get('manager'));
        $user_manag->setLastname('');
        $user_manag->setEmail($request->get('manager').'@'.$request->get('maildomain'));
        $user_manag->setPlainPassword($request->get('manager'));
        $user_manag->setEnabled(true);
        $em->persist($user_manag);

        $user_ope = new \Arii\UserBundle\Entity\User();
        $user_ope->setEnterprise($enterprise);
        $user_ope->setTeam($team_ops);
        $user_ope->setRoles(array('ROLE_OPERATOR'));        
        $user_ope->setUsername($request->get('operator').'@'.$request->get('maildomain'));
        $user_ope->setFirstname($request->get('operator'));
        $user_ope->setLastname('');
        $user_ope->setEmail($request->get('operator').'@'.$request->get('maildomain'));
        $user_ope->setPlainPassword($request->get('operator'));
        $user_ope->setEnabled(true);
        $em->persist($user_ope);

        $user_dev = new \Arii\UserBundle\Entity\User();
        $user_dev->setEnterprise($enterprise);
        $user_dev->setTeam($team_devs);
        $user_dev->setRoles(array('ROLE_DEVELOPPER'));        
        $user_dev->setUsername($request->get('developper').'@'.$request->get('maildomain'));
        $user_dev->setFirstname($request->get('developper'));
        $user_dev->setLastname('');
        $user_dev->setEmail($request->get('developper').'@'.$request->get('maildomain'));
        $user_dev->setPlainPassword($request->get('developper'));
        $user_dev->setEnabled(true);
        $em->persist($user_dev);

        $user = new \Arii\UserBundle\Entity\User();
        $user->setEnterprise($enterprise);
        $user->setTeam($team_users);
        $user->setUsername($request->get('user').'@'.$request->get('maildomain'));
        $user->setFirstname($request->get('user'));
        $user->setLastname('');
        $user->setEmail($request->get('user').'@'.$request->get('maildomain'));
        $user->setPlainPassword($request->get('user'));
        $user->setEnabled(true);
        $em->persist($user);
        
        // on flush avant la mise en place des filtres
        $em->flush();

        $filter1 = new \Arii\CoreBundle\Entity\Filter();
        $filter1->setEnterprise($enterprise);
        $filter1->setFilter('Housekeeping');
        $filter1->setRepository('*');
        $filter1->setSpooler('*');
        $filter1->setJob('*/housekeeping/*');
        $filter1->setJobChain('*/housekeeping/*');
        $filter1->setOrderId('*');
        $filter1->setStatus('*');
        $filter1->setTitle('Housekeeping');
        $em->persist($filter1);
   
        $filter2 = new \Arii\CoreBundle\Entity\Filter();
        $filter2->setEnterprise($enterprise);
        $filter2->setFilter($request->get('supervisor'));
        $filter2->setSpooler($request->get('supervisor'));
        $filter2->setRepository('*');
        $filter2->setJob('*');
        $filter2->setJobChain('*');
        $filter2->setOrderId('*');
        $filter2->setStatus('*');
        $filter2->setTitle($request->get('supervisor'));
        $em->persist($filter2);

        $filter3 = new \Arii\CoreBundle\Entity\Filter();
        $filter3->setEnterprise($enterprise);
        $filter3->setFilter($request->get('spooler'));
        $filter3->setRepository('*');
        $filter3->setSpooler($request->get('spooler'));
        $filter3->setJob('*');
        $filter3->setJobChain('*');
        $filter3->setOrderId('*');
        $filter3->setStatus('*');
        $filter3->setTitle($request->get('spooler'));
        $em->persist($filter3);

        $filter4 = new \Arii\CoreBundle\Entity\Filter();
        $filter4->setEnterprise($enterprise);
        $filter4->setFilter($request->get('spooler').' FAILURE,STOPPED,RUNNING');
        $filter4->setRepository('*');
        $filter4->setSpooler('*');
        $filter4->setJob('*');
        $filter4->setJobChain('*');
        $filter4->setOrderId('*');
        $filter4->setStatus('STOPPED|FAILURE|RUNNING');
        $filter4->setTitle($request->get('spooler').' FAILURE,STOPPED,RUNNING');
        $em->persist($filter4);

        # Attachement des filtres aux utilisateurs
        # Pas de filtre pour les admins
        # Access spooler pour les operateurs
        # Pas d'ecriture
        // on flush avant la mise en place des filtres
        $em->flush();

        $manag_filter3 =  new \Arii\CoreBundle\Entity\TeamFilter();
        $manag_filter3->SetTeam($team_managers);
        $manag_filter3->SetName($request->get('managers').' '.$request->get('spooler').' (RWX)');
        $manag_filter3->SetDescription($request->get('managers').' '.$request->get('spooler').' (RWX)');
        $manag_filter3->SetFilter($filter3);
        $manag_filter3->SetR(true);
        $manag_filter3->SetW(true);
        $manag_filter3->SetX(true);
        $em->persist($manag_filter3);
        
        $manag_filter2 =  new \Arii\CoreBundle\Entity\TeamFilter();
        $manag_filter2->SetTeam($team_managers);
        $manag_filter2->SetName($request->get('managers').' '.$request->get('supervisor').' (RWX)');
        $manag_filter2->SetDescription($request->get('managers').' '.$request->get('supervisor').' (RWX)');
        $manag_filter2->SetFilter($filter3);
        $manag_filter2->SetR(true);
        $manag_filter2->SetW(true);
        $manag_filter2->SetX(true);
        $em->persist($manag_filter2);

        # Acces supervisor pour les devs
        $dev_filter2 =  new \Arii\CoreBundle\Entity\TeamFilter();
        $dev_filter2->SetTeam($team_devs);
        $dev_filter2->SetFilter($filter2);
        $dev_filter2->SetName($request->get('developpers').' '.$request->get('supervisor').' (RWX)');
        $dev_filter2->SetDescription($request->get('developpers').' '.$request->get('supervisor').' (RWX)');
        $dev_filter2->SetR(true);
        $dev_filter2->SetW(true);
        $dev_filter2->SetX(true);
        $em->persist($dev_filter2);

        $ope_filter3 =  new \Arii\CoreBundle\Entity\TeamFilter();
        $ope_filter3->SetTeam($team_ops);
        $ope_filter3->SetName($request->get('operators').' '.$request->get('spooler').' (RX)');
        $ope_filter3->SetDescription($request->get('operators').' '.$request->get('spooler').' (RX)');
        $ope_filter3->SetFilter($filter3);
        $ope_filter3->SetR(true);
        $ope_filter3->SetW(false);
        $ope_filter3->SetX(true);
        $em->persist($ope_filter3);
        
        # Acces supervisor pour les devs
        $dev_filter2 =  new \Arii\CoreBundle\Entity\TeamFilter();
        $dev_filter2->SetTeam($team_devs);
        $dev_filter2->SetFilter($filter2);
        $dev_filter2->SetName($request->get('developpers').' '.$request->get('supervisor').' (RWX)');
        $dev_filter2->SetDescription($request->get('developpers').' '.$request->get('supervisor').' (RWX)');
        $dev_filter2->SetR(true);
        $dev_filter2->SetW(true);
        $dev_filter2->SetX(true);
        $em->persist($dev_filter2);

        # Acces lecture spooler pour les devs
        $dev_filter3 =  new \Arii\CoreBundle\Entity\TeamFilter();
        $dev_filter3->SetTeam($team_devs);
        $dev_filter3->SetFilter($filter3);
        $dev_filter3->SetName($request->get('developpers').' '.$request->get('spooler').' (R)');        
        $dev_filter3->SetDescription($request->get('developpers').' '.$request->get('spooler').' (R)');        
        $dev_filter3->SetR(true);
        $dev_filter3->SetW(false);
        $dev_filter3->SetX(false);
        $em->persist($dev_filter3);

        # Acces lecture pour les users
        $user_filter2 =  new \Arii\CoreBundle\Entity\TeamFilter();
        $user_filter2->SetTeam($team_users);
        $user_filter2->SetFilter($filter3);
        $user_filter2->SetName($request->get('users').' '.$request->get('spooler').' (R)'); 
        $user_filter2->SetDescription($request->get('users').' '.$request->get('spooler').' (R)'); 
        $user_filter2->SetR(true);
        $user_filter2->SetW(false);
        $user_filter2->SetX(false);
        $em->persist($dev_filter2);

        # Filtres de confort
        $admin_filter4 =  new \Arii\CoreBundle\Entity\UserFilter();
        $admin_filter4->SetUser($user_admin);
        $admin_filter4->SetFilter($filter4);
        $admin_filter4->SetName($request->get('users').' FAILURE'); 
        $admin_filter4->SetDescription($request->get('users').' FAILURE'); 
        $em->persist($admin_filter4);
        
        $manag_filter4 =  new \Arii\CoreBundle\Entity\UserFilter();
        $manag_filter4->SetUser($user_manag);
        $manag_filter4->SetFilter($filter4);
        $manag_filter4->SetName($request->get('managers').' FAILURE'); 
        $manag_filter4->SetDescription($request->get('managers').' FAILURE'); 
        $em->persist($manag_filter4);

        $ope_filter4 =  new \Arii\CoreBundle\Entity\UserFilter();
        $ope_filter4->SetUser($user_ope);
        $ope_filter4->SetFilter($filter4);
        $ope_filter4->SetName($request->get('operators').' FAILURE'); 
        $ope_filter4->SetDescription($request->get('operators').' FAILURE'); 
        $em->persist($ope_filter4);
        
        $dev_filter4 =  new \Arii\CoreBundle\Entity\UserFilter();
        $dev_filter4->SetUser($user_dev);
        $dev_filter4->SetFilter($filter4);
        $dev_filter4->SetName($request->get('developppers').' FAILURE'); 
        $dev_filter4->SetDescription($request->get('developppers').' FAILURE'); 
        $em->persist($dev_filter4);

        $user_filter4 =  new \Arii\CoreBundle\Entity\UserFilter();
        $user_filter4->SetUser($user);
        $user_filter4->SetFilter($filter4);
        $user_filter4->SetName($request->get('user').' FAILURE'); 
        $user_filter4->SetDescription($request->get('user').' FAILURE'); 
        $em->persist($user_filter4);
        $em->flush();
        $this->get('session')->getFlashBag()->add('notice', 'Enterprise created');

        return $this->render("AriiAdminBundle:Default:index.html.twig" );
       
        // Nouveau workload
        $connection_workload1 = new \Arii\CoreBundle\Entity\Connection();
        $connection_workload1->setEnterprise($enterprise);
        $connection_workload1->setTitle($request->get('workload1'));
        $connection_workload1->setDescription($request->get('workload1'));
        $connection_workload1->setHost($request->get('workload1_ip'));
        // Arii DB
        $connection_workload1->setPort($request->get('workload1_port'));        
        $connection_workload1->setLogin('');        
        $connection_workload1->setPassword('');  
        // jointure
        // $network_db = new \Arii\CoreBundle\Entity\Network();
        $network_workload1 = $this->getDoctrine()->getRepository("AriiCoreBundle:Network")->findOneBy(array('protocol'=>'osjs'));
        $connection_workload1->setNetwork($network_workload1);
        $em->persist($connection_workload1);
       
        $workload1 = new \Arii\CoreBundle\Entity\Spooler();
        $workload1->setScheduler($request->get('workload1'));
        $workload1->setName("New Spooler"); // ModifyForm
        $workload1->setDescription("this is the new spooler");// ModifyForm
        $workload1->setSite($site);
        $workload1->setConnection($connection_workload1);
        $workload1->setSupervisor($supervisor);
        $workload1->setSmtp($connection_mail);
        $workload1->setDb($connection_db);
        $workload1->setInstallPath($request->get('workload1_install_path'));
        $workload1->setUserPath($request->get('workload1_user_path'));         
        $workload1->setEvents(false); 
        $workload1->setClusterOptions('-distributed-orders'); 
        $workload1->setLicence(''); 
        $workload1->setOsTarget(php_uname('s'));
        $workload1->setInstallDate(new \DateTime());
        $workload1->setStatus('INSTALLATION');
        $workload1->setStatusDate(new \DateTime());
        $workload1->setTimezone('GMT');
        $workload1->setVersion($this->container->getParameter('osjs_version'));
        $workload1->setScheduler('test1');
        $em->persist($workload1);
        $em->flush();
       
        // Nouveau workload
        $connection_workload2 = new \Arii\CoreBundle\Entity\Connection();
        $connection_workload2->setEnterprise($enterprise);
        $connection_workload2->setTitle($request->get('workload2'));
        $connection_workload2->setDescription($request->get('workload2'));
        $connection_workload2->setHost($request->get('workload2_ip'));
        // Arii DB
        $connection_workload2->setPort($request->get('workload2_port'));        
        $connection_workload2->setLogin('');        
        $connection_workload2->setPassword('');  
        // jointure
        //$network_db = new \Arii\CoreBundle\Entity\Network();
        $network_workload2 = $this->getDoctrine()->getRepository("AriiCoreBundle:Network")->findOneBy(array('protocol'=>'osjs'));
        $connection_workload2->setNetwork($network_workload2);
        $em->persist($connection_workload2);

        $workload2 = new \Arii\CoreBundle\Entity\Spooler();
        $workload2->setScheduler($request->get('workload2'));
        $workload2->setName("New Spooler"); // ModifyForm
        $workload2->setDescription("this is the new spooler");// ModifyForm
        $workload2->setSite($site);
        $workload2->setConnection($connection_workload2);
        $workload2->setSupervisor($supervisor);
        $workload2->setSmtp($connection_mail);
        $workload2->setDb($connection_db);
        $workload2->setInstallPath($request->get('workload2_install_path'));
        $workload2->setUserPath($request->get('workload2_user_path'));         
        $workload2->setEvents(false); 
        $workload2->setClusterOptions('-distributed-orders'); 
        $workload2->setLicence(''); 
        $workload2->setOsTarget(php_uname('s'));
        $workload2->setInstallDate(new \DateTime());
        $workload2->setStatus('INSTALLATION');
        $workload2->setStatusDate(new \DateTime());
        $workload2->setTimezone('GMT');
        $workload2->setVersion($this->container->getParameter('osjs_version'));
        $workload2->setScheduler('test2');
        $em->persist($workload2);
        $em->flush();

        print "success";
        return $this->render("AriiAdminBundle:Enterprise:dashboard.html.twig" );
    }

}

?>
