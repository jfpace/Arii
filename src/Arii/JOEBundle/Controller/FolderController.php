<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Entity\ParamJob;
use Arii\JOEBundle\Entity\Script;

class FolderController extends Controller
{
    protected $Folder = array();
    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');     
    }
    
    public function menuAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOEBundle:Folder:menu.xml.twig', array(), $response);
    }

    public function publishAction() {
        // en attendant le cache
        $folder = 'live';
        // $this->syncAction($folder);
        $dhtmlx = $this->container->get('arii_core.db');
        
        $data = $dhtmlx->Connector('data');
        $qry = 'select ID,PATH,FILE,NAME,TYPE,CRC from JOE_FILE where folder="'.$folder.'" order by PATH,FILE'; 
        $res = $data->sql->query( $qry );
        while ( $line = $data->sql->get_next($res) ) {
            $id  =  $line['ID'];
            if ($line['PATH']!='')
                $line['PATH'] = '/'.$line['PATH'];
            if ($line['TYPE'] == 'order') {
                list($chain,$order) = explode(',',$line['NAME']);
                $file =  $line['PATH'].'/'.$chain.'.job_chain.xml/'.$line['FILE'];
                $Info[$file]['LABEL'] = $order;
            }
            elseif ($line['TYPE'] == 'config') {
                $file =  $line['PATH'].'/'.$line['NAME'].'.job_chain.xml/'.$line['FILE'];
                $Info[$file]['LABEL'] = 'config';
            }
            else {
                $file =  $line['PATH'].'/'.$line['FILE'];
                $Info[$file]['LABEL'] = $line['NAME'];
            }
            foreach (array('ID','NAME','TYPE') as $k) {
                $Info[$file][$k] = $line[$k];
            }
            $key_files[$file] = $file;
        }
        
        $tools = $this->container->get('arii_core.tools');
        $tree = $tools->explodeTree($key_files, "/");
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        /*implode(' ',$status)
        SUCCESS FAILURE
        strpos(SUCCESS )*/
        $list .= $this->Folder2XML( $tree, '', $Info );
        $list .= "</rows>\n";
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
                                $return .= '<row id="'.$Info[$i]['ID'].'">';
                                $return .= '<cell image="xml.png"> '.$Info[$i]['LABEL'].'</cell>';
                                $return .= '<cell>'.$Info[$i]['TYPE'].'</cell>';
                                $return .= '<cell>'.$this->images.'/'.$Info[$i]['TYPE'].'.png</cell>';
                            }
                            else {
                                $return .=  '<row id="'.$i.'" open="1">';
                                $return .=  '<userdata name="type">folder</userdata>';
                                $return .=  '<cell image="folder.gif">'.$here.'</cell>';
                            }
                           $return .= $this->Folder2XML( $leaf[$k], $id.'/'.$k, $Info);
                           $return .= '</row>';
                    }
            }
            return $return;
    }

    public function toolbarAction($folder="live") {
        return $this->render("AriiJOEBundle:Default:toolbar.xml.twig");
    }
    
    public function syncAction($Folders=array("live","remote")) {
        set_time_limit(600);
        $t =  time();
        // on ouvre le repertoire folder
        $tools = $this->container->get('arii_core.tools');
        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('data');
        
        $config = $this->container->getParameter('osjs_config');

        foreach ($Folders as $folder) {
            $this->Tree_dir($config.'/'.$folder,'', $folder);
        }

        // etat actuel
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('ID','FOLDER','PATH','FILE','CRC'))
                .$sql->From(array('JOE_FILE')); 
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

        $Queries = array();
        foreach ($this->Folder as $F) {
            $folder = $F['folder'];
            $path = $F['path'];
            $file = $folder.'/'.$path.'/'.$F['file'];
            $Info = stat($config.'/'.$file);            
            if ($folder!='live') {
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

            list($name,$obj,$cat) = $this->getObject($F['file']);                  
            $update = 'FOLDER="'.$folder.'", PATH="'.$path.'",'.'FILE="'.$F['file'].'",CAT="'.$cat.'",SIZE='.$Info[7].', MTIME='.$Info[9];
            $crc= hash('crc32',$update);
            if (isset($FileID[$file])) {
                $id = $FileID[$file];
                $FileDel[$file]= -1;
                if ($FileCRC[$file]!=$crc) {    
                    $md5 = md5_file($config.'/'.$file);       
                    $qry = 'update JOE_FILE set '.$update.',NAME="'.$name.'",TYPE="'.$obj.'",UPDATED='.$timestamp.',CRC="'.$crc.'",MD5="'.$md5.'", ATIME='.$Info[8].' where id='.$id;
                    array_push($Queries, $qry);
                }
            }
            else {
                    $id= 'null';
                    $md5 = md5_file($config.'/'.$file);  
                    $qry = 'insert into JOE_FILE (FOLDER,NAME,TYPE,PATH,FILE,CAT,SIZE,ATIME,MTIME,UPDATED,CRC,MD5) values ( "'.$folder.'","'.$name.'","'.$obj.'","'.$path.'", "'.$F['file'].'","'.$cat.'",'.$Info[7].','.$Info[8].','.$Info[9].','.$timestamp.',"'.$crc.'","'.$md5.'" )';
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

        $res = $data->sql->multi_query( $qry ); 
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
    private function Tree_dir($basedir, $dir, $folder ) {
        if ($dh = opendir($basedir.'/'.$dir)) {
            $Dir = array();
            $Files = array();
            while (($file = readdir($dh)) !== false) {
                $sub = $basedir.'/'.$dir.'/'.$file;
                if (($file != '.') and ($file != '..')) {
                    if (is_dir($sub)) {
                        array_push($Dir, $file );
                    }
                    else {
                        array_push($Files, $file );
                        $new['file'] = $file;
                        $new['path'] = $dir;
                        $new['folder'] = $folder;
                        if ($dir!='') $new['path']=substr($dir,1);
                            else $new['path']='';
                        array_push($this->Folder,$new);
                    }
                }
            }
            closedir($dh);
            
            sort($Dir);
            foreach ($Dir as $file) {
                $this->Tree_dir($basedir,"$dir/$file", $folder);
            }
        }
        else {
            print "!? '".$basedir.'/'.$dir."'";
            exit();
        }
    }

    public function file_viewAction() {
        
        $request = Request::createFromGlobals();
        $config = $this->container->getParameter('osjs_config');

        $Ids = array();
        foreach (explode(',',$request->query->get( 'id' )) as $id) {
            if (is_numeric($id))
                array_push($Ids,$id);
        }
        $id = implode(',',$Ids);

        // On recupere les fichiers
        $dhtmlx = $this->container->get('arii_core.db');
        
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');        
        $qry = $sql->Select(array('ID','FOLDER','PATH','FILE','NAME','SIZE','MTIME','MD5'))
                .$sql->From(array('JOE_FILE'))
                .' where ID in ('.$id.')'
                .$sql->Order(array('PATH','FILE')); 
        $res = $data->sql->query( $qry );
        $count = 0;
        $CRC=array();
        while ( $line = $data->sql->get_next($res) ) {
            $crc = $line['MD5'];
            if (isset($CRC[$crc])) {
                $CRC[$crc]++;
            } 
            else {
                $CRC[$crc]=1;
            }
            $count++;
            
            // sauvegarde des informations
            $id = $line['FOLDER'];
            $File[$id] = $line;
        }
        if ($count==0) {
            print "No file ?!";
            exit();
        }

        // si le compteur du dernier crcest egal au compteur global, c'est qu'il est unique
        $folder= $File[$id]['FOLDER'];
        $path = $File[$id]['PATH'];
        $file = $File[$id]['FILE'];
        // print "<h1>$path/$file</h1>";
        if ($CRC[$crc]==$count) {
            // on renvoie le fichier
            if ($folder=='live') {
                $content = file_get_contents("$config/live/$path/$file");
            }
            else  {
                $content = file_get_contents("$config/remote/$folder/$path/$file");
            }
            // $content = str_replace(array('<','>',"\n"),array('&lt;','&gt;',"<br/>"),$content);
            // print "<pre>$content</pre>";
/*            $File= explode("\n",$content);
            $head = array_shift($File);
            $content = $head.'<?xml-stylesheet href="/arii/css/xml/default.css" type="text/css"?>'.implode("\n",$File);
*/
            $response = new Response();
            $response->headers->set('Content-Type', 'text/xml');
            $response->setContent( utf8_encode($content) );
            return $response;
        }
        
        $Folders = array_keys($File);
        // on affiche les différences
        foreach ($Folders as $folder ) {
            if ($folder=='live') {
                $File[$folder]['CONTENT'] = explode("\n",file_get_contents("$config/live/$path/$file"));
            }
            else  {
                $File[$folder]['CONTENT'] = explode("\n",file_get_contents("$config/remote/$folder/$path/$file"));
            }            
        }
        
        // on supprime les premières lignes equivalentes

        $nb = count($Folders);
        $n = 0;
        do {
            $folder = $Folders[0];
            if (isset($File[$folder]['CONTENT'][$n])) {
                $l = $File[$folder]['CONTENT'][$n];
                $egal = true;
            }
            else {
                $egal = false;
            }
            for($i=1;$i<$nb;$i++) {
                $folder = $Folders[$i];
                if (!isset($File[$folder]['CONTENT'][$n]) or ($File[$folder]['CONTENT'][$n]!=$l)) 
                    $egal = false;
            }
            $n++;
        } while ($egal);

        // Values
        $Val = array('SIZE','MTIME','MD5');
        print '<table border=1>';
        print "<tr><td></td>";
        foreach(array_keys($File) as $k) {
            print "<th>$k</th>";
        }
        print "</tr>";
        print "<tr>";
        print "<th>Size</th>";
        foreach ($Folders as $k ) {
            print '<td align="right">'.$File[$k]['SIZE'].'</td>';
        }
        print "</tr>";
        print "<tr>";
        print "<th>Date</th>";
        foreach ($Folders as $k ) {
            print '<td align="right">'.$this->Localtime2ISO($File[$k]['MTIME']).'</td>';
        }
        print "</tr>";
        print "<tr>";
        print "<th>MD5</th>";
        foreach ($Folders as $k ) {
            print '<td align="right">'.$File[$k]['MD5'].'</td>';
        }
        print "</tr>";
        print '</table>';
        
        foreach($Folders as $k) {
            print "<h2>$k</h2><pre>";
            for($i=$n-3;$i<$n+3;$i++) {
                if ($i==$n-1) print "<b>";
                if (isset($File[$k]['CONTENT'][$i]))
                    print "[$i] ".str_replace(array('<','>'),array('&lt;','&gt;'),$File[$k]['CONTENT'][$i] )."<br/>";                
                if ($i==$n-1) print "</b>";
            }
            print "</pre>";
        }
        exit();
    }
    
    private function Localtime2ISO($t) {
        $lt = localtime($t, true);
        return sprintf("%04d-%02d-%02d %02d:%02d:%02d",
            $lt['tm_year']+1900,$lt['tm_mon']+1,$lt['tm_mday'],$lt['tm_hour'],$lt['tm_min'],$lt['tm_sec']);
    }
    
    public function hot_foldersAction($filter='99',$refresh=false) {
        
        $request = Request::createFromGlobals();
        if ($request->query->get( 'refresh' )=='true') {
            $refresh = true;
        }
        if ($refresh) {
            $this->syncAction();
        }
        if ($request->query->get( 'cat' )!='') {
            $filter = $request->query->get( 'cat' );
        }
        $dhtmlx = $this->container->get('arii_core.db');
        
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');        
        $qry = $sql->Select(array('ID','FOLDER','PATH','FILE','NAME','TYPE','CAT','SIZE','CRC','MD5'))
                .$sql->From(array('JOE_FILE'))
                ." where CAT in (".$filter.")"
                .$sql->Order(array('PATH','FILE')); 
        
        $res = $data->sql->query( $qry );
        $key_files = array();
        while ( $line = $data->sql->get_next($res) ) {
            $id  =  $line['ID'];
            if ($line['PATH']!='')
                $line['PATH'] = '/'.$line['PATH'];
            if ($line['CAT']>0) {
                if ($line['TYPE'] == 'order') {
                    list($chain,$order) = explode(',',$line['NAME']);
                    $file =  $line['PATH'].'/'.$chain.'.job_chain.xml/'.$line['FILE'];
                    $Info[$file]['LABEL'] = $order;
                }
                elseif ($line['TYPE'] == 'config') {
                    $file =  $line['PATH'].'/'.$line['NAME'].'.job_chain.xml/'.$line['FILE'];
                    $Info[$file]['LABEL'] = $line['NAME'];
                }
                else {
                    $file =  $line['PATH'].'/'.$line['FILE'];
                    $Info[$file]['LABEL'] = $line['NAME'];
                }
            }
            else {
                    $file =  $line['PATH'].'/'.$line['FILE'];
                    $Info[$file]['LABEL'] = $line['FILE'];                
            }
                            
            # Verification du MD5
            if (isset($Info[$file]['MD5'])) {
                if ($line['MD5']!=$Info[$file]['LastMD5'])
                    $Info[$file]['MD5'] = true;
            } 
            else {
                $Info[$file]['MD5'] = false;
            }
            $Info[$file]['LastMD5'] = $line['MD5'];
            
            # ajout des ids (pour le filtre DHTMLX
            if (isset($Info[$file]['IDS'])) {
                $Info[$file]['IDS'] .= ",$id";
            } 
            else {
                $Info[$file]['IDS'] = $id;
            }
                        
            foreach (array('NAME','FILE') as $k) {
                $Info[$file][$k] = $line[$k];
            }
            if ($line['CAT']>0) {
                $Info[$file]['TYPE']= $line['TYPE'];
            }
            elseif ($line['CAT']==0) {
                $Info[$file]['TYPE']='xml';
            }
            elseif ($line['CAT']==-1) {
                $Info[$file]['TYPE']= $line['TYPE'];;
            }
            else {
                $Info[$file]['TYPE']='unknown';
            }
            
            $file .= '/'.$line['FOLDER'];            
            foreach (array('ID','SIZE','FOLDER','MD5') as $k) {
                $File[$file][$k] = $line[$k];
            }
            $key_files[$file] = $file;
        }   
        
        if (empty($key_files)) exit();
        
        $tools = $this->container->get('arii_core.tools');
        $tree = $tools->explodeTree($key_files, "/");
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<rows>\n";
        $list .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        /*implode(' ',$status)
        SUCCESS FAILURE
        strpos(SUCCESS )*/
        $list .= $this->HotFolder2XML( $tree, '', $Info, $File );
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

   function HotFolder2XML( $leaf, $id = '', $Info, $File ) {
            $return = '';
            if (is_array($leaf)) {
                    foreach (array_keys($leaf) as $k) {
                            $Ids = explode('/',$k);
                            $here = array_pop($Ids);
                            $i  = "$id/$k";
                            # On ne prend que l'historique
                            if (isset($Info[$i])) {
                                $return .= '<row id=".'.$Info[$i]['IDS'].'">';
                                $return .= '<cell image="'.$Info[$i]['TYPE'].'.png" title="'.$Info[$i]['FILE'].'"> '.$Info[$i]['LABEL'].'</cell>';
                                if ($Info[$i]['MD5']) {
                                    $return .=  '<cell>diff</cell>';
                                    $return .=  '<cell><![CDATA[<img src="'.$this->images.'/diff.png"/>]]></cell>';
                                }
                                else {
                                    $return .=  '<cell/>';
                                    $return .=  '<cell/>';
                                }
                                $return .=  '<userdata name="type">file</userdata>';
                            }
                            elseif (isset($File[$i])) {
                                $return .= '<row id="'.$File[$i]['ID'].'">';
                                if (($File[$i]['FOLDER'] =='live') || ($File[$i]['FOLDER'] =='_all')) {
                                    $icon = $File[$i]['FOLDER'];
                                }
                                else {
                                    $icon = 'remote';
                                }
                                $return .= '<cell image="'.$icon.'.png"> '.$here.'</cell>';
                                $return .= '<cell/>';
                                $return .=  '<cell style="background-color: #'.substr($File[$i]['MD5'],0,6).';"></cell>';
                                $return .=  '<userdata name="type">version</userdata>';
                                
                            }
                            else {
                                $return .=  '<row id="'.$i.'" open="1">';
                                $return .=  '<cell image="folder.gif">'.$here.'</cell>';
                            }
                           $return .= $this->HotFolder2XML( $leaf[$k], $id.'/'.$k, $Info, $File);
                           $return .= '</row>';
                    }
            }
            return $return;
    }

    public function treeAction($folder='live',$refresh=false) {

        $request = Request::createFromGlobals();
        if ($request->query->get( 'refresh' )=='true') {
            $refresh = true;
        }
        if ($refresh) {
            $this->syncAction();
        }
        if ($request->query->get( 'cat' )!='') {
            $filter = $request->query->get( 'cat' );
        }
        $dhtmlx = $this->container->get('arii_core.db');
        
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');        
        $qry = $sql->Select(array('ID','FOLDER','PATH','FILE','NAME','TYPE','CAT'))
                .$sql->From(array('JOE_FILE'))
                .$sql->Where(array('FOLDER' => $folder))
                .$sql->Order(array('PATH','FILE')); 

        $res = $data->sql->query( $qry );
        $key_files = array();
        while ( $line = $data->sql->get_next($res) ) {
            $id  =  $line['ID'];
            if ($line['PATH']!='')
                $line['PATH'] = '/'.$line['PATH'];
            if ($line['CAT']>0) {
                if ($line['TYPE'] == 'order') {
                    list($chain,$order) = explode(',',$line['NAME']);
                    $file =  $line['PATH'].'/'.$chain.'.job_chain.xml/'.$line['FILE'];
                    $Info[$file]['LABEL'] = $order;
                }
                elseif ($line['TYPE'] == 'config') {
                    $file =  $line['PATH'].'/'.$line['NAME'].'.job_chain.xml/'.$line['FILE'];
                    $Info[$file]['LABEL'] = $line['NAME'];
                }
                else {
                    $file =  $line['PATH'].'/'.$line['FILE'];
                    $Info[$file]['LABEL'] = $line['NAME'];
                }
            }
            else {
                    $file =  $line['PATH'].'/'.$line['FILE'];
                    $Info[$file]['LABEL'] = $line['FILE'];                
            }
                            
            # ajout des ids (pour le filtre DHTMLX
            if (isset($Info[$file]['IDS'])) {
                $Info[$file]['IDS'] .= ",$id";
            } 
            else {
                $Info[$file]['IDS'] = $id;
            }
                        
            foreach (array('NAME','FILE') as $k) {
                $Info[$file][$k] = $line[$k];
            }
            if ($line['CAT']>0) {
                $Info[$file]['TYPE']= $line['TYPE'];
            }
            elseif ($line['CAT']==0) {
                $Info[$file]['TYPE']='xml';
            }
            elseif ($line['CAT']==-1) {
                $Info[$file]['TYPE']= $line['TYPE'];;
            }
            else {
                $Info[$file]['TYPE']='unknown';
            }
            
            $key_files[$file] = $file;
        }   
        
        if (empty($key_files)) exit();

        asort($key_files);
        $tools = $this->container->get('arii_core.tools');
        $tree = $tools->explodeTree($key_files, "/");
      
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $list = '<?xml version="1.0" encoding="UTF-8"?>';
        $list .= "<tree id='0'>\n";
        $list .= $this->Folder2Tree( $tree, '', $Info );
        $list .= "</tree>\n";
        $response->setContent( $list );
        return $response;
   }
    
   function Folder2Tree( $leaf, $id = '', $Info ) {
        $return = '';
        if (is_array($leaf)) {
                foreach (array_keys($leaf) as $k) {
                        $Ids = explode('/',$k);
                        $here = array_pop($Ids);
                        $i  = "$id/$k";

                        if (isset($Info[$i])) {
                            $return .= '<item id="'.$Info[$i]['IDS'].'" im0="'.$Info[$i]['TYPE'].'.png" text="'.$here.'">';
                        }
                        else {
                            $return .=  '<item id="'.$i.'" im0="folder.gif" text="'.$here.'">';
                        }
                       $return .= $this->Folder2Tree( $leaf[$k], $id.'/'.$k, $Info);
                       $return .= '</item>';
                }
        }
        return $return;
    }
}
