<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\TokenValidator;
use App\Service\UserValidator;
use App\Service\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

class CommentController extends AbstractController
{
    #[Route('/comment/{id}', name: 'app_comment_moderate', methods: ['PATCH'])]
    public function show(Comment $comment = null, EntityManagerInterface $em, Request $r, Validator $v, TokenValidator $t, UserValidator $u): Response
    {
    
        if ($comment === null) {
            return new JsonResponse('Comment not found', 404);
        }

        $params = 0;

        if ($r->get('state') != null){
            $params++;
            $comment->setState($r->get('state'));
        }

        $header = $r->headers->all();

        $checkToken = $t->checkToken($header);
        if(is_array($checkToken) && $checkToken[0] === true){
            $checkUser = $u->checkUser($checkToken[1]);
            if($checkUser === true){
                if ($params > 0){
                    $isValid = $v->isValid($comment);
                    if($isValid !== true){
                        return new JsonResponse($isValid, 400);
                    }
                    $em->persist($comment);
                    $em->flush();

                    return new JsonResponse('Comment updated', 200);
                }
            }
            else{
                return $checkUser;
            }
        }else{
            return $checkToken;
        }

        return new JsonResponse('No changes', 400);
    }
}
