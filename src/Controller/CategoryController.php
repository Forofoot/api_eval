<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'app_category')]
    public function index(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Category::class)->findAll();

        return new JsonResponse($categories);
    }

    #[Route('/category/{id}', name: 'app_category_show')]
    public function show($id, Category $category = null, EntityManagerInterface $em){
        $category = $em->getRepository(Category::class)->findOneBy(['id' => $id]);

        $articles = $em->getRepository(Article::class)->findBy(['category' => $category]);

        if ($category === null) {
            return new JsonResponse('Category not found', 204);
        }

        return new JsonResponse(
            [
                'category-id' => $category->getId(),
                'category-title' => $category->getTitle(),

                'articles' => array_map(function (Article $article) {
                    return [
                        'article-id' => $article->getId(),
                        'article-title' => $article->getTitle(),
                        'article-content' => $article->getContent(),
                        'article-created_at' => $article->getCreatedAt(),
                        'article-state' => $article->isState(),
                        'article-publishment_date' => $article->getPublishmentDate(),
                        'article-author' => $article->getAuthor()->getEmail(),
                    ];
                }, $articles)


            ]
            , 200);
    }
}
