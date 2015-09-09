<?php
// src/Arii/JIDBundle/Service/AriiHistory.php
/*
 * Recupere les données et fournit un tableau pour les composants DHTMLx
 */ 
namespace Arii\MFTBundle\Service;

class AriiHistory
{
    protected $db;
    protected $sql;
    protected $date;
    protected $tools;
    
    public function __construct (  
            \Arii\CoreBundle\Service\AriiDHTMLX $db, 
            \Arii\CoreBundle\Service\AriiSQL $sql,
            \Arii\CoreBundle\Service\AriiDate $date,
            \Arii\CoreBundle\Service\AriiTools $tools ) {
        $this->db = $db;
        $this->sql = $sql;
        $this->date = $date;
        $this->tools = $tools;
    }

/*********************************************************************
 * Informations de connexions
 *********************************************************************/
   public function Transfers($history=0,$only_warning= 1) {   
        $data = $this->db->Connector('data');
        $sql = $this->sql;
        $date = $this->date;
        
        $Fields = array(
            '{start_time}' => 'h.TRANSFER_TIMESTAMP'
        );
        
        $qry = $sql->Select(array('h.GUID','f.ID','f.MANDATOR','f.SOURCE_HOST','f.SOURCE_DIR','f.SOURCE_FILENAME','f.FILE_SIZE', 
        'h.TRANSFER_TIMESTAMP','h.TARGET_HOST','h.TARGET_DIR','h.TARGET_FILENAME','h.OPERATION','h.PROTOCOL','h.PORT','h.STATUS')) 
        .$sql->From(array('SOSFTP_FILES_HISTORY h'))
        .$sql->LeftJoin('SOSFTP_FILES f',array('h.SOSFTP_ID','f.ID'))
        .$sql->Where($Fields)
        .$sql->OrderBy(array('h.TRANSFER_TIMESTAMP desc','f.MANDATOR','f.SOURCE_HOST','f.SOURCE_DIR','f.SOURCE_FILENAME'));

        $res = $data->sql->query( $qry );
        $nb=0;
        $Transfers = array();
        while ($line = $data->sql->get_next($res)) {
            $id = $line['ID'];
            
            if (isset($Done[$id])) {
                $Done[$id]++;
            }
            else {
                $Done[$id]=0;
            }
            if ($Done[$id]>$history) continue;

            list($source_dir,$source_file) = $this->DirFile($line['SOURCE_DIR'],$line['SOURCE_FILENAME']);
            list($target_dir,$target_file) = $this->DirFile($line['TARGET_DIR'],$line['TARGET_FILENAME']);
            // source dir ?
            $mandator = $line['MANDATOR'];
            $host = $mandator.'/'.$line['SOURCE_HOST'];
            $dir = $host.'/'.str_replace('/','¤',$source_dir);
            $target_host = $dir.'/'.$line['OPERATION'].'|'.$line['TARGET_HOST'];
            $target_dir = $target_host.'/'.$line['PROTOCOL'].'|'.str_replace('/','¤',$target_dir);

            $Transfers[$id]['DBID'] = $line['GUID'];
            $Transfers[$id]['SOURCE_DIR'] = $source_dir;
            $Transfers[$id]['SOURCE_FILE'] = $source_file;
            $Transfers[$id]['TARGET_DIR'] = $source_dir;
            $Transfers[$id]['TARGET_FILE'] = $source_file;
            $Transfers[$id] = $line;
        }
        
        return $Transfers;
   }

   protected function DirFile($dir,$file) {
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
