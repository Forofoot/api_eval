<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Validator $v): Response
    {
        $user = new User();
        $user->setEmail($request->get('email'))
            ->setPlainPassword($request->get('pwd'));


        $isValid = $v->isValid($user);
        if($isValid !== true){
            return new JsonResponse($isValid, 400);
        }

        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            )
        );

        $user->setLastName($request->get('lastName'));

        $user->setFirstName($request->get('firstName'));

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse('Votre compte a été crée', 200);
    }
}