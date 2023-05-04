<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Service\Validator;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ArticleController extends AbstractController
{
    //AJOUT ARTICLE
    #[Route('/article', name: 'add_article', methods: ['POST'])]
    public function add(EntityManagerInterface $em, Request $r, Validator $v): Response
    {
        //validation token
        $headers = $r->headers->all();

        if($headers['token'] != null && !empty($headers['token'])){
            $jwt = current($headers['token']);
            $key = $this->getParameter('jwt_secret');

            try{
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 403);
            }

            //Validation du rôle
            if($decoded->roles != null && in_array('ROLE_ADMIN', $decoded->roles)){
                $article = new Article();

                $article->setTitle($r->get('title'));
        
                $article->setContent($r->get('content'));
        
                $article->setCreatedAt(new \DateTimeImmutable('now'));
        
                $article->setState($r->get('state'));
        
                $article->setPublishmentDate(new \DateTimeImmutable($r->get('publishmentDate')));
        
                //on vérifie si l'utilisateur existe
                $user = $em->getRepository(User::class)->findOneBy(['id' => $r->get('user')]);
                if($user == null){
                    return new JsonResponse('Utilisateur introuvable', 204);
                }
                $article->setAuthor($user);
        
                //on vérifie si la catégorie existe
                $category = $em->getRepository(Category::class)->findOneBy(['id' => $r->get('category')]);
                if($category == null){
                    return new JsonResponse('Catégorie introuvable', 204);
                }
                $article->setCategory($category);
        
                $isValid = $v->isValid($article);
                if($isValid !== true){
                    return new JsonResponse($isValid, 400);
                }
    
                $em->persist($article);
                $em->flush();
        
                return new JsonResponse('Article ajouté', 200);

            }
            return new JsonResponse('Vous ne disposez pas des droits pour ajouter un article', 403);
            
        }
        return new JsonResponse('Token invalide', 203);

    }

    //DETAIL ARTICLE
    #[Route('/article/{id}', name: 'one_article', methods: ['GET'])]
    public function one_article($id, EntityManagerInterface $em): Response
    {
        $article = $em->getRepository(Article::class)->findOneBy(['id' => $id]);

        $comments = $em->getRepository(Comment::class)->findBy(['article' => $article]);

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
            return [
                'id' => $comment->getId(),
                'comment' => $comment->getComment(),
                'author' => $comment->getAuthor()->getEmail(),
                'createdAt' => $comment->getCreatedAt()
            ];
        }, $comments)], 200);
    }

    //UPDATE ARTICLE
    #[Route('/article/{id}', name: 'update_article', methods: ['PATCH'])]
    public function update(Article $article = null, Request $r, Validator $v, EntityManagerInterface $em) : Response
    {
        if($article == null){
            return new JsonResponse('Article introuvable', 404);
        }
        $params = 0;

        //validation token
        $headers = $r->headers->all();

        if($headers['token'] != null && !empty($headers['token'])){
            $jwt = current($headers['token']);
            $key = $this->getParameter('jwt_secret');

            try{
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 403);
            }

            //Validation du rôle
            if($decoded->roles != null && in_array('ROLE_ADMIN', $decoded->roles)){

                if($r->get('title') != null){
                    $article->setTitle($r->get('title'));
                    $params++;
                }

                if($r->get('content') != null){
                    $article->setContent($r->get('content'));
                    $params++;
                }

                if($r->get('state') != null){
                    $article->setState($r->get('state'));
                    $params++;
                }

                if($r->get('publishmentDate') != null){
                    $article->setPublishmentDate(new \DateTimeImmutable($r->get('publishmentDate')));
                    $params++;
                }

                if($r->get('user') != null){
                    $user = $em->getRepository(User::class)->findOneBy(['id' => $r->get('user')]);
                    if($user == null){
                        return new JsonResponse('Utilisateur introuvable', 204);
                    }
                    $article->setAuthor($user);
                    $params++;
                }

                if($r->get('category') != null){
                    $category = $em->getRepository(Category::class)->findOneBy(['id' => $r->get('category')]);
                    if($category == null){
                        return new JsonResponse('Catégorie introuvable', 204);
                    }
                    $article->setCategory($category);
                    $params++;
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
                    return new JsonResponse('Aucun paramètre à modifier', 200);
                }
            }
            return new JsonResponse('Vous ne disposez pas des droits pour modifier un article', 403);
        }
        return new JsonResponse('Token invalide', 203);
    }
    
    //DELETE
    #[Route('/article/{id}', name: 'delete_article', methods: ['DELETE'])]
    public function delete(Article $article = null, EntityManagerInterface $em, Request $r) : Response{
        if($article == null){
            return new JsonResponse('Article introuvable', 204);
        }

        //validation token
        $headers = $r->headers->all();

        if($headers['token'] != null && !empty($headers['token'])){
            $jwt = current($headers['token']);
            $key = $this->getParameter('jwt_secret');

            try{
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 403);
            }

            //Validation du rôle
            if($decoded->roles != null && in_array('ROLE_ADMIN', $decoded->roles)){
                $em->remove($article);
                $em->flush();

                return new JsonResponse('Article supprimé', 200);
            }
            return new JsonResponse('Vous ne disposez pas des droits pour supprimer un article', 403);
        }
        return new JsonResponse('Token invalide', 203);
    }
}