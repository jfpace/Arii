<?php
// src/Arii/JOCBundle/Service/AriiSOS.php
 
namespace Arii\JOCBundle\Service;

class AriiTreeGrid
{
    protected $db;
    protected $sql;
    
    public function __construct() {
    }

/*********************************************************************
 * Informations de connexions
 *********************************************************************/
    public function openState($Open, $State,$rowid) {
        if (isset($Open[$rowid]) and ($Open[$rowid])) return ' open="1"';
        if (isset($State[$rowid])) {
            if ($State[$rowid]==1)
                return ' open="1"';
        }
        return '';
    }

    public function setTitle($name, $title) {
        $info  = $this->XMLProtect($name);
        if (trim($title)!='') {
            if (substr($title,0,3)!='<a ')                    
                $info .= ' <font color="grey">('.$this->XMLProtect($title).')</font>';
            else 
                $info .= ' '.$title;
        }
        return '<![CDATA[ '.$info.']]>';
    }
    
    public function getStyle($status) {
        if ($status=='STOPPED') {
            return ' style="background-color: red; color: yellow;"';
        }
        elseif ($status=='JOB STOP') {
            return ' style="background-color: red; color: yellow;"';
        }
        elseif ($status=='RUNNING') {
            return ' style="background-color: #ffffcc;"';
        }
        elseif ($status=='SETBACK') {
            return ' style="background-color: #ffffcc; color: red;"';
        }
        elseif ($status=='ENDED') {
            return ' style="background-color: #fbb4ae;"';
        }
        elseif ($status=='FAILURE') {
            return ' style="background-color: #fbb4ae;"';
        }
        elseif ($status=='PAUSED') {
            return ' style="background-color: #fbb4ae;"';
        }
        elseif ($status=='SUCCESS') {
            return ' style="background-color: #ccebc5;"';
        }
        elseif ($status=='READY') {
            return ' style="background-color: #ccebc5;"';
        }
        elseif ($status=='SKIPPED') {
            return ' style="background-color: orange;"';
        }
        elseif ($status=='SUSPENDED') {
            return ' style="background-color: red; color: yellow"';
        } 
        elseif ($status=='BLACKLIST') {
            return ' style="background-color: grey;"';
        } 
        elseif ($status=='DELAYED') {
            return ' style="background-color: red;"';
        } 
        return '';
    }

    public function XMLProtect ($txt) {
        $txt = str_replace('<','&lt;',$txt);
        $txt = str_replace('>','&gt;',$txt);
       return $txt;
    }
   
}
