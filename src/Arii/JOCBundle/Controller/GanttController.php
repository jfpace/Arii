<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
class GanttController extends Controller
{
    protected $Map = array();
    protected $Tasks = array();
    protected $Links = array();
    protected $FoldersId = array();
    protected $FId = 0;
    
    public function indexAction()
    {
        return $this->render('AriiJOCBundle:Orders:gantt.html.twig');
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        return $this->render('AriiJOCBundle:Orders:gantt.xml.twig',array(), $response );
    }

    public function ordersAction() {
        
        $sql = $this->container->get('arii_core.sql');
        // on part de la base de données du scheduler distant,
        // elle est en temps réel
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $gantt = $dhtmlx->Connector('data');
        
        $Fields = array( '{start_time}' => 'soh.START_TIME',
                        'soh.END_TIME' => '(null)' );
        $qry = $sql->Select(array('soh.HISTORY_ID','soh.SPOOLER_ID','soh.JOB_CHAIN','soh.ORDER_ID','soh.SPOOLER_ID','soh.TITLE','soh.STATE_TEXT','soh.STATE as ORDER_STATE','soh.START_TIME as ORDER_START',
                        'sosh.TASK_ID','sosh.STEP','sosh.STATE','sosh.START_TIME','sosh.END_TIME','sosh.ERROR','sosh.ERROR_TEXT',
                        'sh.JOB_NAME','sh.START_TIME as JOB_START','sh.START_TIME as JOB_END','sh.PID','sh.EXIT_CODE' ))
                .$sql->From(array('SCHEDULER_ORDER_HISTORY soh'))
                .$sql->LeftJoin('SCHEDULER_ORDER_STEP_HISTORY sosh',array('soh.HISTORY_ID','sosh.HISTORY_ID'))
                .$sql->LeftJoin('SCHEDULER_HISTORY sh',array('sosh.TASK_ID','sh.ID'))
                .$sql->Where($Fields)
                .$sql->OrderBy(array('soh.SPOOLER_ID','soh.JOB_CHAIN','sosh.START_TIME desc'));

        $res = $gantt->sql->query( $qry );
        // on considère les ordres et non pas les job_chains
        $DoneSpooler = $DoneOrder = $DoneChain = array();
        $Step = array(); // on traite les steps a part pour les ordonner à la fin
        $ID = array(); // Retourver un id par sa localisation
        $id_folder =0;
        while ($line = $gantt->sql->get_next($res)) {
            $spooler = $line['SPOOLER_ID'];
            
            $here = $spooler;
            // On cree le spooler si ce n'est pas déjà fait
            if (!isset($DoneSpooler[$spooler])) {
                $t['text'] = $spooler;
                $t['description'] = $spooler;
                $t['sortorder'] = '0'; 
                $t['parent'] = '0';
                $t['orders'] = 0;
                $t['errors'] = 0;                
                $t['status'] = "";      
                $id_spooler = 'S:'.$spooler;
                $this->Tasks[$id_spooler] = $t;
                $DoneSpooler[$spooler]=1;
                
                $ID[$here] = $id_spooler;
            }
            
            // On cree les folders
            $PATH = explode('/',$line['JOB_CHAIN']);
            $chain = array_pop($PATH);
            
            $id = $spooler;
            $id_parent = $id_spooler;
            foreach ($PATH as $p) {
                $id .= '/'.$p;
                if (isset($this->FoldersId[$id])) {
                    $id_folder = $this->FoldersId[$id];
                }
                else {
                    $this->FoldersId[$id] = $this->FId;
                    // On cree la tache du folder 
                    $t['text'] = $p;
                    $t['description'] = str_replace('\\','/',$id);
                    $t['errors'] = 0;                                
                    $t['sortorder'] = '0'; 
                    $t['parent'] = $id_parent;
                    $t['status'] = "";
                    $t['progress'] = 0;   
                    $id_folder = 'F:'.$this->FId;
                    $this->Tasks[$id_folder] = $t; 
                    $id_parent = $id_folder;
                    $this->FId++;
                }
            }
            
            // On cree la chaine
            $job_chain = $spooler.'/'.$line['JOB_CHAIN'];
            $here = $job_chain;
            if (!isset($DoneChain[$job_chain])) {
                $t['text'] = $chain;
                $t['description'] = '';                
                $t['sortorder'] = '0'; 
                $t['parent'] = $id_folder;
                $t['progress'] = 0;   
                $t['orders'] = 0;
                $t['errors'] = 0;
                $t['status'] = "";                
                $id_chain = 'C:'.$this->FId;
                $ID[$here] = $id_chain;
                $this->Tasks[$id_chain] = $t;
                $this->FId++;
                // nouvelle tache
                $DoneChain[$job_chain] = 1;           
            }
            
            // On cree l'ordre, sauf si deja créé  
            $id_order = $line['HISTORY_ID'];
            $here = $job_chain.','.$line['ORDER_ID'];
            if (!isset($DoneOrder[$id_order])) {
                $t['text'] = str_replace('\\','/',$line['ORDER_ID']);
                $t['description'] = '';                   
                $t['start_date'] = $line['ORDER_START'];
                $t['progress'] = 0;   
                $t['errors'] = 0;                 
                $t['sortorder'] = '0'; 
                $t['parent'] = $id_chain;
                $t['status'] = "running";
                $this->Tasks[$id_chain]['orders']++;
                $this->Tasks[$id_spooler]['orders']++;
                $this->Tasks['O:'.$id_order] = $t;
                $ID[$here] = 'O:'.$id_order;
                // nouvelle tache
                $DoneOrder[$id_order]=1;
            }

            // Ajout du step            
            $step_id = $line['TASK_ID'];
            $here .= '/'.$line['STATE'];
            $t['text'] = $line['STATE'];
            $t['description'] = $line['JOB_NAME'];              
            $t['start_date'] = $line['START_TIME'];
            $t['end_date'] = $line['END_TIME'];
            $t['sortorder'] = '0';
            $t['parent'] = 'O:'.$id_order;
            if ($t['end_date']=='') {
                $t['status'] = 'running';
                $t['end_date'] = gmdate("Y-m-d H:i:s");
            }
            elseif ($line['ERROR']>0) { 
                $t['errors'] = 1;
                $this->Tasks['O:'.$id_order]['errors']++; 
                $this->Tasks[$id_chain]['errors']++;
                $this->Tasks[$id_spooler]['errors']++;
                if (substr($line['STATE'],0,1)=='!')
                    $t['status'] = 'fatal';
                else 
                    $t['status'] = 'failure';
            }
            else {
                $t['status'] = 'success';
            }
            
            // duree
            $t['runtime'] = strtotime($t['end_date'])-strtotime($t['start_date']);
            $t['duration'] = $t['runtime']/86400;
            $t['progress'] = 1; // par defaut, c'est fini             

            if ($line['JOB_NAME']!='')
                $id = 'T:'.$step_id;
            $t['id'] = $id;
            $Step[$here] = $t;
            $ID[$here] = $id;
            
        }

        //////////////////////////////////////////////////////////
        // Calcul des temps d'execution
        // Cette requette peut être mise en cache !!
        // On complete avec les steps des ordres
        $db = $this->container->get('arii_core.db');
        $gantt = $db->Connector('data');

        $Fields = array( '{start_time}' => 'o.START_TIME',
                        'jc.STATE' => 'running' );
        $qry = $sql->Select(array('o.ID as ID_ORDER','o.PATH as ORDER_PATH','o.NAME as ORDER_ID','o.START_TIME as ORDER_START','o.SUSPENDED',
                            's.NAME as SPOOLER',
                            'runs.RUN_TIME',
                            'sruns.TASK_ID','sruns.JOB_CHAIN_NODE_ID','sruns.RUN_TIME as STEP_TIME'))
                .$sql->From(array('FOCUS_ORDERS o'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS jc',array('o.JOB_CHAIN_ID','jc.ID'))
                .$sql->LeftJoin('FOCUS_SPOOLERS s',array('o.SPOOLER_ID','s.ID'))
                .$sql->LeftJoin('FOCUS_ORDER_RUNTIMES runs',array('o.ID','runs.ORDER_ID'))
                .$sql->LeftJoin('FOCUS_ORDER_STEP_RUNTIMES sruns',array('o.ID','sruns.ORDER_ID'))
                .$sql->Where($Fields);

        $res = $gantt->sql->query( $qry );
        $nb=0;
        $Calc = $CalcT = array(); // pour eviter de calculer 2 fois
        while ($line = $gantt->sql->get_next($res)) {
            $spooler = $line['SPOOLER'];        
            // on cree le temps d'execution de l'ordre
            $id_order = 'O:'.$line['ID_ORDER'];
            
            if ((!isset($Calc[$id_order])) and ($line['RUN_TIME']>0)) {
                $Calc[$id_order] = $line['RUN_TIME'];
                // on peut utiliser maintenant
                // On recupere le bon id
                $order = $line['SPOOLER'].$line['ORDER_PATH'];
                if (isset($ID[$order])) {
                    $ido = $ID[$order];
                    $this->Tasks[$ido]['runtime'] = $line['RUN_TIME'];
                    $this->Tasks[$ido]['duration'] = $line['RUN_TIME']/86400;  
                    // Attention a l'ordre suspendu
                    $s = strtotime($this->Tasks[$ido]['start_date']);
                    if ($line['SUSPENDED']>0) {
                        $this->Tasks[$ido]['end_date'] = gmdate("Y-m-d H:i:s"); 
                        $this->Tasks[$ido]['status']='suspended';
                    }
                    else {
                        $this->Tasks[$ido]['end_date'] = date("Y-m-d H:i:s", $s+$line['RUN_TIME']); 
                    }
                    $this->Tasks[$ido]['progress'] = (strtotime(gmdate("Y-m-d H:i:s"))-$s)/$line['RUN_TIME'];
                }
            }
            // on cree le temps d'execution de la tache
            if ($line['STEP_TIME']==0) continue;

            $id_task = 'T:'.$line['TASK_ID'];
            if (!isset($CalcT[$id_task]))
                $CalcT[$id_task] = $line['STEP_TIME'];
            $id_node = 'H:'.$line['JOB_CHAIN_NODE_ID']; 
            if (!isset($CalcT[$id_node]))
                $CalcT[$id_node] = $line['STEP_TIME'];
            
            // On peut calculer la progression
            
        }

        // Les liens
        $Next = $Error = array();
        // on commence par tracer les ordres
        $Fields = array( '{start_time}' => 'o.START_TIME',
                        'jc.STATE' => 'running' );
        $qry = $sql->Select(array('distinct o.ID as ID_ORDER','o.PATH as ORDER_PATH','o.NAME as ORDER_ID','o.TITLE','o.START_TIME as ORDER_START',
                        's.NAME as SPOOLER',
                        'jc.ID as ID_CHAIN','jc.PATH as CHAIN_PATH','jc.NAME as JOB_CHAIN','jc.TITLE as CHAIN_TITLE',
                        'jcn.ID as ID_STEP','jcn.STATE as STEP','jcn.NEXT_STATE','jcn.ERROR_STATE','jcn.ORDERING','jcn.ACTION',
                        'j.PATH as JOB_NAME',
                        's.NAME as SPOOLER' ))
                .$sql->From(array('FOCUS_ORDERS o'))
                .$sql->LeftJoin('FOCUS_JOB_CHAINS jc',array('o.JOB_CHAIN_ID','jc.id'))
                .$sql->LeftJoin('FOCUS_JOB_CHAIN_NODES jcn',array('jc.ID','jcn.JOB_CHAIN_ID'))
                .$sql->LeftJoin('FOCUS_JOBS j',array('jcn.JOB_ID','j.ID'))
                .$sql->LeftJoin('FOCUS_SPOOLERS s',array('o.SPOOLER_ID','s.ID'))
                .$sql->Where($Fields)
                .$sql->OrderBy(array('s.NAME','o.PATH','jcn.ORDERING'));

        $res = $gantt->sql->query( $qry );
        $nb=0;
        $Sort = $Next = $Error = array();
        while ($line = $gantt->sql->get_next($res)) {
            $spooler = $line['SPOOLER'];

            // $order_name = substr($line['ORDER_ID'],strpos($line['ORDER_ID'],',')+1);
            $order = $spooler.$line['ORDER_PATH'];
            // si l'ordre n'existe pas, la synchro n'est pas faite

            if (!isset($ID[$order])) continue;
            $id_order = $ID[$order];          
            $state = $order.'/'.$line['STEP'];

            // on ne prend pas les noeuds de fin
            if ($line['JOB_NAME']=='') continue;

            // On conserve l'ordre initial
            $o = $order.'/'.$line['ORDERING'];
            $Sort[$o] = $state;
            
            // On cree les liens
            $next_state = $order.'/'.$line['NEXT_STATE'];
            $Next[$state] = $next_state;
            $error_state = $order.'/'.$line['ERROR_STATE'];
            $Error[$state] = $error_state;

            // Precedents 
            $From[$error_state] = $line['STEP'];
            $From[$next_state] = $line['STEP'];            
            $IDFrom[$error_state] = $state;
            $IDFrom[$next_state] = $state;            
                    
            // le step est connu ?
            $id_step = 'H:'.$line['ID_STEP'];
            if (!isset($ID[$state])) {
                // le step est inconnu, on l'ajoute
                $t['text'] = $line['STEP'];
                $t['description'] = substr($line['JOB_NAME'],1);  
                $t['id'] = $id_step;
                
                // Depart de l'étape:
                // Est ce qu'on a le precedent ?
                $ORDER = $this->Tasks[$id_order];
                // la date de depart est la fin du precedent
                if (isset($IDFrom[$state])) {
                    $precedent = $IDFrom[$state];
                    $id_precedent = $ID[$precedent];
                    // le precedent est realise ou non ? T ou H
                    // on retrouve le end_date du precedent
                    if (isset($Step[$precedent]['end_date']))
                        $t['start_date'] = $Step[$precedent]['end_date'];                    
                    // sinon on se cale au moins sur l'ordre
                    else
                        $t['start_date'] = $ORDER['start_date'];       
                }
                
                // la date de fin est la date de depart + duree connue
                if (isset($CalcT[$id_step])) {
                    $t['runtime'] = $CalcT[$id_step];
                    $t['duration'] = $CalcT[$id_step]/86400;                    
                }
                else { // duree nulle
                    $t['runtime'] = "";
                    $t['duration'] = 0;
                }
                
                // on calcule la date de fin pour le prochain
                $start_date = strtotime($t['start_date']);
                $tm = localtime($start_date+$t['runtime'],true);
                $t['end_date'] = sprintf("%04d-%02d-%02d %02d:%02d:%02d",
                        $tm['tm_year']+1900,$tm['tm_mon']+1,$tm['tm_mday'],$tm['tm_hour'],$tm['tm_min'],$tm['tm_sec']);
                
                $t['progress'] = 0; // aucune progression
                $t['status'] = 'todo';
                $t['sortorder'] = '0';
                $t['parent'] = $id_order;  
                
                // $this->Tasks[$step_id] = $t;
                $Step[$state] = $t;
                $ID[$state] = $t['id'];              
            }
            elseif ($Step[$state]['status']=='running' ) { // si c'est en running, on calcule l'execution
                // la fin est la fin theorique
                $Step[$state]['progress'] = 0.5;
                $e = strtotime($Step[$state]['end_date']); // on l'a calculé avant
                $s = strtotime($Step[$state]['start_date']); 
                $duree = $e-$s;
                $Step[$state]['runtime'] = $duree; 
                if (isset($CalcT[$id_step])) {
                    $Step[$state]['duration'] = $CalcT[$id_step]/86400; 
                    $Step[$state]['progress'] = $duree/$CalcT[$id_step];
                    $Step[$state]['end_date'] = date("Y-m-d H:i:s", $s+$CalcT[$id_step]);
                }
                else {
                    // rien ?!
                }
            }
            
            // On conserve la source
            if (isset($From[$state]))
                $Step[$state]['from'] = $From[$state]; 
            
            // On verifie le status
            if ($line['ACTION']=='next_state') {
                $Step[$state]['status']='skipped';
            }
        }

        // On ajoute les Step triés aux taches
        foreach ($Sort as $k=>$v) {
            $Info = $Step[$v];
            $id = $Info['id'];
            $this->Tasks[$id] = $Info;
        }
                
        // on refait les liens
        foreach ($Next as $s => $t ) {
            $source = $ID[$s];
            if (isset($ID[$t])) {
                $target = $ID[$t];
                $id = $source.$target;
                array_push($this->Links, '{ "id": "'.$id.'", "source": "'.$source.'", "target": "'.$target.'", "type": "0", "state": "next" }' ); 
            }
        }
        foreach ($Error as $s => $t ) {
            $source = $ID[$s];
            if (isset($ID[$t])) {
                 $target = $ID[$t];
                 $id = $source.$target;
                array_push($this->Links, '{ "id": "'.$id.'", "source": "'.$source.'", "target": "'.$target.'", "type": "0", "state": "error" }' ); 
            }
        }

        $gantt->mix("open", 1);
        $session = $this->container->get('arii_core.session');
        $States = $session->get('state');
        if (isset($States['gantt_orders'])) $State = $States['gantt_orders'];
        
        $gantt->enable_order("sortorder");
        
        $json = '{ "data": [ ';
        $Res = array();
        $now = gmdate("Y-m-d H:i:s");
        $date = $this->container->get('arii_core.date');
        foreach ($this->Tasks as $id=>$t) {
            $Line = array();
            array_push($Line,'{ "id": "'.$id.'"');
            foreach (array('text','start_date','end_date','duration','runtime','progress','sortorder','parent','open','description','errors','status','from') as $k ) {
                if (isset($t[$k])) {
                     if ($k == "runtime") {
                        array_push($Line,'"runtime": "'.$date->FormatTime($t[$k]).'"');
                     }
                     else {
                        array_push($Line,'"'.$k.'": "'.$t[$k].'"');
                     }
                }
            }
            $type = substr($id,0,1);
            array_push($Line,'"objtype": "'.$type.'" ');
            if (isset($State[$id])) {
                array_push($Line,'"open": "'.$State[$id].'"');
            } 
            else {
                array_push($Line,'"open": "1"');
            }
            array_push($Res, implode(',',$Line)."}\n");
        }
        $json .= implode(',',$Res);
        $json .= ']';
        $json .= ', "collections": { "links": [ ';
        $json .= implode(',', $this->Links);
        $json .= '] }';
        $json .= ' }';
        print $json;
exit();
    }

    public function FolderId($path,$start,$spooler='scheduler') {
        if (isset($this->FoldersId[$path])) {
            return $this->FoldersId[$path];
        }
        // on reverifie l'existences parents et on leur affecte les ids
        $root = '';
        $id = 'F0';
        foreach (explode('/',$path) as $p) {
            $root .= "$p/";
            if (!isset($this->FoldersId[$root])) {
                $this->FId++;
                // on cree une nouvelle tache
                $id = 'F'.$this->FId;
                $this->FoldersId[$root] = $id;  
                if (isset($father)) {
                    $t['parent'] = $this->FoldersId[$father];
                    $t['text'] = $p;
                }
                else {
                    $t['text'] = $spooler;                    
                }
                $t['start_date'] = $start;
                $t['duration'] = 0;
             //   $t['progress'] = '0.0'; 
                $this->Tasks[$id] = $t;
            }
            // on garde l'id qui est le pere du prochain
            $father = $root;
        }
        return $id;
    }
    
    private function runtime() {
                $start_date = strtotime($this->Tasks[$id_order]['start_date']);
                $t = localtime($start_date+$line['RUN_TIME'],true);
                $this->Tasks[$id_order]['progress'] = (time()-$start_date)/$line['RUN_TIME'];
                $this->Tasks[$id_order]['end_date'] = sprintf("%04d-%02d-%02d %02d:%02d:%02d",
                        $t['tm_year']+1900,$t['tm_mon']+1,$t['tm_mday'],$t['tm_hour'],$t['tm_min'],$t['tm_sec']);
    }

}
