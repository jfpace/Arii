<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DetailController extends Controller
{
    public function __construct() {
    }
    
    public function jobAction( )
    {
        $request = Request::createFromGlobals();     
        $id = $request->query->get( 'id' );   
//        $this->refresh($id); // pas de verif, on affiche le timestamp

        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
                
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('j.NAME as JOB_NAME','j.PATH','j.TITLE','j.STATE','j.STATE_TEXT','j.LAST_INFO','j.WAITING_FOR_PROCESS','j.UPDATED',
                                    'p.NAME as PROCESS_CLASS'))
               .$sql->From(array("FOCUS_JOBS j"))
               .$sql->LeftJoin("FOCUS_PROCESS_CLASSES p",array('j.process_class_id','p.id'))                
               .$sql->Where(array("j.ID"=>substr($id,2)));
        
        try {
            $res = $data->sql->query( $qry );
        } catch (Exception $exc) {
            exit();
        }
      
        $Infos = $data->sql->get_next($res);
        return $this->render('AriiJOCBundle:Job:detail.html.twig', $Infos);
    }
    
    private function refresh($id) {
        
        $sos = $this->container->get('arii_joc.sos');
        switch(substr($id,0,1)) {
            case 'J':
                list($spooler,$protocol,$hostname,$port,$path,$job) = $sos->getJobInfos(substr($id,2));        
                break;
            default:
                print substr($id,0,1)." ?";
                exit();
        }
        
        $focus = $this->container->get('arii_focus.focus');
        return $focus->get($hostname,$port);
    }
    
}

