<?php
// src/Arii/JOCBundle/Service/AriiSOS.php
 
namespace Arii\JOCBundle\Service;

class AriiSOS
{
    protected $db;
    protected $sql;
    
    public function __construct(  
            \Arii\CoreBundle\Service\AriiDB $db, 
            \Arii\CoreBundle\Service\AriiSQL $sql ) {
        $this->db = $db;
        $this->sql = $sql;
    }

/*********************************************************************
 * Informations de connexions
 *********************************************************************/

   public function getOrderInfos($id) {   
        $data = $this->db->Connector('data');
        $sql = $this->sql;
        
        $Fields = array (
            '{spooler}'    => 'NAME',
            '{job_chain}'   => 'fjc.PATH',
            'fo.id'=> $id );
        
        $qry = $sql->Select(array('fo.SPOOLER_ID','fo.PATH','fo.NAME as ORDER_ID','fo.STATE','fo.INITIAL_STATE','fo.END_STATE'))
                .$sql->From(array('FOCUS_ORDERS fo'))
                .$sql->Where($Fields);
        
        $res = $data->sql->query( $qry );
        while ($line = $data->sql->get_next($res)) {
            $p = strpos($line['PATH'],',');
            $job_chain = substr($line['PATH'],0,$p);
            $order  = $line['ORDER_ID'];
            $spooler_id  = $line['SPOOLER_ID'];
            if (($p = strpos($order,','))>0) 
                    $order = substr($order,$p+1);
            $state = $line['STATE'];
            $initial = $line['INITIAL_STATE'];
            $end = $line['END_STATE'];
            return array($spooler_id,$order,$job_chain,$state,$initial,$end);  
        }
        print "Order ID $id ?!";
        exit();
   }

   public function getConnectInfos($spooler_id) {
        $data = $this->db->Connector('data');
        $sql = $this->sql;
           
           // on cherche le scheduler dans la base de données
            $Fields = array (
                '{spooler}'    => 'NAME',
                'ID'=>$spooler_id );
            $qry = $sql->Select(array('NAME','IP_ADDRESS','TCP_PORT'))
                    .$sql->From(array('FOCUS_SPOOLERS'))
                    .$sql->Where($Fields);

           $res = $data->sql->query( $qry );
           // pourrais etre en parametre si besoin
           $protocol = "http"; $path = "";
           while ($line = $data->sql->get_next($res)) {
               $scheduler = $line['NAME'];
               $hostname = $line['IP_ADDRESS'];
               $port = $line['TCP_PORT'];
               return array($protocol,$scheduler,$hostname,$port,$path);  
           }
           print "Spooler ID $spooler_id ?!";
           exit();
   }

   public function getJobChainInfos($job_chain_id) {   
        $data = $this->db->Connector('data');
        $sql = $this->sql;
        $Fields = array (
            '{spooler}'    => 'NAME',
            '{job_chain}'   => 'fjc.PATH',
            'fjc.id'=> $job_chain_id );
        
        $qry = $sql->Select(array('fjc.PATH','fs.NAME','fs.IP_ADDRESS','fs.TCP_PORT'))
                .$sql->From(array('FOCUS_JOB_CHAINS fjc'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fjc.spooler_id','fs.id'))
                .$sql->Where($Fields);
        
        $res = $data->sql->query( $qry );
        $protocol = "http"; $path = "";
        while ($line = $data->sql->get_next($res)) {
            $job_chain = $line['PATH'];
            $scheduler = $line['NAME'];
            $hostname = $line['IP_ADDRESS'];
            $port = $line['TCP_PORT'];
            return array($scheduler,$protocol,$hostname,$port,$path,$job_chain);  
        }
           print "Job chain ID $job_chain_id ?!";
           exit();
   }

   public function getJobInfos($job_id,$status="UNKNOWN") {   
        $data = $this->db->Connector('data');
        $sql = $this->sql;
        $Fields = array (
            '{spooler}'    => 'NAME',
            '{job_name}'   => 'fj.PATH',
            'fj.id'=> $job_id );
        
        $qry = $sql->Select(array('fj.PATH','fs.NAME','fs.IP_ADDRESS','fs.TCP_PORT'))
                .$sql->From(array('FOCUS_JOBS fj'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fj.spooler_id','fs.id'))
                .$sql->Where($Fields);
        
        $res = $data->sql->query( $qry );
        $protocol = "http"; $path = "";
        if ($line = $data->sql->get_next($res)) {
            $job = $line['PATH'];
            $scheduler = $line['NAME'];
            $hostname = $line['IP_ADDRESS'];
            $port = $line['TCP_PORT'];
            $res = $data->sql->query( 'update FOCUS_JOBS set STATE="'.$status.'",CRC="" where id='.$job_id);
            return array($scheduler,$protocol,$hostname,$port,$path,$job);  
        }
        print "Job ID $job_id ?!";
        exit();
   }

   public function getJobChainNodeInfos($job_chain_node_id) {   
        $data = $this->db->Connector('data');
        $sql = $this->sql;
        $Fields = array (
            '{spooler}'    => 'NAME',
            '{job_chain}'   => 'fjc.PATH',
            'fjcn.id'=> $job_chain_node_id );
        
        $qry = $sql->Select(array('fjc.PATH','fjcn.STATE','fs.NAME','fs.IP_ADDRESS','fs.TCP_PORT'))
                .$sql->From(array('FOCUS_JOB_CHAIN_NODES fjcn'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS fjc',array('fjcn.job_chain_id','fjc.id'))
                .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('fjcn.spooler_id','fs.id'))
                .$sql->Where($Fields);
          
        
        $res = $data->sql->query( $qry );
        $protocol = "http"; $path = "";
        while ($line = $data->sql->get_next($res)) {
            $job_chain = $line['PATH'];
            $state = $line['STATE'];
            $scheduler = $line['NAME'];
            $hostname = $line['IP_ADDRESS'];
            $port = $line['TCP_PORT'];
            return array($scheduler,$protocol,$hostname,$port,$path,$job_chain,$state);  
        }
           print "Job chain ID $job_chain_node_id ?!";
           exit();
   }
   
}
