<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Utilisateur;
use App\Entity\Acces;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthentificationController extends AbstractController
{
    #[Route('/authentification', name: 'authentification')]
    public function index(): Response
    {
        return $this->render('authentification/index.html.twig', [
            'controller_name' => 'Page de connexion',
        ]);
    }
	 #[Route('/connexion', name: 'connexion')]
    public function connexion(Request $request, EntityManagerInterface $manager): Response
    {
        //Récupération des données du controleur
		$identifiant = $request->request->get('identifiant');
		$password    = $request->request->get('password');
		//connexion avec la BD et récupération du couple id/password
		$aUser = $manager->getRepository(Utilisateur::class)->findBy(["nom"=>$identifiant, "code"=>$password]);
		//test de l'existence d'un tel couple
		 if (sizeof($aUser)>0){
			 //Récupération de l'utilisateur 
			 $utilisateur = new Utilisateur;
			 $utilisateur = $aUser[0];
			 //Démarrage d'une session
			 $sess = $request->getSession();
			 //Créer des variables de session
			 $sess->set("idUtilisateur", $utilisateur->getId());
			 $sess->set("nomUtilisateur", $utilisateur->getNom());
			 $sess->set("prenomUtilisateur", $utilisateur->getPrenom());
			 return $this->redirectToRoute('dashboard');			 
		 }else{
			 return $this->redirectToRoute('authentification');
		 }
		//dd($reponse);
		return new response(1);
    }
	 #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(Request $request, EntityManagerInterface $manager): Response
    {
		$sess = $request->getSession();
		if($sess->get("idUtilisateur")){
			$listeDocuments = $manager->getRepository(Acces::class)->findByUtilisateurId($sess->get("idUtilisateur"));
			$listeDocumentsAll = $manager->getRepository(Acces::class)->findAll();
			$nbDocument = 0;
			$lastDocument = new \Datetime("2000-01-01");
			$documentPrives = Array();
			$flag = 0;
			// Recherche du dernier document partagé
			foreach($listeDocuments as $val){
				$nbDocument ++ ;
				if($val->getDocumentId()->getCreatedAt() > $lastDocument){
					$lastDocument = $val->getDocumentId()->getCreatedAt();
					$doc = $val->getDocumentId();
				}
			// Recherche du dernier documents privés.
				foreach($listeDocumentsAll as $valeur){
					if($valeur->getDocumentId()->getId() == $val->getDocumentId()->getId() && $valeur->getUtilisateurId()->getId() != $sess->get("idUtilisateur")){
							$flag = $flag +1;	
					}
				}
				if($flag==0){
					$documentPrives[] = $val;		
				}
			$flag=0;
			}
			dump($documentPrives);
			return $this->render('authentification/dashboard.html.twig', [
				'controller_name' => 'Espace Client',
				'nbDocument' => $nbDocument,
				'doc' => $doc,
				'listeDocUtilisateur' => $documentPrives
			]);
		}else{
			return $this->redirectToRoute('authentification');
		}		
    }
	 #[Route('/deconnexion', name: 'deconnexion')]
    public function deconnexion(Request $request, EntityManagerInterface $manager): Response
    {
		$sess = $request->getSession();
		$sess->remove("idUtilisateur");
		$sess->invalidate();
		$sess->clear();
		$sess=$request->getSession()->clear();
        return $this->redirectToRoute('authentification');
    }
}
