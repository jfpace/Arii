<?php

namespace Arii\GraphvizBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class GraphvizController extends Controller
{
    private $graphviz_dot;
    private $config;

    public function generateAction($action = 'oss2gvz')
    {
        $request = Request::createFromGlobals();
        $return = 0;

        set_time_limit(120);
        
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
        
        // Astuce
        // Si c'est un xml on utilise arii_graph sinon oss2gvz
        if (substr($path,-3)=='xml') {
            $action = 'arii_graph';
        }
        else {
            $action = 'oss2gvz';
        }
        // d'autres repertoires selectionnes ?
/*
        if ($request->query->get( 'paths' )) {
            $Paths= array();
            foreach (explode(',',$request->query->get( 'paths' )) as $p) {
                array_push($Paths, '^'.$this->CleanPath($p).'$');
            }
            array_push($Paths, $this->CleanPath($path));
            $path = '('.implode('|', $Paths).')';
        }
        else {
           $path = $this->CleanPath($path);
        }
*/        
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

        //$gvz_cmd = $this->container->getParameter('graphviz_cmd');
        $gvz_cmd = 'perl '.dirname(__FILE__).str_replace('/',DIRECTORY_SEPARATOR,'/../Perl/'.$action.'.pl ');
        $config = str_replace('/',DIRECTORY_SEPARATOR,$this->config);
        // Parametre en fonction du script 
        if ($action == 'arii_graph') {
            $cmd = $gvz_cmd.' -config="'.$config.'" -hotfolder="'.$hotfolder.'" -images="'.$images_path.'" -path="'.$path.'" '.$filtre.' -splines="'.$splines.'" -rankdir="'.$rankdir.'" -show_params="'.$show_params.'" -show_events="'.$show_events.'" -show_chains="'.$show_chains.'" -show_jobs="'.$show_jobs.'" -show_config="'.$show_config.'"';        
        }
        else {
            $cmd = $gvz_cmd.' -config="'.$config.'" -images="'.$images_path.'" -path="'.$path.'" -splines="'.$splines.'" -rankdir="'.$rankdir.'" -show_params="'.$show_params.'" -show_events="'.$show_events.'" -show_chains="'.$show_chains.'" -show_jobs="'.$show_jobs.'" -show_config="'.$show_config.'"';        
        }
        $check = $cmd;
      $cmd .= ' | "'.$this->graphviz_dot.'" -T '.$output;
/*
print $cmd;
print `$cmd`;
exit();
*/
        if ($output == 'svg') {
            // exec($cmd,$out,$return);
            $out = `$cmd`;

            header('Content-type: image/svg+xml');
            // integration du script svgpan
            $head = strpos($out,'<g id="graph');
            if (!$head) {                
                print $check;
                print $this->graphviz_dot;
                exit();
            }
            $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg style="width: 100%;" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1">
<script xlink:href="'.$this->container->get('templating.helper.assets')->getUrl("bundles/ariigraphviz/js/SVGPan.js").'"/>
<g id="viewport"';
            $xml .= substr($out,$head+14);
            print str_replace('xlink:href="'.$images_path,'xlink:href="'.$images_url,$xml);
        }
        elseif ($output == 'pdf') {
            header('Content-type: application/pdf');
            $out = `$cmd`;
            print trim($out);
        }
        else {
            header('Content-type: image/'.$output);
            $out = `$cmd`;
            print system($cmd);
            exit();
        }
        exit();
    }

    public function configAction()
    {
        $request = Request::createFromGlobals();
        // system('C:/xampp/htdocs/Symfony/vendor/graphviz/config.cmd');
        $return = 0;
        $output = "svg";
        if ($request->query->get( 'output' ))
            $output = $request->query->get( 'output' );
        
        $gvz_cmd = $this->container->getParameter('graphviz_config_cmd');
        $config = "c:/arii/enterprises/sos-paris/spoolers";
        $cmd = $gvz_cmd.' "'.$config.'" "'.$output.'"';

   //     print $cmd; exit();
        $base =  $this->container->getParameter('graphviz_base'); 
        if ($output == 'svg') {
            exec($cmd,$out,$return);
            header('Content-type: image/svg+xml');
            foreach ($out as $o) {
                $o = str_replace('xlink:href="../../web','xlink:href="'.$base.'',$o);
                print $o;
            }
        }
        elseif ($output == 'pdf') {
            header('Content-type: application/pdf');
            system($cmd);
        }
        else {
            header('Content-type: image/'.$output);
            system($cmd);
        }
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
