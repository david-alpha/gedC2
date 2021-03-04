<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Document;
use App\Entity\Genre;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use DateTime;


class GedController extends AbstractController
{
    #[Route('/uploadGed', name: 'uploadGed')]
    public function uploadGed(Request $request, EntityManagerInterface $manager): Response
    {
		//Requête pour récupérer toute la table genre
		$listeGenre = $manager->getRepository(Genre::class)->findAll();
        return $this->render('ged/uploadGed.html.twig', [
            'controller_name' => "Upload d'un Document",
            'listeGenre' => $listeGenre,
        ]);
    }
	#[Route('/insertGed', name: 'insertGed')]
    public function insertGed(Request $request, EntityManagerInterface $manager): Response
    {
		
		//création d'un nouveau document
		$Document = new Document();
		//Récupération et transfert du fichier
		//dd($request->request->get('choix'));
		$brochureFile = $request->files->get("fichier");
		if ($brochureFile){
			$newFilename = uniqid('', true) . "." . $brochureFile->getClientOriginalExtension();
            $brochureFile->move($this->getParameter('upload'), $newFilename);
			//insertion du document dans la base.
			if($request->request->get('choix') == "on"){
				$actif=1;
			}else{
				$actif=2;
			}
			$Document->setActif($actif);
			$Document->setNom($request->request->get('nom'));
			$Document->setTypeId($manager->getRepository(Genre::class)->findOneById($request->request->get('genre')));
			$Document->setCreatedAt(new \Datetime);	
			$Document->setChemin($newFilename);	
			
			$manager->persist($Document);
			$manager->flush();
		}
		
		
		
		
		//Requête pour récupérer toute la table genre
		$listeGenre = $manager->getRepository(Genre::class)->findAll();
        return $this->render('ged/uploadGed.html.twig', [
            'controller_name' => "Upload d'un Document",
            'listeGenre' => $listeGenre,
        ]);
    }
}
