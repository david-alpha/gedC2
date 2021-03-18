<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Document;
use App\Entity\Genre;
use App\Entity\Autorisation;
use App\Entity\Utilisateur;
use App\Entity\Acces;
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
		$listeAutorisation = $manager->getRepository(Autorisation::class)->findAll();
        return $this->render('ged/uploadGed.html.twig', [
            'controller_name' => "Upload d'un Document",
            'listeGenre' => $listeGenre,
            'listeAutorisation' => $listeAutorisation,
            'listeUsers' => $manager->getRepository(Utilisateur::class)->findAll(),
        ]);
    }
	#[Route('/insertGed', name: 'insertGed')]
    public function insertGed(Request $request, EntityManagerInterface $manager): Response
    {
		$sess = $request->getSession();
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
		if($request->request->get('utilisateur') != -1){
			$user = $manager->getRepository(Utilisateur::class)->findOneById($request->request->get('utilisateur'));
			$autorisation = $manager->getRepository(Autorisation::class)->findOneById($request->request->get('autorisation'));
			$acces = new Acces();
			$acces->setUtilisateurId($user);
			$acces->setAutorisationId($autorisation);
			$acces->setDocumentId($Document);
			$manager->persist($acces);
			$manager->flush();	
		}
		//Création d'un accès pour l'uploadeur (propriétaire)
		$user = $manager->getRepository(Utilisateur::class)->findOneById($sess->get("idUtilisateur"));
			$autorisation = $manager->getRepository(Autorisation::class)->findOneById(1);
			$acces = new Acces();
			$acces->setUtilisateurId($user);
			$acces->setAutorisationId($autorisation);
			$acces->setDocumentId($Document);
			$manager->persist($acces);
			$manager->flush();	
		
		return $this->redirectToRoute('listeDocument');
    }
	
	#[Route('/listeDocument', name: 'listeDocument')]
    public function listeDocument(Request $request, EntityManagerInterface $manager): Response
    {
		//Ouverture de la session
		$sess = $request->getSession();
		
		//Récupération de l'utilisateur
		$user = $manager->getRepository(Utilisateur::class)->findOneById($sess->get("idUtilisateur"));
		$listeAcces = $manager->getRepository(Acces::class)->findByUtilisateurId($user);
		//$listeDocument = $manager->getRepository(Document::class)->findAll();
        return $this->render('ged/listeDocument.html.twig', [
            'controller_name' => 'Liste des Documents',
            //'listeDocument' => $listeDocument,
            'listeAcces' => $listeAcces,
        ]);
    }
	#[Route('/deleteDocument/{id}', name: 'delete_document')]
    public function deleteDocument(Request $request, EntityManagerInterface $manager, Document $id): Response
    {
		$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
		// supprimer le lien avec l'accés
		$recupListeacces = $manager->getRepository(Acces::class)->findByDocumentId($id);
		//dd($recupListeacces);
		foreach($recupListeacces as $doc){
			$manager->remove($doc);
			$manager->flush();
		}	
		//suppression physique du document :
		if(unlink("upload/".$id->getChemin())){
		//suppression du lien dans la base de données
			$manager->remove($id);
			$manager->flush();
		}
		return $this->redirectToRoute('listeDocument');
		}else{
			return $this->redirectToRoute('authentification');	
		}
    }
	#[Route('/permission', name: 'permission')]
    public function permission(Request $request, EntityManagerInterface $manager, Document $id): Response
    {
		$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
			//Récupération des listes
			$listeDocument = $manager->getRepository(Document::class)->findAll();
			$listeUser = $manager->getRepository(Utilisateur::class)->findAll();
			return $this->render('ged/permission.html.twig', [
            'controller_name' => "Attribution d'une permission",
            'listeDocument' => $listeDocument,
            'listeUser' => $listeUser,
        ]);
		}else{
			return $this->redirectToRoute('authentification');	
		}
    }
}
