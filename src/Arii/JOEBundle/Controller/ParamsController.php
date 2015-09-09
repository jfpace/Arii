<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Form\JobType;
use Symfony\Component\HttpFoundation\Request;

class ParamsController extends Controller
{

     public function gridAction()
    {
        $dhtmlx = $this->container->get('arii_core.db');
        $data = $dhtmlx->Connector('grid');

        $request = Request::createFromGlobals();
        $id = -1;
        if ($request->get('id')) {
            $id = $request->get('id' );
        }
        
        $data->render_sql('select name,value from joe_param_job where job_id='.$id,'','name,value');
    }

}
