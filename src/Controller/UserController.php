<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    //LOGIN
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login (Request $r, EntityManagerInterface $em, UserPasswordHasherInterface $uph): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $r->get('email')]);

        if($user == null){
            return new JsonResponse('Utilisateur introuvable', 404);
        }

        if($r->get('pwd') == null || !$uph->isPasswordValid($user, $r->get('pwd'))){
            return new JsonResponse('Mot de passe érroné', 400);
        }

        return new JsonResponse('Vous êtes connecté', 200);
    }
}
