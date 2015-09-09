<?php
// src/Arii/MFTBundle/Controller/TransfersController.php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class TransferController extends Controller
{
    protected  $ColorStatus = array (
            'success' => '#ccebc5',
            'error' => '#fbb4ae'
        );

    public function formAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');    
        
        $qry = $sql->Select(array('ID','MANDATOR','SOURCE_HOST','SOURCE_HOST_IP','SOURCE_DIR','SOURCE_FILENAME','SOURCE_USER','MD5','FILE_SIZE'))
                .$sql->From(array('SOSFTP_FILES'))
                .$sql->Where(array('ID' => $id));
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('form');
        $data->render_sql($qry,'ID','ID,MANDATOR,SOURCE_HOST,SOURCE_HOST_IP,SOURCE_DIR,SOURCE_FILENAME,SOURCE_USER,MD5,FILE_SIZE');
    }
 
    public function historyAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $sql = $this->container->get('arii_core.sql');    
	        
        $qry = $sql->Select(array('GUID','TRANSFER_TIMESTAMP','PID','PPID','TARGET_HOST','TARGET_HOST_IP','OPERATION','TARGET_USER','TARGET_DIR','TARGET_FILENAME','PROTOCOL','PORT','STATUS','LAST_ERROR_MESSAGE'))
                .$sql->From(array('SOSFTP_FILES_HISTORY'))
                .$sql->Where(array('SOSFTP_ID' => $id));
        
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data = $dhtmlx->Connector('grid');
        $data->event->attach("beforeRender",array($this,"grid_render"));
        $data->render_sql($qry,'GUID','TRANSFER_TIMESTAMP,TARGET_HOST,TARGET_DIR,TARGET_FILENAME,STATUS,LAST_ERROR_MESSAGE');
    }

   function grid_render ($data){
        if ($data->get_value('STATUS')=='success') {
            $data->set_row_color($this->ColorStatus['success']);
        }
        else {
            $data->set_row_color($this->ColorStatus['error']);
        }
    }


}
