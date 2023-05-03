<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/category/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show($id, Category $category = null, EntityManagerInterface $em){
        $category = $em->getRepository(Category::class)->findOneBy(['id' => $id]);

        $articles = $em->getRepository(Article::class)->findBy(['category' => $category]);

        if ($category === null) {
            return new JsonResponse('Category not found', 404);
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

    #[Route('/category', name: 'app_category_create', methods: ['POST'])]
    public function create(EntityManagerInterface $em, Request $request, Validator $validator){

        $category = new Category();

        $category->setTitle($request->get('title'));

        $isValid = $validator->isValid($category);
        if($isValid !== true){
            return new JsonResponse($isValid, 400);
        }

        $em->persist($category);
        $em->flush();

        return new JsonResponse('Category created', 200);
    }

    #[Route('/category/{id}', name: 'app_category_update', methods: ['PATCH'])]
    public function update(Category $category = null, EntityManagerInterface $em, Request $request, Validator $validator){

        if ($category == null) {
            return new JsonResponse('Category not found', 404);
        }

        $params = 0;

        if($request->get('title') !== null){
            $params++;
            $category->setTitle($request->get('title'));
        }

        if($params > 0){

            $isValid = $validator->isValid($category);
            if($isValid !== true){
                return new JsonResponse($isValid, 400);
            }
            $em->persist($category);
            $em->flush();

            return new JsonResponse('Category updated', 200);
        }else{
            return new JsonResponse('No parameters to update', 400);
        }

        return new JsonResponse('success', 200);
    }

    #[Route('/category/{id}', name: 'app_category_delete', methods: ['DELETE'])]
    public function delete(Category $category = null, EntityManagerInterface $em){

        if ($category == null) {
            return new JsonResponse('Category not found', 404);
        }
        
        $em->remove($category);
        $em->flush();

        return new JsonResponse('Category deleted', 200);
    }
}
