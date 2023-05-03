<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Firebase\JWT\JWT;

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
        $user = $em ->getRepository(User::class)->findOneBy(['email' => $r->get('email')]);

        if($user === null){
            return new JsonResponse('Utilisateur introuvable', 404);
        }

        if($r->get('pwd') == null || !$uph -> isPasswordValid($user, $r->get('pwd'))){
            return new JsonResponse('Mot de passe incorrect', 400);
        }

        $key = $this->getParameter('jwt_secret');
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'roles' => $user->getRoles(),
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');

        return new JsonResponse($jwt, 200);
    }
}
