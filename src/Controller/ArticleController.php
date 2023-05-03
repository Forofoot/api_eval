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

class ArticleController extends AbstractController
{
    //ADD AN ARTICLE
    #[Route('/article', name: 'add_article', methods: ['POST'])]
    public function add(EntityManagerInterface $em, Request $r, Validator $v): Response
    {
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
}
