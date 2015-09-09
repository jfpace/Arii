<?php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadController extends Controller
{
    protected $ColorStatus = array (
            'NO CHANGE' => '#ccebc5',
            'UPDATE' => '#ffffcc',
            'DELETE' => 'red',
            'OLDER' => '#fbb4ae',
            'INSERT' => '#ffffcc'
        );
    
    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Load:index.html.twig');
    }

    public function ribbonAction()
    {
        $folder = $this->container->get('arii_core.folder');
        $Dir = $folder->Remotes();
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $this->render('AriiJOEBundle:Load:ribbon.json.twig',array('Schedulers' => $Dir), $response);
    }

    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOEBundle:Load:menu.xml.twig', array(), $response);
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOEBundle:Load:toolbar.xml.twig', array(), $response );
    }

    public function treeAction($path='live')
    {
        $tools = $this->container->get('arii_core.tools');
        $config =  $this->container->getParameter('osjs_config');
        $spooler =  $this->container->getParameter('osjs_id');
        
        $request = Request::createFromGlobals();
        if ($request->get('path') != '') {
            $path = str_replace(':','#', $request->get('path'));            
        }

        $folder = $this->container->get('arii_core.folder');

        $xml = "<?xml version='1.0' encoding='utf-8'?>";                
        $xml .= '<tree id="0">';        
        $xml .= $folder->TreeXML( str_replace('\\','/',$config), $path );
        $xml .= '</tree>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent($xml);
        return $response;
    }

    function gridAction() {
        $request = Request::createFromGlobals();
        $path = $request->get('path');
        $Info = explode('/',$path);
        $folder = array_shift($Info);
        $file = array_pop($Info);
        $path = implode('/',$Info);
        $Fields = array (
            'FOLDER' => $folder,
            'PATH' => "$path/%" );
        if ($file!='') {
            $Fields['FILE'] = $file;
        }
        
        $sql = $this->container->get('arii_core.sql');                  
        $qry = $sql->Select(array('ID','FOLDER','PATH','FILE','SIZE','MTIME','TYPE','UPDATED','CRC','MD5'))
                .$sql->From(array('JOE_FILE'))
                .$sql->Where( $Fields )
                .$sql->OrderBy(array('PATH','FILE'));
        
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('grid');
//        $data->event->attach("beforeRender",array($this,"form_render"));
        $data->render_sql($qry,'ID','FOLDER,PATH,FILE,TYPE,SIZE,MTIME,UPDATED,CRC,MD5');        
    }
    
    // Comparaison entre la base de données et les fichiers
    function gridCheckAction() {
        
        $request = Request::createFromGlobals();
        $path = $request->get('path');
        
        $Info = explode('/',$path);
        $folder = array_shift($Info);
        $file = array_pop($Info);
        $path = implode('/',$Info);
        $Fields = array (
            'FOLDER' => $folder,
            'PATH' => "$path" );
        if ($file!='') {
            $Fields['FILE'] = $file;
        }
        $sql = $this->container->get('arii_core.sql');                  
        $qry = $sql->Select(array('ID','FOLDER','PATH','FILE','SIZE','MTIME','TYPE','UPDATED','CRC','MD5'))
                .$sql->From(array('JOE_FILE'))
                .$sql->Where( $Fields )
                .$sql->OrderBy(array('PATH','FILE'));

        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $Files = array();
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $id = $line['FOLDER'].'/'.$line['PATH'].'/'.$line['FILE'];
            $Files[$id]['DB'] = $line;
        }
        
        // Lecture de l'arborescence
        $folders = $this->container->get('arii_core.folder');
        $config =  $this->container->getParameter('osjs_config');
        
        $l = strlen($config);
        foreach ($folders->Tree("$config/$folder",$path) as $f) {
            $id = substr($f,$l+1);
            $Files[$id]['FILE'] = stat($f);
        }
        
        ksort($Files);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "<rows>\n";
        $xml .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        foreach ($Files as $id => $info) {
            $store = 0;
            $action = '';
            $size = 0;
            $mtime=0;
            if (isset($Files[$id]['DB'])) {
                $store += 1;
                $folder = $Files[$id]['DB']['FOLDER'];
                $path = $Files[$id]['DB']['PATH'];
                $file = $Files[$id]['DB']['FILE'];
                
                $size = $Files[$id]['DB']['SIZE'];
                $mtime = $Files[$id]['DB']['MTIME'];
                // suprimer du disque 
                if (isset($Files[$id]['FILE'])) {
                    if ($Files[$id]['DB']['MTIME']<>$Files[$id]['FILE']['mtime']) {
                        # Changement ?                        
                        if ($Files[$id]['DB']['MTIME'] != md5_file($f)) {
                            if ($Files[$id]['DB']['MTIME']>$Files[$id]['FILE']['mtime']) {
                                $action = 'OLDER';
                            } 
                            else {
                                $action = 'UPDATE';
                            }
                        }
                    }
                    else {
                        $action = 'NO CHANGE';
                    }
                }
                else {
                    $action = 'DELETE';                    
                }
            }
            else {
                $store += 2;
                $Info = explode('/',$id);
                $folder = array_shift($Info);
                $file = array_pop($Info);
                $path = implode('/',$Info);
                
                $size = $Files[$id]['FILE']['size'];
                $mtime = $Files[$id]['FILE']['mtime'];
                $action = 'INSERT';
            }
            
            $xml .= '<row bgColor="'.$this->ColorStatus[$action].'">';
            $xml .= '<cell>'.$folder.'</cell>';
            $xml .= '<cell>/'.$path.'</cell>';
            $xml .= '<cell>'.$file.'</cell>';
            $xml .= '<cell>'.$action.'</cell>';
            $xml .= '<cell>'.$size.'</cell>';            
            $xml .= '<cell>'.date("Y-m-d H:i:s",$mtime).'</cell>';            
            $xml .= '</row>';
        }
        $xml .= "</rows>\n";
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');        
        $response->setContent( $xml );
        return $response;
    }

    public function syncAction($syncFolder='live') {
        set_time_limit(600);
        $t =  time();
        // on ouvre le repertoire folder
        $tools = $this->container->get('arii_core.tools');
        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('data');
        
        $config = $this->container->getParameter('osjs_config');

        $request = Request::createFromGlobals();
        $path = $request->get('path');
        
        $Info = explode('/',$path);
        $folder = array_shift($Info);
        $file = array_pop($Info);
        $path = implode('/',$Info);
        
        $Fields = array (
            'FOLDER' => $folder,
            'PATH' => "$path/%" );
        if ($file!='') {
            $Fields['FILE'] = $file;
        }
        // etat actuel
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('ID','FOLDER','PATH','FILE','CRC'))
                .$sql->From(array('JOE_FILE'))
                .$sql->Where( $Fields )
                .$sql->OrderBy(array('PATH','FILE'));
        
        $res = $data->sql->query( $qry );
        $c_file=0;
        $FileID = $FileCRC = $FileDel = array();
        while ( $line = $data->sql->get_next($res) ) {
                $folder = $line['FOLDER'];
                $id  =  $line['ID'];
                $path = $line['PATH'];
                if ($folder!='live') {
                    $path = $folder.'/'.$path;
                    $folder = 'remote';
                }
                $file =  $folder.'/'.$path.'/'.$line['FILE'];
                $FileID[$file] = $id;
                $FileCRC[$file] = $line['CRC'];
                $FileDel[$file] = $id;
                $c_file++;
        }
        $qry = '';
        $timestamp = time();

        // Lecture de l'arborescence
        $folders = $this->container->get('arii_core.folder');
        $config =  $this->container->getParameter('osjs_config');
        $Files = $folders->Tree("$config/$syncFolder",$path);
        
        $l = strlen("$config/$syncFolder");
        $Queries = array();
        foreach ($Files as $id) {
            $file = substr($id,$l+1);
            $path = dirname($file);
            $filename = basename($file);
            $Info = stat($id);            
            if ($syncFolder!='live') {
                $p = strpos($path,'/');
                if ($p>0) {
                    $folder = substr($path,0,$p);
                    $path = substr($path,$p+1);
                } 
                else {
                    $folder = $path;
                    $path = '';
                }      
            }

            list($name,$obj,$cat) = $this->getObject($filename);   
            $update = 'FOLDER="'.$syncFolder.'", PATH="'.$path.'",'.'FILE="'.$filename.'",CAT="'.$cat.'",SIZE='.$Info[7].', MTIME='.$Info[9];
            $crc= hash('crc32',$update);
            if (isset($FileID[$file])) {
                $dbid = $FileID[$file];
                $FileDel[$file]= -1;
                if ($FileCRC[$file]!=$crc) {    
                    $md5 = md5_file($id);       
                    $qry = 'update JOE_FILE set '.$update.',NAME="'.$name.'",TYPE="'.$obj.'",UPDATED='.$timestamp.',CRC="'.$crc.'",MD5="'.$md5.'", ATIME='.$Info[8].' where id='.$id;
                    array_push($Queries, $qry);
                }
            }
            else {
                    $dbid= 'null';
                    $md5 = md5_file($id);  
                    $qry = 'insert into JOE_FILE (FOLDER,NAME,TYPE,PATH,FILE,CAT,SIZE,ATIME,MTIME,UPDATED,CRC,MD5) values ( "'.$folder.'","'.$name.'","'.$obj.'","'.$path.'", "'.$filename.'","'.$cat.'",'.$Info[7].','.$Info[8].','.$Info[9].','.$timestamp.',"'.$crc.'","'.$md5.'" )';
                    array_push($Queries, $qry);
            }
        }
        
        // Suppression des fichiers
        $Del = array();
        foreach ($FileDel as $k=>$v) {
            if ($v>=0) {
                array_push($Del,$v);
            }
        }

        if (!empty($Del)) 
                array_push($Queries, 'delete from JOE_FILE where ID in ('.implode(',',$Del).')');

        if (empty($Queries)) exit();

        // print_r($Queries);
        // sauvegarde
        $qry = implode(';', $Queries);
        print "Synchronized!";
        exit();
    //    $res = $data->sql->multi_query( $qry ); 
        return true;
    }

    private function getObject($file) {
        $CAT = array( 
            'job'=>1, 
            'job_chain'=>2,
            'order'=>3,
            'schedule'=>4,
            'process_class'=>5,
            'lock'=>6,
            'days'=>7,
            'config' =>8,
            'params' =>9);
        $Info = explode('.',$file);
        $ext = array_pop($Info);
        if ($ext=='xml') {
            $ext = array_pop($Info);
            if (isset($CAT[$ext])) {
                $cat = $CAT[$ext];
            }
            else {
                $cat = 0;
            }
        }
        elseif (in_array($ext,array('pl','cmd','bat','sh','ksh','vb'))) {
            $cat = -1;
        }
        else {
            $cat = -2;
        }
        return array(implode('.',$Info),$ext,$cat);
    }

}
