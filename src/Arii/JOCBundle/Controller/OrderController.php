<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    protected $images;
    
    public function __construct( )
    {
          $request = Request::createFromGlobals();
          $this->images = $request->getUriForPath('/../arii/images/wa');
    }
    
    public function indexAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $state = $this->container->get('arii_joc.state');
        $Orders = $state->Orders(1,0,'last',$id);
        if (isset($Orders[$id])) {
            $Infos['id'] = $id;
            $Infos['spooler'] = $Orders[$id]['SPOOLER_ID'];
            $Infos['order'] = $Orders[$id]['ORDER'];
            $Infos['chain'] = $Orders[$id]['JOB_CHAIN'];
        }
        else {
            print "order $id ?";
        }
        return $this->render('AriiJOCBundle:Order:index.html.twig', $Infos);
    }

/*******************************************/
    public function detailAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array('fo.name as ORDER_ID','fo.TITLE','fo.STATE','fo.STATE_TEXT',
                    'fo.SUSPENDED','fo.START_TIME','fo.END_TIME',
                    'fjc.NAME as JOB_CHAIN'))
               .$sql->From(array('FOCUS_ORDERS fo'))
               .$sql->LeftJoin('FOCUS_JOB_CHAINS fjc',array('fo.job_chain_id','fjc.id'))
               .$sql->Where(array('fo.id'=>substr($id,2)));
        try {
            $res = $data->sql->query( $qry );
        } catch (Exception $exc) {
            exit();
        }
        $Infos = $data->sql->get_next($res);
        if ($Infos['SUSPENDED']>0) {
            $Infos['STATUS'] = 'SUSPENDED';
        }
        else {
            $Infos['STATUS'] = 'ACTIVE';
        }
        return $this->render('AriiJOCBundle:Order:detail.html.twig', $Infos);
    }
    
/*******************************************/
    public function paramsAction()
    {
        $request = Request::createFromGlobals();
        $id = substr($request->query->get( 'id' ),2);
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array( 'id','name','value'))
                .$sql->From(array('FOCUS_PAYLOADS'))
                .$sql->Where(array('order_id'=>$id))
                .$sql->OrderBy(array('name'));
        $db = $this->container->get('arii_core.db');
        $grid = $db->Connector('grid');
        $grid->render_sql($qry,'id','name,value');
    }

    public function formAction()
    {
        $request = Request::createFromGlobals();
        $id = $request->query->get( 'id' );
        $sql = $this->container->get('arii_core.sql');
        $qry = $sql->Select(array( 'SPOOLER_ID','ID','TITLE','PATH','NAME','STATE','INITIAL_STATE','END_STATE',
                'NEXT_START_TIME','PRIORITY','START_TIME','SUSPENDED','IN_PROCESS_SINCE','ON_BLACKLIST','HISTORY_ID','TASK_ID',
                'SETBACK','SETBACK_COUNT'))
                .$sql->From(array('FOCUS_ORDERS'))
                .$sql->Where(array('ID'=>$id));
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('form');
        $data->event->attach("beforeRender",array($this,"form_render"));
        $data->render_sql($qry,'ID','ID,SPOOLER_ID,FOLDER,TITLE,CHAIN,NAME,STATE,INITIAL_STATE,END_STATE,NEXT_START_TIME,PRIORITY,START_TIME,SUSPENDED,IN_PROCESS_SINCE,ON_BLACKLIST,SETBACK,SETBACK_COUNT,HISTORY_ID,TASK_ID');
    }

    function form_render ($data){
        $data->set_value('FOLDER',dirname($data->get_value('PATH'))); 
        $p = strpos($data->get_value('NAME'),',');
        $data->set_value('CHAIN', substr($data->get_value('NAME'),$p+1)); 
        $data->set_value('NAME',  substr($data->get_value('NAME'),0,$p)); 
    }
    
    public function graphvizAction()
    {
        $request = Request::createFromGlobals();
        $return = 0;
        
        $tmp = $this->container->getParameter('arii_tmp');
        $images = '/bundles/ariigraphviz/images/silk';
        $this->images = $this->get('kernel')->getRootDir().'/../web'.$images;
        $images_url = $this->container->get('templating.helper.assets')->getUrl($images);        

        $this->graphviz_dot = $this->container->getParameter('graphviz_dot');
        $session = $this->getRequest()->getSession();
        $id = $request->query->get( 'id' );

        $file = '.*';
        $rankdir = 'LR';
        $splines = 'polyline';
        $show_params = 'n';
        $show_events = 'n';

        if ($request->query->get( 'splines' ))
            $splines = $request->query->get( 'splines' );
        if ($request->query->get( 'show_params' ))
            $show_params = $request->query->get( 'show_params' );
        if ($show_params == 'true') {
            $show_params = 'y';
        }
        else {            
            $show_params = 'n';
        }
        if ($request->query->get( 'show_events' ))
            $show_events = $request->query->get( 'show_events' );
        if ($show_events == 'true') {
            $show_events = 'y';
        }
        else {            
            $show_events = 'n';
        }
        
        if ($request->query->get( 'output' ))
            $output = $request->query->get( 'output' );
        else {
            $output = "svg";        
        }
        
        // on commence par recuperer le statut de l'ordre
        $db = $this->container->get('arii_core.db');
        $data = $db->Connector('data');
        
        $SOS = $this->container->get('arii_core.sos');
        $sql = $this->container->get('arii_core.sql');

        $qry = $sql->Select(
                array(  'o.NAME as ORDER_ID','o.STATE as CURRENT_STATE','o.TITLE as ORDER_TITLE','o.HISTORY_ID',
                        'jcn.STATE','jcn.NEXT_STATE','jcn.ERROR_STATE',
                        'jc.NAME as JOB_CHAIN',
                        'fs.NAME as SPOOLER_ID'))
        .$sql->From(array('FOCUS_ORDERS o'))
        .$sql->LeftJoin('FOCUS_JOB_CHAINS jc',array('o.JOB_CHAIN_ID','jc.ID'))
        .$sql->LeftJoin('FOCUS_JOB_CHAIN_NODES jcn',array('jc.ID','jcn.JOB_CHAIN_ID'))
        .$sql->LeftJoin('FOCUS_SPOOLERS fs',array('o.SPOOLER_ID','fs.ID'))
        .$sql->Where(array('o.ID' => $id ));
        
        $F = array('STEP','START_TIME','END_TIME','ERROR','ERROR_TEXT');
        // Creation de la structure
        $res = $data->sql->query( $qry );   
        $h = 0;
        $State = array();
        while ($line = $data->sql->get_next($res)) {
            $h = $line['HISTORY_ID'];
            $s = $line['STATE'];
            $State[$s] = $line;
            
            // mise à zero pour la suite
            foreach ($F as $f) {
                $State[$s][$f] = '';
            }    
        }

        $qry = $sql->Select(array('soh.JOB_CHAIN','soh.ORDER_ID','soh.SPOOLER_ID','soh.TITLE as ORDER_TITLE','soh.STATE as CURRENT_STATE','soh.START_TIME as ORDER_START_TIME','soh.END_TIME as ORDER_END_TIME',
            'sosh.TASK_ID','sosh.STATE','sosh.STEP','sosh.START_TIME','sosh.END_TIME','sosh.ERROR','sosh.ERROR_TEXT'))
        .$sql->From(array('SCHEDULER_ORDER_HISTORY soh')) 
        .$sql->LeftJoin('SCHEDULER_ORDER_STEP_HISTORY sosh',array('soh.HISTORY_ID','sosh.HISTORY_ID'))
        .$sql->Where(array('soh.HISTORY_ID' => $h ))
        .$sql->OrderBy(array('sosh.STEP'));

        // Creation de la structure
        $dhtmlx = $this->container->get('arii_core.dhtmlx');
        $data2 = $dhtmlx->Connector('data');
        $res = $data2->sql->query( $qry );      
        $last = '';
        while ($line = $data2->sql->get_next($res)) {
            $s = $line['STATE'];
            // On complète
            foreach ($F as $f) {
                $State[$s][$f] = $line[$f];
            }
            // on conserve les chemins 
            $Step["$last#$s"] = $line['STEP'];
            
            // setback ?
            if ($last==$s) {
                if (isset($Count[$s]))
                    $Count[$s] .= ','.$line['STEP'];
                else 
                    $Count[$s] = $line['STEP'];
            }
            $last =$s;
        }
        
        $svg = "digraph arii {\n";
        $svg .= "randkir=$rankdir\n";
        $svg .= "fontname=arial\n";
        $svg .= "fontsize=12\n";
        $svg .= "splines=$splines\n";
        $svg .= "node [shape=plaintext,fontname=arial,fontsize=8]\n";
        $svg .= "edge [shape=plaintext,fontname=arial,fontsize=8]\n";
        $svg .= "bgcolor=transparent\n";
        $svg .= "subgraph \"clusterJOBCHAIN\" {\n";
        $svg .= "style=filled;\n";
        $svg .= "color=lightgrey;\n";
        $last = '';        

        $Done = array(); // Noeuds traités

        $svg .= 'label="'.$State[$s]['JOB_CHAIN']."\"\n";
        // current states ?
        // est ce que le noeud de l'ordre est un noeud ?
        // si non, end node
        foreach ($State as $s => $info) {
            $svg .= $this->Node($info);            
            if ($info['NEXT_STATE']!='') {
                $next = $info['NEXT_STATE'];
                if (isset($Step["$s#$next"])) 
                    $svg .= '"'.$s.'" -> "'.$info['NEXT_STATE'].'" '."[label=\"".$Step["$s#$next"]."\",color=green]\n";
                else
                    $svg .= '"'.$s.'" -> "'.$info['NEXT_STATE'].'" '."[color=green,style=dashed]\n";
            }
            if ($info['ERROR_STATE']!='') {
                $next = $info['ERROR_STATE'];
                if (isset($Step["$s#$next"])) 
                    $svg .= '"'.$s.'" -> "'.$info['ERROR_STATE'].'" '."[label=\"".$Step["$s#$next"]."\",color=red]\n";
                else {
                    $svg .= '"'.$s.'" -> "'.$info['ERROR_STATE'].'" '."[color=red,style=dashed]\n";
                }
            }
            // splitter
            if (($p=strpos($s,':'))>0) {
                $prefix = substr($s,0,$p);
                $svg .= '"'.$prefix.'" -> "'.$s.'" '."[color=yellow,style=dashed]\n";
            }
            // setback ?
            if (isset($Count[$s])) {
                foreach(split(',',$Count[$s]) as $setback) {
                    $svg .= '"'.$s.'" -> "'.$s.'" '."[label=\"".$setback."\",color=red]\n";
                }
            }
        }
        
/*        $current = $OrderInfo['STATE'];
        if (!isset($Done[$current])) {       
            $svg .= "\"$current\" [shape=record,color=$color,style=filled,fillcolor=\"".$this->ColorNode($current,$OrderInfo['ERROR'],$OrderInfo['END_TIME'])."\"]\n";    
            // on le relie au dernier
            $svg .= "\"$last\" -> \"$current\" [label=$etape,color=$color]\n";
        }
*/        $svg .= "}\n"; // fin de chaine
/*        $svg .= $this->Order($OrderInfo);
        $svg .= '"'.$order.'" -> "'.$OrderInfo['STATE'].'" [style=dashed]'."\n";        
*/        $svg .= "}\n"; // fin de graph
        
        $tmpfile = $tmp.'/arii.dot';
        file_put_contents($tmpfile, $svg);

        $cmd = '"'.$this->graphviz_dot.'" "'.$tmpfile.'" -T '.$output;
/*
print $cmd;
print `$cmd`;
exit();
*/
        // $base = "/arii/images";
        if ($output == 'svg') {
            // exec($cmd,$out,$return);
            $out = `$cmd`;
            header('Content-type: image/svg+xml');
            // integration du script svgpan
            $head = strpos($out,'<g id="graph');
            $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1">
<script xlink:href="'.$this->container->get('templating.helper.assets')->getUrl("bundles/ariigraphviz/js/SVGPan.js").'"/>
<g id="viewport"';
            $xml .= substr($out,$head+14);
            print str_replace('xlink:href="'.$this->images,'xlink:href="'.$images_url,$xml);
            
        }
        elseif ($output == 'pdf') {
            header('Content-type: application/pdf');
            print system($cmd);
        }
        else {
            header('Content-type: image/'.$output);
            print system($cmd);
        }
        exit();
    }

    private function ColorNode($state,$error,$endtime) {
        if ($endtime=='') {
            $color='#ffffcc';
        }
        elseif ($error) {
            if (substr($state,0,1)=='!') {
                $color = 'red';
            }
            else {
                $color='#fbb4ae';
            }
        }
        else {
            $color = "#ccebc5";        
        }
        return $color;
    }
    private function Node($Infos=array()) {
        $res = '"'.$Infos['STATE'].'" '; 
        $color = $this->ColorNode($Infos['STATE'],$Infos['ERROR'],$Infos['END_TIME']);
        $res .= '[id="\N";label=<<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="'.$color.'">';
        $res .= '<TR><TD align="left" colspan="3">'.$Infos['STATE'].'</TD></TR>';
        if ($Infos['ERROR']>0) {
            $res .= '<TR><TD><IMG SRC="'.$this->images.'/error.png"/></TD><TD align="left" COLSPAN="2">'.substr($Infos['ERROR_TEXT'],15).'</TD></TR>';
        }
        if (isset($Infos['JOB_NAME'])) {
            $res .= '<TR><TD><IMG SRC="'.$this->images.'/cog.png"/></TD><TD align="left" colspan="2">'.$Infos['JOB_NAME'].'</TD></TR>';
        }
        if (isset($Infos['START_TIME'])) {
            $res .= '<TR><TD><IMG SRC="'.$this->images.'/time.png"/></TD><TD align="left" >'.$Infos['START_TIME'].'</TD><TD align="left" >'.$Infos['END_TIME'].'</TD></TR>';
        }
        $res .= "</TABLE>> URL=\"javascript:parent.JobDetail();\"]";
        return "$res\n";
    }
    
    private function Order($Infos=array()) {
        $res = '"'.$Infos['NAME'].'" '; 
        if ($Infos['END_TIME']=='') {
            $color='#ffffcc';
        }
        elseif ($Infos['ERROR']) {
            $color='#fbb4ae';
        }
        else {
            $color = "#ccebc5";        
        }
        $res .= '[id="\N";label=<<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="'.$color.'">';
        $res .= '<TR><TD><IMG SRC="'.$this->images.'/lightning.png"/></TD><TD align="left">'.$Infos['NAME'].'</TD></TR>';
        if ($Infos['TITLE']!='') {
                $res .= '<TR><TD><IMG SRC="'.$this->images.'/comment.png"/></TD><TD align="left">'.$Infos['TITLE'].'</TD></TR>';
        }
        if ($Infos['ERROR']>0) {
            $res .= '<TR><TD><IMG SRC="'.$this->images.'/error.png"/></TD><TD align="left">'.$Infos['ERROR_TEXT'].'</TD></TR>';
        }
        $res .= '</TABLE>>]';
        return "$res\n";
    }

}
