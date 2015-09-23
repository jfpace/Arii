<?php
/*
 * Recupere les données et fournit un tableau pour les composants DHTMLx
 */ 
namespace Arii\DSBundle\Service;

class AriiDailySchedule
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
   public function DailySchedule( $cyclic = 0, $only_warning=1, $standalone=1) {   
        $data = $this->db->Connector('data');
        $sql = $this->sql;
        $date = $this->date;
        
        $Fields = array (
        '{spooler}'    => 'SCHEDULER_ID',
        '{job_name}'   => 'JOB',
        '{start_time}' => 'SCHEDULE_PLANNED' );
        
        if (!$cyclic) {
            $Fields['IS_REPEAT'] = '(null)';
        }
        if ($standalone) {
            $Fields['JOB_CHAIN'] ='(null)';            
        }
        else {
            $Fields['JOB'] ='(null)';            
        } 
        $qry = $sql->Select(array('ID','SCHEDULER_ID','SCHEDULER_HISTORY_ID','SCHEDULER_ORDER_HISTORY_ID','JOB','JOB_CHAIN','ORDER_ID','SCHEDULE_PLANNED','SCHEDULE_EXECUTED','PERIOD_BEGIN','PERIOD_END','IS_REPEAT','START_START','STATUS','RESULT','CREATED','MODIFIED')) 
                .$sql->From(array('DAYS_SCHEDULE'))
                .$sql->Where($Fields)
                .$sql->OrderBy(array('SCHEDULE_PLANNED','SCHEDULER_ID','JOB'));
/*
print $qry;
exit();
 */
        $res = $data->sql->query( $qry );
        $nb=0;
        $H = array();
        $Jobs = array();
        $now = gmdate("Y-m-d H:i");
        while ($line = $data->sql->get_next($res)) {
            $nb++;
            $id = $line['ID'];
            $ok=0;
            $line['SCHEDULE_PLANNED'] = substr($line['SCHEDULE_PLANNED'],0,16);
            if ($line['SCHEDULE_EXECUTED']!='')
                $line['SCHEDULE_EXECUTED'] = substr($line['SCHEDULE_EXECUTED'],0,16);
            if ($line['SCHEDULE_EXECUTED'] == $line['SCHEDULE_PLANNED']) {
                $status='EXECUTED';
                $ok=1;
            }
            elseif ($line['SCHEDULE_PLANNED']>$now) {
                $status='WAITING';
                $ok=1;
            }
            elseif ($line['SCHEDULE_EXECUTED'] > $line['SCHEDULE_PLANNED']) {
                $status='DELAYED';
            }
            elseif ($line['SCHEDULE_PLANNED'] < $now) {
                $status='BLOCKED';
            }
            else {
                $status='UNKNOWN';            
            }
            
            if (($only_warning) and ($ok)) continue; 
            $Jobs[$id] = $line;
            $Jobs[$id]['STATUS'] = $status;
        }  
        return $Jobs;
   }

}
