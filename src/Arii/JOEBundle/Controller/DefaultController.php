<?php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Default:index.html.twig');
    }

    public function ribbonAction()
    {
        $folder = $this->container->get('arii_core.folder');
        $Dir = $folder->Remotes();
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiJOEBundle:Default:ribbon.json.twig',array('Schedulers' => $Dir), $response);
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOEBundle:Default:menu.xml.twig', array(), $response);
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOEBundle:Default:toolbar.xml.twig', array(), $response );
    }

    public function treeAction($folder='live',$filter=0) {
        $request = Request::createFromGlobals();
        if ($request->get('folder')!='') {
            $folder = $request->get('folder');
        }
        
        $sql = $this->container->get('arii_core.sql');                  
        $qry = $sql->Select(array('ID','FOLDER','PATH','FILE','TYPE'))
                .$sql->From(array('JOE_FILE'))
                .$sql->Where(array('FOLDER'=>$folder))
                .$sql->OrderBy(array('PATH','FILE'));
        
        $db = $this->container->get('arii_core.db');        
        $data = $db->Connector('grid');
        $res = $data->sql->query( $qry );
        $Info = $key_files = array();
        while ( $line = $data->sql->get_next($res) ) {
            $file = $line['PATH'].'/'.$line['FILE'];
            $Info[$file] = $line;
            $key_files[$file] = $file;
        }
        
        $tools = $this->container->get('arii_core.tools');
        $tree = $tools->explodeTree($key_files, "/");
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<tree id='0'>\n";
        $list .= $this->Folder2XML( $tree, '', $Info );
        $list .= "</tree>\n";
        $response->setContent( $list );
        return $response;

    }
    
   function Folder2XML( $leaf, $id = '', $Info ) {
            $return = '';
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $Ids = explode('/',$k);
                            $here = array_pop($Ids);
                            $i  = "$id/$k";
                            # On ne prend que l'historique
                            if (isset($Info[$i])) {
                                $return .= '<item id="'.$Info[$i]['ID'].'" text="'.$k.'" im0="'.$Info[$i]['TYPE'].'.png">';
                            }
                            else {
                                $return .=  '<item id="'.$i.'" text="'.$k.'" im0="folder.gif">';
                            }
                            $return .= $this->Folder2XML( $leaf[$k], $i, $Info);
                            $return .= '</item>';
                    }
            }
            return $return;
    }

}
