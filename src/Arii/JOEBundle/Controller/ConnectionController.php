<?php
// src/Arii/JOEBundle/Controller/DBController.php

namespace Arii\JOEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Arii\JOEBundle\Entity\Job;
use Arii\JOEBundle\Form\JobType;
use Symfony\Component\HttpFoundation\Request;

class ConnectionController extends Controller
{

    public function indexAction()
    {
        return $this->render('AriiJOEBundle:Connection:index.html.twig');
    }

    public function editAction($mode='new')
    {
        // en cas d'edit on retrouve l'id par le request (demandé par dhtmlx)
        $id = -1;
        if ($mode == 'edit') {
            $request = Request::createFromGlobals();
             if ($request->get('id')) {
                 $id = $request->get('id' );
             }
        }
        return $this->render('AriiJOEBundle:Connection:edit.html.twig', array( 'mode'=> $mode, 'id' => $id ));
    }

    public function newAction($jobtype = '')
    {
      // On crée un objet Job
      $job = new Job;
      $form =  $this->createForm(new JobType, $job);

    // On récupère la requête
        $request = $this->get('request');

        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {
          // On fait le lien Requête <-> Formulaire
          $form->bind($request);

          // On vérifie que les valeurs rentrées sont correctes
          // (Nous verrons la validation des objets en détail plus bas dans ce chapitre)
          if ($form->isValid()) {
            // On l'enregistre notre objet $article dans la base de données
            $em = $this->getDoctrine()->getManager();
            $em->persist($job);
            $em->flush();

            // On redirige vers la page de visualisation de l'article nouvellement créé
            return $this->redirect($this->generateUrl('arii_JOE_job_edit', array('id' => $job->getId())));
          }
        }

      // On passe la méthode createView() du formulaire à la vue afin qu'elle puisse afficher le formulaire toute seule
      return $this->render('AriiJOEBundle:Job:new.html.twig', array(
        'form' => $form->createView(),
      ));
     }
 
}
