<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
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
    
}