<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Form\Type\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Security;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class CategoriesController extends AbstractFOSRestController
{
    /**
     * @Rest\Get(path="/categories")
     * @Rest\View(serializerGroups={"category"} )
     * @OA\Response(
     *     response=200,
     *     description="Returns the list of categories",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Category::class, groups={"category"}))
     *     )
     * )
     * @OA\Tag(name="category")
     * @Security(name="Bearer")
     */
    public function getActions(Request $request, CategoryRepository $categoryRepository)
    {
        return $categoryRepository->findAll();
    }

    /**
     * @Rest\Post(path="/categories")
     * @Rest\View(serializerGroups={"category"} )
     * @OA\Response(
     *     response=200,
     *     description="Create category",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Category::class, groups={"category"}))
     *     )
     *
     * )
     * @OA\Tag(name="category")
     * @Security(name="Bearer")
     */
    public function postActions( EntityManagerInterface $entityManager, Request $request)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($category);
            $entityManager->flush();
            return $category;
        }

        return $form;
    }

    /**
     * @Rest\Post(path="/categories/{id}")
     * @Rest\View(serializerGroups={"category"} )
     * @OA\Response(
     *     response=200,
     *     description="Update category",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Category::class, groups={"category"}))
     *     )
     * )
     * @OA\Tag(name="category")
     * @Security(name="Bearer")
     */
    public function editAction(int $id,
                               EntityManagerInterface $entityManager,
                               CategoryRepository $categoryRepository,
                               Request $request
    )
    {
        $category = $categoryRepository->find($id);
        if(!$category){
            throw $this->createNotFoundException('Category not found');
        }
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if (!$form->isSubmitted()){
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        if($form->isValid()){
            $category->setName($category->getName());
            $entityManager->persist($category);
            $entityManager->flush();
            return $category;
        }
        return $form;
    }

    /**
     * @Rest\Delete(path="/categories/{id}")
     * @Rest\View(serializerGroups={"category"} )
     * @OA\Response(
     *     response=200,
     *     description="Delete category",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Category::class, groups={"category"}))
     *     )
     * )
     *  * @OA\Parameter(
     *     name="id",
     *     description="The category id to be deleted",
     *     in="query",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="category")
     * @Security(name="Bearer")
     */
    public function deleteAction(int $id,
                                 CategoryRepository $categoryRepository,
                                ProductRepository $productRepository
    )
    {
        $category = $categoryRepository->find($id);
        if(!$category){
            throw $this->createNotFoundException('Product not found');
        }
        $products = $productRepository->findBy(['category' =>$id]);
        if($products){
            throw $this->createNotFoundException('This category has products');
        }
        $categoryRepository->remove($category,true);
        return $category;

    }
}