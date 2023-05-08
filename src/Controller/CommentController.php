<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Article;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\TokenValidator;
use App\Service\UserValidator;
use App\Service\Validator;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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

        return new JsonResponse('Token invalide', 404);
    }

    #[Route('/comment/{id}', name: 'app_comment_add', methods: ['POST'])]
    public function add($id ,EntityManagerInterface $em, Request $r, Validator $v, TokenValidator $t, UserValidator $u): Response
    {
        $comment = new Comment();

        $article = $em->getRepository(Article::class)->findOneBy(['id' => $id]);

        if ($article === null) {
            return new JsonResponse('Article not found', 404);
        }

        $comment->setComment($r->get('comment'));
        $comment->setArticle($article);
        $comment->setCreatedAt(new DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $comment->setState(true);
        $header = $r->headers->all();

        $checkToken = $t->checkToken($header);
        if(is_array($checkToken) && $checkToken[0] === true){
            $currentUser = $em->getRepository(User::class)->findOneBy(['id' => $checkToken[1]->id]);
            $comment->setAuthor($currentUser);
            $isValid = $v->isValid($comment);
            if($isValid !== true){
                return new JsonResponse($isValid, 400);
            }
            $em->persist($comment);
            $em->flush();

            return new JsonResponse('Comment added', 200);
        }else{
            return $checkToken;
        }
    }
}
