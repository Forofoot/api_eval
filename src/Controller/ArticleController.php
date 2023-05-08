<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Comment;
use App\Service\TokenValidator;
use App\Service\UserValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Service\Validator;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    //AJOUT ARTICLE
    #[Route('/article', name: 'add_article', methods: ['POST'])]
    public function add(EntityManagerInterface $em, Request $r, Validator $v, TokenValidator $t, UserValidator $u): Response
    {
        //validation token
        $header = $r->headers->all();

        $article = new Article();
        
        $checkToken = $t->checkToken($header);
        if(is_array($checkToken) && $checkToken[0] === true){
            $checkUser = $u->checkUser($checkToken[1]);
            if($checkUser === true){
                $article->setTitle($r->get('title'));
                $article->setContent($r->get('content'));
                $article->setCreatedAt(new \DateTimeImmutable());
                $article->setState($r->get('state'));
                if ($r->get('state') == 'true') {
                    $article->setPublishmentDate(new \DateTimeImmutable());
                }
                $user = $em->getRepository(User::class)->findOneBy(['id' => $checkToken[1]->id]);
                $article->setAuthor($user);
                $category = $em->getRepository(Category::class)->findOneBy(['id' => $r->get('category')]);
                $article->setCategory($category);

                $isValid = $v->isValid($article);
                if($isValid !== true){
                    return new JsonResponse($isValid, 400);
                }

                $em->persist($article);
                $em->flush();

                return new JsonResponse('Article ajouté', 201);
            }
            else{
                return $checkUser;
            }
            
        }else{
            return $checkToken;
        }

    }

    //DETAIL ARTICLE
    #[Route('/article/{id}', name: 'one_article', methods: ['GET'])]
    public function one_article($id, EntityManagerInterface $em): Response
    {
        $article = $em->getRepository(Article::class)->findOneBy(['id' => $id, 'state' => true]);

        $comments = $em->getRepository(Comment::class)->findBy(['article' => $article, 'state' => true]);

        if($article == null){
            return new JsonResponse('Article introuvable', 404);
        }

        return new JsonResponse(
        [
        'id' => $article->getId(),
        'title' => $article->getTitle(),
        'content' => $article->getContent(),
        'createdAt' => $article->getCreatedAt(),
        'state' => $article->isState(),
        'publishmentDate' => $article->getPublishmentDate(),
        'author' => $article->getAuthor()->getEmail(),
        'category' => $article->getCategory()->getTitle(),
        'comments'  => array_map(function (Comment $comment) {
            if ($comment->isState() == true){
                return [
                    'id' => $comment->getId(),
                    'comment' => $comment->getComment(),
                    'author' => $comment->getAuthor()->getEmail(),
                    'createdAt' => $comment->getCreatedAt(),
                    'state' => $comment->isState()
                ];
            }
        }, $comments)], 200);
    }

    //UPDATE ARTICLE
    #[Route('/article/{id}', name: 'update_article', methods: ['PATCH'])]
    public function update(Article $article = null, Request $r, Validator $v, EntityManagerInterface $em, TokenValidator $t, UserValidator $u) : Response
    {
        if($article == null){
            return new JsonResponse('Article introuvable', 404);
        }

        $params = 0;

        //validation token
        $headers = $r->headers->all();

        $checkToken = $t->checkToken($headers);
        if(is_array($checkToken) && $checkToken[0] === true){
            $checkUser = $u->checkUser($checkToken[1]);
            if($checkUser === true){
                if($r->get('title') != null){
                    $params++;
                    $article->setTitle($r->get('title'));
                }
                if($r->get('content') != null){
                    $params++;
                    $article->setContent($r->get('content'));
                }
                if($r->get('state') != null){
                    if ($r->get('state') == true) {
                        $article->setPublishmentDate(new \DateTimeImmutable());
                    }else{
                        $article->setPublishmentDate(null);
                    }
                    $params++;
                    $article->setState($r->get('state'));
                }
                if($r->get('category') != null){
                    $params++;
                    $category = $em->getRepository(Category::class)->findOneBy(['id' => $r->get('category')]);
                    $article->setCategory($category);
                }

                if($params > 0){
                    $isValid = $v->isValid($article);
                    if($isValid !== true){
                        return new JsonResponse($isValid, 400);
                    }
                    $em->persist($article);
                    $em->flush();

                    return new JsonResponse('Article modifié', 200);
                }else{
                    return new JsonResponse('Aucun paramètre à modifier', 400);
                }
            }
            else{
                return $checkUser;
            }
        }else{
            return $checkToken;
        }
    }
    
    //DELETE
    #[Route('/article/{id}', name: 'delete_article', methods: ['DELETE'])]
    public function delete(Article $article = null, EntityManagerInterface $em, Request $r, TokenValidator $t, UserValidator $u) : Response{
        if($article == null){
            return new JsonResponse('Article introuvable', 204);
        }

        //validation token
        $headers = $r->headers->all();

        $checkToken = $t->checkToken($headers);

        if(is_array($checkToken) && $checkToken[0] === true){
            $checkUser = $u->checkUser($checkToken[1]);
            if($checkUser === true){
                $em->remove($article);
                $em->flush();

                return new JsonResponse('Article supprimé', 200);
            }
            else{
                return $checkUser;
            }
        }else{
            return $checkToken;
        }
    }
}