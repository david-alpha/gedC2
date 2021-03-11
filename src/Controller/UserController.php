<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Utilisateur;

class UserController extends AbstractController
{
    #[Route('/user', name: 'user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => '',
        ]);
    }
	#[Route('/createUser', name: 'createUser')]
    public function createUser(Request $request, EntityManagerInterface $manager): Response
    {
		$User = new Utilisateur();
		$User->setNom($request->request->get('nom'));
		$User->setPrenom($request->request->get('prenom'));
		$User->setCode($request->request->get('code'));
		$User->setSalt($request->request->get('salt'));
		
		$manager->persist($User);
		$manager->flush();
		
        return $this->render('user/index.html.twig', [
            'controller_name' => 'Un utilisateur a été ajouté',
        ]);
    }
	#[Route('/listeUser', name: 'listeUser')]
    public function listeUser(Request $request, EntityManagerInterface $manager): Response
    {
		//Récupération de tous les utilisateurs
		$listeUser = $manager->getRepository(Utilisateur::class)->findAll();
        return $this->render('user/listeUser.html.twig', [
            'controller_name' => 'Liste des utilisateurs',
            'listeUser' => $listeUser,
        ]);
    }
	#[Route('/updateUser/{id}', name: 'updateUser')]
    public function updateUser(Request $request, EntityManagerInterface $manager, Utilisateur $id ): Response
    {
		$sess = $request->getSession();
		//Créer des variables de session
		$sess->set("idUserModif", $id->getId());
		return $this->render('user/updateUser.html.twig', [
            'controller_name' => "Mise à jour d'un Utilisateur",
            'user' => $id,
        ]);
    }
	#[Route('/updateUserBdd', name: 'updateUserBdd')]
    public function updateUserBdd(Request $request, EntityManagerInterface $manager): Response
    {
		$sess = $request->getSession();
		//Créer des variables de session
		$id = $sess->get("idUserModif");
		$user = $manager->getRepository(Utilisateur::class)->findOneById($id);
		if(!empty($request->request->get('nom')))
			$user->setNom($request->request->get('nom'));
		if(!empty($request->request->get('prenom')))
			$user->setPrenom($request->request->get('prenom'));
		if(!empty($request->request->get('code')))
			$user->setCode($request->request->get('code'));
		$manager->persist($user);
		$manager->flush();
		
		return $this->redirectToRoute('listeUser');
    }
}
