<?php
// src/Arii/JOCBundle/Service/AriiState.php
/*
 * Recupere les données et fournit un tableau pour les composants DHTMLx
 */ 
namespace Arii\JOCBundle\Service;

class AriiState
{
    protected $db;
    protected $sql;
    protected $date;
    
    public function __construct (  
            \Arii\CoreBundle\Service\AriiDB $db, 
            \Arii\CoreBundle\Service\AriiSQL $sql,
            \Arii\CoreBundle\Service\AriiDate $date ) {
        $this->db = $db;
        $this->sql = $sql;
        $this->date = $date;
    }

/*********************************************************************
 * Informations de connexions
 *********************************************************************/
   public function Jobs($ordered = false, $only_warning= true) {   
        $date = $this->date;        
        $sql = $this->sql;
        $db = $this->db;
        $data = $db->Connector('data');
        
        // Jobs
        $Fields = array( '{spooler}' => 'NAME',
                         '{job_name}'   => 'PATH' );

        if (!$ordered) {
            $Fields['{standalone2}'] = 'ORDERED';
        }
        if ($only_warning) {
            $Fields['{!pending}'] = 'STATE';
        }
        $qry = $sql->Select(array('SPOOLER_NAME as SPOOLER',
                        'ID','PATH','STATE','STATE_TEXT','TITLE','UPDATED','ORDERED','NEXT_START_TIME','LAST_INFO','LEVEL','HIGHEST_LEVEL','LAST_WARNING','LAST_ERROR','ERROR','WAITING_FOR_PROCESS','ORDERED','PROCESS_CLASS_NAME','SCHEDULE_NAME' ))
                .$sql->From(array('FOCUS_JOBS'))
                .$sql->Where($Fields)
                .$sql->OrderBy(array('SPOOLER_NAME','PATH'));

        $res = $data->sql->query($qry);
        $Jobs = array();
        while ($line = $data->sql->get_next($res))
        {            
            $jn = $line['SPOOLER'].'/'.$line['PATH'];
            $jn = str_replace("//", "/", $jn);
            $joid = $line['ID'];

            $Jobs[$jn] =$line;

            $Jobs[$jn]['DBID'] = $joid;
            $Jobs[$jn]['FOLDER'] = dirname($line['PATH']);
            $Jobs[$jn]['NAME'] = basename($line['PATH']);
            # Remontees d'informations
            $Jobs[$jn]['STATE'] = strtoupper($line['STATE']);
        }
        return $Jobs;
   }

   public function Spoolers() {   
        $date = $this->date;        
        $sql = $this->sql;
        $db = $this->db;
        $data = $db->Connector('data');
        
        // Jobs
        $Fields = array( '{spooler}' => 's.NAME' );
        $qry = $sql->Select(array('s.ID','s.NAME as SPOOLER','s.STATE','s.VERSION','s.HOST','s.IP_ADDRESS','s.TCP_PORT','s.SPOOLER_ID','s.NEED_DB','s.UPDATED','s.TIME'))
               .$sql->From(array('FOCUS_SPOOLERS s'))
               .$sql->OrderBy(array('s.NAME'));

        $res = $data->sql->query($qry);
        $Spoolers = array();
        while ($line = $data->sql->get_next($res))
        {            
           // $sn = $line['IP_ADDRESS'].':'.$line['TCP_PORT'];
            $sn = $line['ID']; // pour les doublons
            $Spoolers[$sn] = $line;
            $Spoolers[$sn]['DBID'] = $line['ID'];
            
            // Calcul du statut
            $last = time() - $line['UPDATED'];
            $Spoolers[$sn]['LAST_UPDATE'] = $last;
            if ($last>300) {
                $status = 'LOST';
            }
            elseif ($line['STATE']=='paused') {
                $status = 'PAUSED';
            }
            elseif ($line['STATE']=='running') {
                $status = 'RUNNING';
            }
            elseif ($line['SPOOLER_ID']=='') {
                $status = 'UNKNOWN';
            }
            else {
                $status = $line['STATE'];
            }
            $Spoolers[$sn]['STATUS'] = $status;
            $Spoolers[$sn]['TIME'] = $date->Date2Local($line['TIME'],$line['SPOOLER']);
        }
        return $Spoolers;
   }

   public function Orders( $nested=0, $only_warning= true, $sort='last', $id=0) {
        $date = $this->date;
        $sql = $this->sql;
        $db = $this->db;
        $data = $db->Connector('data');

        $Fields = array( '{spooler}' => 'fs.NAME',
                         '{job_chain}'   => 'fo.PATH',
                         '{order_id}' => 'fo.NAME' );
        if ($id>0)
            $Fields['fo.ID'] = $id;
        
        switch ($sort) {
            case 'spooler':
                $Sort = array('fs.SPOOLER_ID','fo.PATH');
                break;
            case 'chain':
                $Sort = array('fo.PATH','fs.SPOOLER_ID');
                break;
            default:
                $Sort = array('fo.NEXT_START_TIME desc','fo.START_TIME desc','fs.SPOOLER_ID','fo.PATH');
                break;
        }
        
        $qry = $sql->Select(array( 'fs.NAME as SPOOLER_ID','fo.PATH','fo.ID','fo.NAME','fo.START_TIME','fo.NEXT_START_TIME','fo.TITLE','fo.STATE','fo.STATE_TEXT','fo.SUSPENDED','fo.IN_PROCESS_SINCE','fo.TASK_ID','fo.HISTORY_ID','fo.SETBACK','fo.SETBACK_COUNT' ))
                .$sql->From(array('FOCUS_ORDERS fo'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fo.SPOOLER_ID','fs.ID'))
                .$sql->Where($Fields)
                .$sql->OrderBy($Sort);              

        $Orders = array();
        $res = $data->sql->query($qry);
        while ($line = $data->sql->get_next($res))
        {
            $id = $line['ID'];
            
            if ($line['SUSPENDED']==1) {
                $status = 'SUSPENDED';                
            }
            elseif ($line['SETBACK']!='') {
                $status = 'SETBACK';                
            }
            elseif ($line['START_TIME']!='') {
                $status = 'RUNNING';                
            }
            elseif (substr($line['NEXT_START_TIME'],0,4)=='2038') {
                $status = 'DONE';
            }
            elseif ($line['NEXT_START_TIME']!='') {
                $status = 'WAITING';                
            }
            else {
                $status = 'UNKNOWN!';
            }
            if (($only_warning) and (($status =='DONE') or ($status =='WAITING'))) continue;
            $Orders[$id] = $line;
            $Orders[$id]['STATUS'] = $status;
            $p = strpos($line['PATH'],',');
            $Orders[$id]['JOB_CHAIN'] = substr($line['PATH'],0,$p);
            $Orders[$id]['ORDER'] = substr($line['PATH'],$p+1); 
            if ($line['START_TIME']!='')
                $Orders[$id]['START_TIME'] = $date->Date2Local($line['START_TIME'],$line['SPOOLER_ID']);
            else 
                $Orders[$id]['START_TIME'] = '';
            if (($line['NEXT_START_TIME']=='') or ($status=='DONE')) {
                $Orders[$id]['NEXT_START_TIME'] = '';
            }
            else {
                $Orders[$id]['NEXT_START_TIME'] = $date->Date2Local($line['NEXT_START_TIME'],$line['SPOOLER_ID']);
            }
        }
        return $Orders;
   }

   public function Locks() {   
        $date = $this->date;        
        $sql = $this->sql;
        $db = $this->db;
        $data = $db->Connector('data');
        
        // Jobs
        $Fields = array( '{spooler}' => 's.NAME' );
        $qry = $sql->Select(array('s.NAME as SPOOLER','l.ID','l.NAME','l.PATH','l.MAX_NON_EXCLUSIVE','l.IS_FREE','l.STATE'))
               .$sql->From(array('FOCUS_LOCKS l'))
               .$sql->LeftJoin('FOCUS_SPOOLERS s',array('l.SPOOLER_ID','s.ID'))
               .$sql->Where($Fields)
               .$sql->OrderBy(array('s.NAME','l.PATH'));

        $res = $data->sql->query($qry);
        $Locks = array();
        while ($line = $data->sql->get_next($res))
        {         
            $id = $line['ID'];
            $Locks[$id]=$line;
            $Locks[$id]['FOLDER'] = dirname($line['PATH']);
        }
        return $Locks;
   }

}
