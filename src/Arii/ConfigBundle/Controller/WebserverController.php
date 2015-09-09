<?php

namespace Arii\ConfigBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WebserverController extends Controller
{
    public function indexAction()
    {
        $Config = array (
            "repository_name" => "Repository name", 
            "repository_dbname" => "Repository database",
            "repository_host" => "Repository host",
            "repository_port" => "Repository port",
            "repository_user" => "Repository user",
            "repository_password" => "Repository password",
            "repository_driver" => "Repository driver",
            "workspace" => "Workspace",
            "enterprise" => "Default enterprise",
            "site_name" => "Default site",
            "osjs_id"   => "Job scheduler Id",
            "osjs_ipaddress" => "Job scheduler IP adress",
            "osjs_version" => "Job scheduler version",
            "osjs_port" => "Job scheduler port",
            "osjs_protocol" => "Job scheduler protocol",
            "osjs_path" => "Job scheduler path",
            "packages" => "Packages",
            "perl" => "Perl interpreter",
            "arii_tmp" => "Temporary files",
            
            "doc_job" => "Link to job doc",
            "doc_order" => "Link to job order",
            "doc_plan" => "Link to job plan",
            "graphviz_dot" => "Graphviz interpreter",
            "graphviz_images" => "Graphviz images"
        );
        // On recupere les parametres
        $Render = array();
        foreach ($Config as $k=>$v) {
            $Info['id'] = $k;
            $Info['label'] = $v;
            $Info['value'] = str_replace('\\','/',$this->container->getParameter($k));
            array_push($Render,$Info);
        }
        return $this->render('AriiConfigBundle:Webserver:index.html.twig',array( 'Params' => $Render )  );
    }
}
