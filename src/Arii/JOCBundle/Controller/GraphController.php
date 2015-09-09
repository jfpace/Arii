<?php

namespace Arii\JOCBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class GraphController extends Controller
{
    public function percentAction($percent=100,$color='LEGEND') {        
        $request = Request::createFromGlobals();
        // Heure locale
        if ($request->query->get( 'percent' )!='') {
            $percent = $request->query->get( 'percent' );
        }
        if ($request->query->get( 'color' )!='')
            $color = $request->query->get( 'color' );

        $width = 50; $height = 16;
        if ($percent<2) $percent=2;
        $im = imagecreate(round($percent/2),$height);
        if (substr($color,0,1)=='#') {
            $col = imagecolorallocate($im, hexdec(substr($color,1,2)), hexdec(substr($color,3,2)), hexdec(substr($color,5,2)));
        }
        else {
            $white = imagecolorallocate($im, 255, 255, 255);
            $black = imagecolorallocate($im, 0, 0, 0);
            $green = imagecolorallocate($im, 50, 120, 40);
            $red = imagecolorallocate($im, 255, 0, 0);
            $orange = imagecolorallocate($im, 255, 200, 70);
            $grey = imagecolorallocate($im, 200, 200, 200);
            $color_rgb = array( 'READY' => $green,
                            'green' => $green,
                            'black' => $black,
                            'red' => $red,
                            'orange' => $orange );
            if (isset($color_rgb[$color]))
                $col = $color_rgb[$color];
            else 
                $col = $color_rgb['black'];
        }
            header ("Content-type: image/png");
        // imagefilledrectangle($im, 100-$percent, 1, $width, $height-2, $col);
        imagefilledrectangle($im, 0, 1, round($percent/2), $height-2, $col);
        imagepng($im);        
        exit();
    }
}
