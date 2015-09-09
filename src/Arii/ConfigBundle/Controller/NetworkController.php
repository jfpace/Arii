<?php

namespace Arii\ConfigBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NetworkController extends Controller
{
     public function networkAction()
    {
        $session = $this->container->get('arii_core.session');
        $enterprise = $session->getEnterprise();
        $enterprise_id  = $session->getEnterpriseId();
/*
		curl_setopt_array($ch = curl_init(), array(
  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
  CURLOPT_POSTFIELDS => array(
    "token" => "abeYUMe7PNpKQAapSMd8PQHajxX2wp",
    "user" => "ud15DYqmrkVw2CY8pU7Hw1ZkfydD4n",
    "message" => "hello world"
  )));
print curl_exec($ch);
curl_close($ch);
*/
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $qry = "SELECT 
                sp.id as spooler_id, sp.scheduler as spooler,
                sp.supervisor_id as supervisor,	
                s.name as site,
                sp.db_id, c1.title as db
                FROM ARII_SPOOLER sp
                LEFT JOIN ARII_SITE s
                ON sp.site_id=s.id
                LEFT JOIN ARII_CONNECTION c
                ON sp.connection_id=c.id        
                LEFT JOIN ARII_CONNECTION c1
                ON sp.db_id=c1.id                 
                ORDER by s.name,sp.id ";
        
        $res = $data->sql->query($qry);
        $Spooler_info = array();
        $Nodes = array();
        $Links = array();
        $Done = array();
        while ($line = $data->sql->get_next($res))
        {
            $spooler = $line['spooler'];
            $spooler_id = $line['spooler_id'];
            array_push($Nodes, array( 'id'=>'spooler_'.$spooler_id, 'name' => $spooler, 'image' => 'spooler' ));

            $supervisor = $line['supervisor'];
            if ($supervisor != '') {
                    array_push($Links, array( 'from'=>'spooler_'.$supervisor, 'to' => 'spooler_'.$spooler_id, 'color'=>'lightblue', 'style'=> 'moving-dot', 'length'=>50, 'width'=>0.5 ));
            }
            
            $db = $line['db'];
            if ($db != '') {
                $db_id = $line['db_id'];
                if (!isset($Done["db_$db_id"])) {
                    array_push($Nodes, array( 'id'=>'db_'.$db_id, 'name' => $db, 'image' => 'database' ));
                    $Done["db_$db_id"] = 1;
                }
                array_push($Links, array( 'from'=>'spooler_'.$spooler_id, 'to' => 'db_'.$db_id, 'color'=>'lightgray', 'style'=> 'moving-arrows', 'length'=>100, 'width'=>1 ));
            }
/*            
            $site = $line['site'];
            if ($site != '') {
                if (!isset($Done[$site])) {
                    array_push($Nodes, array( 'id'=>'site_'.$site, 'name' => $site, 'image' => 'site' ));
                    $Done[$site] = 1;
                }
                array_push($Links, array( 'from'=>'spooler_'.$spooler_id, 'to' => 'site_'.$site, 'color'=>'grey', 'style'=> 'dotted', 'length'=>100, 'width'=>1 ));                
            }
*/
           }
		
/*		
        $network = "nodesTable.addRow([1, 'Wireless', DIR + 'Network-Pipe-icon.png', 'image']);";
        $network .= "nodesTable.addRow([2, 'Wireless', DIR + 'Network-Pipe-icon.png', 'image']);";
        $network .= "linksTable.addRow([1, 2, lengthMain]);";
*/		
        return $this->render('AriiConfigBundle:Network:network.html.twig', array('Nodes' => $Nodes, 'Links' => $Links) );
    }
}
