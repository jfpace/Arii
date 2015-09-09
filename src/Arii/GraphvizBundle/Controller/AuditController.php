<?php

namespace Arii\GraphvizBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditController extends Controller
{
    private $graphviz_dot;
    private $config;

    public function indexAction()
    {
        return $this->render('AriiGraphvizBundle:Audit:index.html.twig');
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiGraphvizBundle:Audit:toolbar.xml.twig',array(), $response);
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiGraphvizBundle:Audit:menu.xml.twig',array(), $response);
    }

    public function ribbonAction()
    {
        $folder = $this->container->get('arii_core.folder');
        $Dir = $folder->Remotes();
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiGraphvizBundle:Audit:ribbon.json.twig',array('Schedulers' => $Dir), $response);
    }

   public function commentsAction()
    {
        $db = $this->container->get("arii_core.db");
        $data = $db->Connector('grid'); 
        return $data->render_table('GVZ_COMMENTS','ID','FILE,TYPE,COMMENT');
    }

    public function generateAction($action = 'audit')
    {
        $request = Request::createFromGlobals();
        $return = 0;

        set_time_limit(600);
        
        // Localisation des images 
        $images = '/bundles/ariigraphviz/images/silk';
        $images_path = $this->get('kernel')->getRootDir().'/../web'.$images;
        $images_url = $this->container->get('templating.helper.assets')->getUrl($images);        
        
        $this->config = $this->container->getParameter('osjs_config');
        $this->images = $this->container->getParameter('graphviz_images');
        $this->graphviz_dot = $this->container->getParameter('graphviz_dot');
        $session = $this->getRequest()->getSession();
        $path = '.*';
        if ($request->query->get( 'path' ))
            $path = $request->query->get( 'path' );
        if ($request->query->get( 'action' ))
            $action = $request->query->get( 'action' );
        
        // bidouille 
        if (substr($path,-1)=='/')
                $path = substr($path,0,strlen($path)-1);
        
        $file = '.*';
        $rankdir = 'TB';
        $splines = 'polyline';
        $show_params = 'n';
        $show_events = 'n';
        $show_jobs = 'n';
        $show_chains = 'n';
        $show_config = 'n';
/*
         if ($request->query->get( 'file' ))
            $file = $request->query->get( 'file' );
 */
        if ($request->query->get( 'splines' ))
            $splines = $request->query->get( 'splines' );
        if ($request->query->get( 'show_params' ))
            $show_params = $request->query->get( 'show_params' );
        if ($show_params == 'true')
            $show_params = 'y';
        else            
            $show_params = 'n';

        if ($request->query->get( 'show_events' ))
            $show_events = $request->query->get( 'show_events' );
        if ($show_events == 'true')
            $show_events = 'y';
        else           
            $show_events = 'n';

        if ($request->query->get( 'show_chains' ))
            $show_chains = $request->query->get( 'show_chains' );
        if ($show_chains == 'true')
            $show_chains = 'y';
        else           
            $show_chains = 'n';

        if ($request->query->get( 'show_jobs' ))
            $show_jobs = $request->query->get( 'show_jobs' );
        if ($show_jobs == 'true')
            $show_jobs = 'y';
        else           
            $show_jobs = 'n';

        if ($request->query->get( 'show_config' ))
            $show_config = $request->query->get( 'show_config' );
        if ($show_config == 'true')
            $show_config = 'y';
        else           
            $show_config = 'n';
        
        if ($request->query->get( 'output' ))
            $output = $request->query->get( 'output' );
        else {
            $output = "svg";        
        }

        // Si le path est un xml, l'objet devient le filtre   
        $P = explode('/',$path);
        $hotfolder = array_shift($P);
        $f = array_pop($P);
        $filtre = '';
        if (substr($f,-4)=='.xml') {
            $IF = explode('.',$f);
            $ext = array_pop($IF);
            $type = array_pop($IF);            
            switch ($type) {
                case 'order':
                case 'job_chain':
                case 'job':
                    $filtre .= ' -'.$type.'="'.implode('.',$IF).'"';
                    break;
            }
            $path = '/'.implode('/',$P);
        }
        else {
            $filtre = ' -file=.*';
            $path = implode('/',$P).'/'.$f;
        }

        if ($hotfolder!='live') {
            $hotfoler="remote/$hotfolder";
        }

        //$gvz_cmd = $this->container->getParameter('graphviz_cmd');
        $gvz_cmd = 'perl '.dirname(__FILE__).str_replace('/',DIRECTORY_SEPARATOR,'/../Perl/'.$action.'.pl ');
        $config = str_replace('/',DIRECTORY_SEPARATOR,$this->config);
        // Parametre en fonction du script
        // Ajout de l'environnement
        $tmp = $this->container->getParameter('arii_tmp');
        $web = $images.'/../reports';
      // $web = $this->getRequest()->getUriForPath('/web/bundles/ariigraphviz/images/reports');
         $img = $this->get('kernel')->getRootDir() . '/../web'.$web;
        // $img = 'D:/www/arii/web/bundles/ariigraphviz/images/reports';
          

        // On cree le fichier de commentaire
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');          
        $sql = $this->container->get('arii_core.sql');  
        $qry = $sql->Select(array('FILE','TYPE','COMMENT' ))
                .$sql->From(array('GVZ_COMMENTS'))
                .$sql->OrderBy(array('FILE'));  
        
        $res = $data->sql->query( $qry );
        $comments ='';
        while ( $line = $data->sql->get_next($res) ) {
            $comments .= $line['FILE']."\t".$line['TYPE']."\t".$line['COMMENT']."\n";
        }    
        file_put_contents( "$img/comments.txt", $comments);          
        $cmd = $gvz_cmd.' -config="'.$config.'" -folder="'.$hotfolder.'" -images="'.$images_path.'" -path="'.$path.'" -splines="'.$splines.'" -rankdir="'.$rankdir.'" -show_params="'.$show_params.'" -show_events="'.$show_events.'" -show_chains="'.$show_chains.'" -show_jobs="'.$show_jobs.'" -show_config="'.$show_config.'" -web="'.$web.'" -tmp="'.$tmp.'" -img="'.$img.'" -dot="'.$this->graphviz_dot.'"';
        print `$cmd`;
      exit();
    }


    function CleanPath($path) {
        
        // bidouille en attendant la fin de l'étude
/*        if (substr($path,0,4)=='live') 
            $path = substr($path,4);
        elseif (substr($path,0,6)=='remote') 
            $path = substr($path,6);
        elseif (substr($path,0,5)=='cache') 
            $path = substr($path,5);
*/      
        $path = str_replace('/','.',$path);
        $path = str_replace('\\','.',$path);
        $path = str_replace('#','.',$path);
        
        // protection des | sur windows
        $path = str_replace('|','^|',$path);       
        
        return $path;
    }
}
