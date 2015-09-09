<?php

namespace Arii\GraphvizBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DropzoneController extends Controller
{
     
    public function indexAction()
    {
        return $this->render('AriiGraphvizBundle:Dropzone:index.html.twig');
    }

    public function uploadAction() {
        $session = $this->container->get('arii_core.session');
        $current_dir = $session->get('current_dir');
        
        $ds = DIRECTORY_SEPARATOR;
        $storeFolder = $directory = $this->container->getParameter('workspace').$ds.'/enterprises/'.$session->get('enterprise').'/spoolers';
        $target = $current_dir;
        if (!empty($_FILES)) {
            $tempFile = $_FILES['file']['tmp_name'];            
            $targetPath = $storeFolder . $ds . $target;
            print $targetPath;
            
            // est ce un zip ?
            if ($_FILES['file']['type']=='application/x-zip-compressed' ) {
                $this->unzip($tempFile, $targetPath.'/' , true, true);
            }
            else {
                $targetFile =  $targetPath. $ds.  $_FILES['file']['name']; 
                move_uploaded_file($tempFile,$targetFile); 
            }
         }
         exit();

         
    }

    /**
     * Unzip the source_file in the destination dir
     *
     * @param   string      The path to the ZIP-file.
     * @param   string      The path where the zipfile should be unpacked, if false the directory of the zip-file is used
     * @param   boolean     Indicates if the files will be unpacked in a directory with the name of the zip-file (true) or not (false) (only if the destination directory is set to false!)
     * @param   boolean     Overwrite existing files (true) or not (false)
     * 
     * @return  boolean     Succesful or not
     */
    function unzip($src_file, $dest_dir=false, $create_zip_name_dir=true, $overwrite=true)
    {
      if ($zip = zip_open($src_file))
      {
        if ($zip)
        {
          $splitter = ($create_zip_name_dir === true) ? "." : "/";
          if ($dest_dir === false) $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter))."/";

          // Create the directories to the destination dir if they don't already exist
          $this->create_dirs($dest_dir);

          // For every file in the zip-packet
          while ($zip_entry = zip_read($zip))
          {
            // Now we're going to create the directories in the destination directories

            // If the file is not in the root dir
            $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
            if ($pos_last_slash !== false)
            {
              // Create the directory where the zip-entry should be saved (with a "/" at the end)
              $this->create_dirs($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
            }

            // Open the entry
            if (zip_entry_open($zip,$zip_entry,"r"))
            {

              // The name of the file to save on the disk
              $file_name = $dest_dir.zip_entry_name($zip_entry);

              // Check if the files should be overwritten or not
              if (is_file($file_name)) {
                if ($overwrite === true || $overwrite === false )
                {
                  // Get the content of the zip entry
                  $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
  print "<hr/>((($fstream))->(($file_name))";
                  file_put_contents($file_name, $fstream );
                  // Set the rights
                  chmod($file_name, 0777);
                  echo "save: ".$file_name."<br />";
                }
                else {
//                    $this->create_dirs($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
                }
              }
              // Close the entry
              zip_entry_close($zip_entry);
            }      
          }
          // Close the zip-file
          zip_close($zip);
        }
      }
      else
      {
        return false;
      }

      return true;
    }

    /**
     * This function creates recursive directories if it doesn't already exist
     *
     * @param String  The path that should be created
     * 
     * @return  void
     */
    function create_dirs($path)
    {
      if (!is_dir($path))
      {
        $directory_path = "";
        $directories = explode("/",$path);
        array_pop($directories);

        foreach($directories as $directory)
        {
          $directory_path .= $directory."/";
          if (!is_dir($directory_path))
          {
            mkdir($directory_path);
            chmod($directory_path, 0777);
          }
        }
      }
    }

    public function generateAction()
    {
        $request = Request::createFromGlobals();
        // system('C:/xampp/htdocs/Symfony/vendor/graphviz/graphviz.cmd');
        $output = array();
        $return = 0;
        
        $session = $this->getRequest()->getSession();
        $path = '.*';
        if ($request->query->get( 'path' ))
            $path = $request->query->get( 'path' );
        // d'autres repertoires selectionnes ?
        if ($request->query->get( 'paths' )) {
            $Paths= array();
            foreach (explode(',',$request->query->get( 'paths' )) as $p) {
                array_push($Paths, '^'.$this->CleanPath($p).'$');
            }
            array_push($Paths, $this->CleanPath($path));
            $path = '('.implode('|', $Paths).')';
        }
        else {
           $path = $this->CleanPath($path);
        }
        
        $file = '.*';
        $rankdir = 'TB';
        $splines = 'spline';
        $show_params = 'n';
        $show_events = 'n';
        $output = "svg";
        if ($request->query->get( 'file' ))
            $file = $request->query->get( 'file' );
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
        
        $gvz_cmd = $this->container->getParameter('graphviz_cmd'); 
        $cmd = $gvz_cmd.' "'.$path.'" "'.$file.'" "'.$splines.'" "'.$rankdir.'" "'.$show_params.'" "'.$show_events.'" "'.$output.'"';
        // print $cmd; exit();
        $base =  $this->container->getParameter('graphviz_base'); 
        if ($output == 'svg') {
            exec($cmd,$out,$return);
            header('Content-type: image/svg+xml');
            foreach ($out as $o) {
                $o = str_replace('xlink:href="../../web','xlink:href="'.$base.'',$o);
                print $o;
            }
        }
        elseif ($output == 'pdf') {
            header('Content-type: application/pdf');
            system($cmd);
        }
        else {
            header('Content-type: image/'.$output);
            system($cmd);
        }
        exit();
    }
    
    function CleanPath($path) {
        
        // bidouille en attendant la fin de l'étude
/*        if (substr($path,0,4)=='live') 
            $path = substr($path,4);
        elseif (substr($path,0,6)=='remote') 
            $path = substr($path,6);
        elseif (substr($path,0,5)=='cache') 
            $path = substr($path,5);
*/      
        $path = str_replace('/','.',$path);
        $path = str_replace('\\','.',$path);
        $path = str_replace('#','.',$path);
        
        // protection des | sur windows
        $path = str_replace('|','^|',$path);       
        
        return $path;
    }
}
