<?php
// src/Arii/MFTBundle/Controller/TransfersController.php

namespace Arii\MFTBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class TransfersController extends Controller
{
    protected  $ColorStatus = array (
            'success' => '#ccebc5',
            'error' => '#fbb4ae'
        );

    public function indexAction( $name = 'eric' )
    {
       return $this->render('AriiMFTBundle:Default:index.html.twig' , array('name' =>$name));
    }

    public function toolbarAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        
        return $this->render('AriiMFTBundle:Transfers:toolbar.xml.twig',array(), $response );
    }

    public function formAction()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        
        return $this->render('AriiMFTBundle:Transfers:form.xml.twig',array(), $response );
    }

    public function pieAction( $history_max=0, $only_warning=1 )
    {
        $request = Request::createFromGlobals();
        if ($request->get('history')>0) {
            $history_max = $request->get('history');
        }
        if ($request->get('only_warning')!='')
            $only_warning = $request->get('only_warning');

        $history = $this->container->get('arii_mft.history');
        $Transfers = $history->Transfers($history_max, $only_warning);
       
        $Status['success']=$Status['error']=$Status['running']=0;
        foreach ($Transfers as $k=>$Transfer) {
            $status = $Transfer['STATUS'];
            if (isset($Status[$status])) {
                $Status[$status]++;
            }
            else {
                $Status[$status]=0;
            }
        }
        
        $pie = '<data>';
        if ($only_warning==0)
            $pie .= '<item id="success"><STATUS>success</STATUS><JOBS>'.$Status['success'].'</JOBS><COLOR>#ccebc5</COLOR></item>';
        $pie .= '<item id="error"><STATUS>error</STATUS><JOBS>'.$Status['error'].'</JOBS><COLOR>#fbb4ae</COLOR></item>';
//        $pie .= '<item id="RUNNING"><STATUS>RUNNING</STATUS><JOBS>'.$Status['running'].'</JOBS><COLOR>#ffffcc</COLOR></item>';
  
        $pie .= '</data>';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $pie );
        return $response;
    }
    
    public function gridAction( $history_max=0, $only_warning=1 )
    {
        $request = Request::createFromGlobals();
        if ($request->get('history')>0) {
            $history_max = $request->get('history');
        }
        if ($request->get('only_warning')!='')
            $only_warning = $request->get('only_warning');

        $history = $this->container->get('arii_mft.history');
        $Transfers = $history->Transfers($history_max, $only_warning);
        
        $grid = '<?xml version="1.0" encoding="UTF-8"?>';
        $grid .= "<rows>\n";
        $grid .= '<head>
            <afterInit>
                <call command="clearAll"/>
            </afterInit>
        </head>';
        foreach ($Transfers as $k=>$Transfer) {
            $status = $Transfer['STATUS'];
            if ($only_warning and ($status == 'success')) continue;
            $grid .= '<row id="'.$k.'" style="background-color: '.$this->ColorStatus[$status].'">';
            $grid .= "<cell>".$Transfer['MANDATOR']."</cell>";
            $grid .= "<cell>".$Transfer['SOURCE_HOST']."</cell>";
            $grid .= "<cell>".$Transfer['SOURCE_DIR']."</cell>";
            $grid .= "<cell>".$Transfer['SOURCE_FILENAME']."</cell>";
            $grid .= "<cell>".$Transfer['TARGET_HOST']."</cell>";
            $grid .= "<cell>".$Transfer['TARGET_DIR']."</cell>";
            $grid .= "<cell>".$Transfer['TARGET_FILENAME']."</cell>";
            $grid .= "<cell>".$Transfer['OPERATION']."</cell>";
            $grid .= "<cell>".$Transfer['STATUS']."</cell>";
            $grid .= "<cell>".$Transfer['TRANSFER_TIMESTAMP']."</cell>";
            $grid .= "<cell>".$Transfer['PROTOCOL']."</cell>";
            $grid .= "<cell>".$Transfer['PORT']."</cell>";
            $grid .= "</row>";
        }
        $grid.='</rows>';
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent( $grid );
        return $response;
    }
    
}
