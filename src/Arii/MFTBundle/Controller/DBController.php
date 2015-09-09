<?php
// src/Arii/MFTBundle/Controller/TransfersController.php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DBController extends Controller
{
    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');          
    }

    public function sosftp_historyAction()
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('grid');
        $sql = $this->container->get('arii_core.sql');
        
        $qry = $sql->Select(array('h.GUID','f.ID','f.MANDATOR','f.SOURCE_HOST','f.SOURCE_DIR','f.SOURCE_FILENAME','f.FILE_SIZE', 
        'h.TRANSFER_TIMESTAMP','h.TARGET_HOST','h.TARGET_DIR','h.OPERATION','h.PROTOCOL','h.STATUS')) 
        .$sql->From(array('SOSFTP_FILES f'))
        .$sql->LeftJoin('SOSFTP_FILES_HISTORY h',array('f.ID','h.SOSFTP_ID'))
        .$sql->OrderBy(array('h.TRANSFER_TIMESTAMP desc'));
        $data->event->attach("beforeRender", array( $this, "color_rows") );
        $data->render_sql($qry,"GUID","TRANSFER_TIMESTAMP,MANDATOR,STATUS,SOURCE_HOST,OPERATION,TARGET_HOST,SOURCE_FILENAME");
    }
    
    function color_rows($row){
            if ($row->get_value("STATUS")=='success') {
                    $row->set_row_attribute("class","backgroundsuccess");
            }
            else {
                    $row->set_row_attribute("class","backgroundfailure");
            }
            $source = $row->get_value("SOURCE_DIR");
            if ($row->get_value("SOURCE_FILENAME")!='null') {
                $source .= $row->get_value("SOURCE_FILENAME");
            }            
            $row->set_value("SOURCE_FILENAME",$source);
            
    }
    
    public function historyAction($history=0)
    {
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        $sql = $this->container->get('arii_core.sql');
        
        $request = Request::createFromGlobals();
        if ($request->query->get( 'history' )>0) {
            $history = $request->query->get( 'history' );
        }
        $qry = $sql->Select(array('h.GUID','f.ID','f.MANDATOR','f.SOURCE_HOST','f.SOURCE_DIR','f.SOURCE_FILENAME','f.FILE_SIZE', 
        'h.TRANSFER_TIMESTAMP','h.TARGET_HOST','h.TARGET_DIR','h.TARGET_FILENAME','h.OPERATION','h.PROTOCOL','h.STATUS')) 
        .$sql->From(array('SOSFTP_FILES_HISTORY h'))
        .$sql->LeftJoin('SOSFTP_FILES f',array('h.SOSFTP_ID','f.ID'))
        .$sql->OrderBy(array('h.TRANSFER_TIMESTAMP desc','f.MANDATOR','f.SOURCE_HOST','f.SOURCE_DIR','f.SOURCE_FILENAME'));

        $res = $data->sql->query( $qry );
        $nb=0;
        $key_files = array();
        while ($line = $data->sql->get_next($res)) {
            list($source_dir,$source_file) = $this->DirFile($line['SOURCE_DIR'],$line['SOURCE_FILENAME']);
            list($target_dir,$target_file) = $this->DirFile($line['TARGET_DIR'],$line['TARGET_FILENAME']);
            // source dir ?
            $mandator = $line['MANDATOR'];
            $host = $mandator.'/'.$line['SOURCE_HOST'];
            $dir = $host.'/'.str_replace('/','¤',$source_dir);
            $target_host = $dir.'/'.$line['OPERATION'].'|'.$line['TARGET_HOST'];
            $target_dir = $target_host.'/'.$line['PROTOCOL'].'|'.str_replace('/','¤',$target_dir);
            $id = $target_dir.'/'.$source_file.'|'.$target_file;

            $unic = $id;
            if (isset($Done[$unic])) {
                $Done[$unic]++;
            }
            else {
                $Done[$unic]=0;
                if (!isset($Mandator[$mandator]['size'])) {
                    $Mandator[$mandator]['size']=0;
                    $Mandator[$mandator]['total']=0;
                    $Mandator[$mandator]['success']=0;
                }
                $Mandator[$mandator]['size'] += $line['FILE_SIZE'];
                $Mandator[$mandator]['total']++;
                if ($line['STATUS']=='success') {
                    $Mandator[$mandator]['success']++;
                }
                if (!isset($Mandator[$mandator]['timestamp'])) {
                    $Mandator[$mandator]['timestamp'] = $line['TRANSFER_TIMESTAMP'];
                }
                
                if (!isset($Host[$host]['size'])) {
                    $Host[$host]['size']=0;
                    $Host[$host]['total']=0;
                    $Host[$host]['success']=0;
                }
                $Host[$host]['size'] += $line['FILE_SIZE'];
                $Host[$host]['total']++;
                if ($line['STATUS']=='success') {
                    $Host[$host]['success']++;
                }

                if (!isset($Host[$host]['timestamp'])) {
                    $Host[$host]['timestamp'] = $line['TRANSFER_TIMESTAMP'];
                }                
                
                if (!isset($Dir[$dir]['size'])) {
                    $Dir[$dir]['size']=0;
                    $Dir[$dir]['total']=0;
                    $Dir[$dir]['success']=0;
                }                
                $Dir[$dir]['size'] += $line['FILE_SIZE'];
                $Dir[$dir]['total']++;
                if (!isset($Dir[$dir]['timestamp'])) {
                    $Dir[$dir]['timestamp'] = $line['TRANSFER_TIMESTAMP'];
                }  
                if ($line['STATUS']=='success') {
                    $Dir[$dir]['success']++;
                }
              
                if (!isset($THost[$target_host]['size'])) {
                    $THost[$target_host]['size']=0;
                    $THost[$target_host]['total']=0;
                    $THost[$target_host]['success']=0;
                }                
                $THost[$target_host]['size'] += $line['FILE_SIZE'];
                $THost[$target_host]['total']++;
                if (!isset($THost[$target_host]['timestamp'])) {
                    $THost[$target_host]['timestamp'] = $line['TRANSFER_TIMESTAMP'];
                }  
                if ($line['STATUS']=='success') {
                    $THost[$target_host]['success']++;
                }

                if (!isset($TDir[$target_dir]['size'])) {
                    $TDir[$target_dir]['size']=0;
                    $TDir[$target_dir]['total']=0;
                    $TDir[$target_dir]['success']=0;
                }                
                $TDir[$target_dir]['size'] += $line['FILE_SIZE'];
                $TDir[$target_dir]['total']++;
                if (!isset($TDir[$target_dir]['timestamp'])) {
                    $TDir[$target_dir]['timestamp'] = $line['TRANSFER_TIMESTAMP'];
                }  
                if ($line['STATUS']=='success') {
                    $TDir[$target_dir]['success']++;
                }
               
            }

            if ($Done[$unic]>$history) continue;
            if ($Done[$unic]>0) {
                $id = $target_dir.'/'.$source_file.'|'.$target_file.'/!'.$Done[$unic];
            }
            $key_files[$id] = $id;
            $Info[$id] = $line['GUID'].'|'.$line['TRANSFER_TIMESTAMP'].'|'.$line['TARGET_HOST'].'|'.$line['TARGET_DIR'].'|'.$line['OPERATION'].'|'.$line['PROTOCOL'].'|'.$line['STATUS'].'|'.$line['FILE_SIZE'];
        }
        
        $tools = $this->container->get('arii_core.tools');
        $tree = $tools->explodeTree($key_files, "/");

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        if (count($key_files)==0) {
            $response->setContent( $this->NoRecord() );
            return $response;
        }

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
        $list .= $this->MFT2XML( $tree, '', $Info, $Mandator, $Host, $Dir, $THost, $TDir);
        $list .= "</rows>\n";
        $response->setContent( $list );
        return $response;
    }

    function MFT2XML( $leaf, $id = '', $Info, $Mandator, $Host, $Dir, $THost, $TDir ) {
        $date = $this->container->get('arii_core.date');        
        $color = array (
            'SUCCESS' => '#ccebc5',
            'RUNNING' => '#ffffcc',
            'FAILURE' => '#fbb4ae',
            'STOPPED' => '#FF0000'
            );
        $return = '';
        if (is_array($leaf)) {
                foreach (array_keys($leaf) as $k) {
                        $Ids = explode('/',$k);
                        $here = array_pop($Ids);
                        $i  = substr("$id/$k",1);
                        # On ne prend que l'historique
                        if (isset($Info[$i])) {
                            $cell = $open = '';
                            list( $dbid,$timestamp,$target_host,$target_dir,$operation, $protocol, $status,$filesize ) = explode('|',$Info[$i]);
                            if ($status == 'success') {
                                $color='#ccebc5';
                            }
                            else {
                                $color='#fbb4ae';
                            }
                            if (substr($here,0,1)=='!') { // historique
                                $here = substr($here,1);
                                $icon = 'database';
                                $old = $here;
                                $ren = '';
                            }
                            else {
                                if ($status == 'success') {
                                    $icon= 'bullet_green';                                
                                }
                                else {
                                    $icon= 'bullet_red';                                
                                }
                                list($old,$new) = explode('|',$here);
                                if ($old == $new) {
                                    $ren = '';
                                }
                                else {
                                    $ren = ' -> '.$new;
                                }
                            }
                            $return .= '<row id="'.$dbid.'" style="background-color: '.$color.';"'.$open.'>';
                            $cell .= '<cell image="'.$icon.'.png">'.$old.$ren.'</cell>';
                            $cell .= '<cell>'.$timestamp.'</cell>';
                            $cell .= '<cell>'.$status.'</cell>';
                            $cell .= '<cell/>';
                            $cell .= '<cell>'.$filesize.'</cell>';
                            $return .= $cell;
                        }
                        elseif (isset($Mandator[$i])) {
                                    if ($Mandator[$i]['success']==$Mandator[$i]['total']) {
                                        $percent = '100';
                                        $col = '#ccebc5';
                                        $status = 'complete';
                                   }
                                    else {
                                        $percent = round($Mandator[$i]['success']*100/$Mandator[$i]['total']);
                                        $col = '#fbb4ae';
                                        $status = 'failure';
                                    }
                                    // $open = ' open="1"';
                                    $open = '';
                                    $return .=  '<row id="'.$i.'"'.$open.' style="background-color: '.$col.';">';
                                    $return .=  '<userdata name="type">mandator</userdata>';
                                    $return .=  '<cell image="package.png">'.$here.'</cell>';
                                    $return .=  '<cell>'.$Mandator[$i]['timestamp'].'</cell>';
                                    $return .=  '<cell>'.$status.'</cell>';
                                    $return .=  '<cell>'.$percent.'</cell>';
                                    $return .=  '<cell>'.$Mandator[$i]['size'].'</cell>';                            
                        }
                        elseif (isset($Host[$i])) {
                                    if ($Host[$i]['success']==$Host[$i]['total']) {
                                        $percent = '100';
                                        $col = '#ccebc5';
                                        $status = 'complete';
                                   }
                                    else {
                                        $percent = round($Host[$i]['success']*100/$Host[$i]['total']);
                                        $col = '#fbb4ae';
                                        $status = 'failure';
                                    }
                                    // $open = ' open="1"';
                                    $open = '';
                                    $return .=  '<row id="'.$i.'"'.$open.' style="background-color: '.$col.';">';
                                    $return .=  '<userdata name="type">host</userdata>';
                                    $return .=  '<cell image="server.png">'.$here.'</cell>';
                                    $return .=  '<cell>'.$Host[$i]['timestamp'].'</cell>';
                                    $return .=  '<cell>'.$status.'</cell>';
                                    $return .=  '<cell>'.$percent.'</cell>';
                                    $return .=  '<cell>'.$Host[$i]['size'].'</cell>';                            
                        }
                        elseif (isset($THost[$i])) {
                                    list($operation, $target) = explode('|',$here); 
                                    if ($THost[$i]['success']==$THost[$i]['total']) {
                                        $percent = '100';
                                        $col = '#ccebc5';
                                        $status = 'complete';
                                   }
                                    else {
                                        $percent = round($THost[$i]['success']*100/$THost[$i]['total']);
                                        $col = '#fbb4ae';
                                        $status = 'failure';
                                    }
                                    // $open = ' open="1"';
                                    $open = '';
                                    $return .=  '<row id="'.str_replace('|','/',$i).'"'.$open.' style="background-color: '.$col.';">';
                                    $return .=  '<userdata name="type">host</userdata>';
                                    $return .=  '<cell image="'.$operation.'.png">'.$target.'</cell>';
                                    $return .=  '<cell>'.$THost[$i]['timestamp'].'</cell>';
                                    $return .=  '<cell>'.$status.'</cell>';
                                    $return .=  '<cell>'.$percent.'</cell>';
                                    $return .=  '<cell>'.$THost[$i]['size'].'</cell>';                            
                        }
                        elseif (isset($Dir[$i])) {
                                    if ($Dir[$i]['success']==$Dir[$i]['total']) {
                                        $percent = '100';
                                        $col = '#ccebc5';
                                        $status = 'complete';
                                   }
                                    else {
                                        $percent = round($Dir[$i]['success']*100/$Dir[$i]['total']);
                                        $col = '#fbb4ae';
                                        $status = 'failure';
                                    }
                                    // $open = ' open="1"';
                                    $open = '';
                                    $return .=  '<row id="'.$i.'"'.$open.' style="background-color: '.$col.';">';
                                    $return .=  '<userdata name="type">host</userdata>';
                                    $return .=  '<cell image="folder.gif">'.str_replace('¤','/',$here).'</cell>';
                                    $return .=  '<cell>'.$Dir[$i]['timestamp'].'</cell>';
                                    $return .=  '<cell>'.$status.'</cell>';
                                    $return .=  '<cell>'.$percent.'</cell>';
                                    $return .=  '<cell>'.$Dir[$i]['size'].'</cell>';                            
                        }
                        elseif (isset($TDir[$i])) {
                                    list($protocol,$dir)=explode('|',$here);
                                    if ($TDir[$i]['success']==$TDir[$i]['total']) {
                                        $percent = '100';
                                        $col = '#ccebc5';
                                        $status = 'complete';
                                   }
                                    else {
                                        $percent = round($TDir[$i]['success']*100/$TDir[$i]['total']);
                                        $col = '#fbb4ae';
                                        $status = 'failure';
                                    }
                                    // $open = ' open="1"';
                                    $open = '';
                                    $return .=  '<row id="'.str_replace('|','/',$i).'"'.$open.' style="background-color: '.$col.';">';
                                    $return .=  '<userdata name="type">host</userdata>';
                                    $return .=  '<cell image="folder.gif">['.$protocol.'] '.str_replace('¤','/',$dir).'</cell>';
                                    $return .=  '<cell>'.$TDir[$i]['timestamp'].'</cell>';
                                    $return .=  '<cell>'.$status.'</cell>';
                                    $return .=  '<cell>'.$percent.'</cell>';
                                    $return .=  '<cell>'.$TDir[$i]['size'].'</cell>';                            
                        }
                        else {
                                {
                                    # on compte le nombre de 
                                    $return .=  '<row id="'.$i.'">';
                                    $return .=  '<userdata name="type">folder</userdata>';
                                    $return .=  '<cell image="folder.gif">'.$here.'</cell>';
                                    $return .=  '<cell>'.$i.'('.$k.')</cell>';
                                }
                        }
                       $return .= $this->MFT2XML( $leaf[$k], $id.'/'.$k, $Info, $Mandator, $Host, $Dir, $THost, $TDir );
                       $return .= '</row>';
                }
        }
        return $return;
    }

    public function detailAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->query->get( 'id' );

        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('data');
        
        $sql = $this->container->get('arii_core.sql');

        $qry = $sql->Select(array('h.GUID','f.ID','f.MANDATOR','f.SOURCE_HOST','f.SOURCE_DIR','f.SOURCE_FILENAME','f.FILE_SIZE', 
        'h.TRANSFER_TIMESTAMP','h.TARGET_HOST','h.TARGET_DIR','h.TARGET_FILENAME','h.OPERATION','h.PROTOCOL','h.STATUS')) 
        .$sql->From(array('SOSFTP_FILES f'))
        .$sql->LeftJoin('SOSFTP_FILES_HISTORY h',array('f.ID','h.SOSFTP_ID'))
        .$sql->Where(array('h.GUID'=>$id))
        .$sql->OrderBy(array('h.TRANSFER_TIMESTAMP desc'));

        $res = $data->sql->query( $qry );
        $line = $data->sql->get_next($res);

        list($line['SOURCE_DIR'],$line['SOURCE_FILENAME']) = $this->DirFile($line['SOURCE_DIR'],$line['SOURCE_FILENAME']);
        list($line['TARGET_DIR'],$line['TARGET_FILENAME']) = $this->DirFile($line['TARGET_DIR'],$line['TARGET_FILENAME']);
        return $this->render('AriiMFTBundle:Default:detail.html.twig', $line );
    }

    function DirFile($dir,$file) {
        if ($dir=='./') $dir='';
        if ($dir=='\\.') $dir='';        
        if ($dir != '') {
            $filename = str_replace(array('/./','\\.\\'),array('/','\\'),$dir.'/'.$file);
        }
        else {
            $filename = str_replace(array('/./','\\.\\'),array('/','\\'),$file);
        }
        
        // Pb dirname entre Windows et Unix
        if ((strpos($filename,':')) or (substr($filename,0,2)=='\\') or (strpos($filename,'\\')))  {
            // print "Windows";
            $filename = str_replace('/','\\',$filename);
            $sep = '\\';
        }
        else {
            // print "Unix";
            $filename = str_replace('\\','/',$filename);
            $sep = '/';
        }
        $Parts = explode($sep,$filename);
        $file = array_pop($Parts);
        $dir = str_replace($sep.$sep,$sep,implode($sep,$Parts));
        // esthetique
        if (substr($dir,-2)=='\.') $dir = substr($dir,0,strlen($dir)-2);
        if (substr($dir,-1)=='/') $dir = substr($dir,0,strlen($dir)-1);
/*        print "($filename)($dir)($file)";
        exit();
*/        return array($dir,$file);
    }
}