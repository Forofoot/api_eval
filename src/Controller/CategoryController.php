<?php

namespace App\Controller;

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
        $category = $em->getRepository(Category::class)->find($id);

        if ($category === null) {
            return new JsonResponse('Category not found', 204);
        }

        return new JsonResponse($category);
    }
}
