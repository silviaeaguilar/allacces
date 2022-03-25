<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Form\Type\ProductType;
use App\Repository\ProductRepository;
use App\Service\GetExchangeRates;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class ProductsController extends AbstractFOSRestController
{
    /**
     * @Rest\Get(path="/product")
     * @Rest\View(serializerGroups={"product"} )
     * @OA\Response(
     *     response=200,
     *     description="Returns the list of products",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"product"}))
     *     )
     * )
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
     */
    public function getActions(Request $request, ProductRepository $productRepository)
    {
        return $productRepository->findAll();
    }

    /**
     * @Rest\Get(path="/product/featured/{currency?}")
     * @Rest\View(serializerGroups={"product"} )
     * @OA\Response(
     *     response=200,
     *     description="Returns the list of featured products",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"product"}))
     *     )
     * )
     *  @OA\Parameter(
     *     name="currency",
     *     description="The field used convert the rates",
     *     in="query",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
     */
    public function getFeaturedAction(Request $request, ProductRepository $productRepository, $currency,
                                      GetExchangeRates $exchangeRates)
    {
        $featuredProducts = $productRepository->findFeatured();

        $currency = $request->query->get('currency', null);

        //Determino si debo hacer conversion
        if ($currency !== null)
        {
            $json = ($exchangeRates)($currency);
            $exchange = $json[$currency];
            $productsArray = $exchangeRates->convertList($exchange,$featuredProducts,$currency);
        }
        else {
            $productsArray = $featuredProducts;
        }

        $response = new JsonResponse();
        $response->setData(
            [
                'success'=>true,
                'data'=>$productsArray
            ]
        );

        return $response;
    }


    /**
     * @Rest\Post(path="/product")
     * @Rest\View(serializerGroups={"product"} )
     *   @OA\Response(
     *     response=200,
     *     description="Create product",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"product"}))
     *     )
     *
     * )
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
     */


    public function postActions( EntityManagerInterface $entityManager, Request $request)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($product);
            $entityManager->flush();
            return $product;
        }

        return $form;
    }


    /**
     * @Rest\Post(path="/product/{id}")
     * @Rest\View(serializerGroups={"product"} )
     * @OA\Response(
     *     response=200,
     *     description="Update product",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"product"}))
     *     )
     * )
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
     */
    public function editAction(int $id,
                               EntityManagerInterface $entityManager,
                               ProductRepository $productRepository,
                               Request $request
                     )
    {
        $product = $productRepository->find($id);
        if(!$product){
            throw $this->createNotFoundException('Product not found');
        }
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if (!$form->isSubmitted()){
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        if($form->isValid()){
            $product->setName($product->getName());
            $product->setCurrency($product->getCurrency());
            $product->setFeatured($product->getFeatured());
            $product->setPrice($product->getPrice());
            $product->setCategory($product->getCategory());
            $entityManager->persist($product);
            $entityManager->flush();
            return $product;
        }
        return $form;
    }

    /**
     * @Rest\Delete(path="/product/{id}")
     * @Rest\View(serializerGroups={"product"} )
     * @OA\Response(
     *     response=200,
     *     description="Delete product",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"product"}))
     *     )
     * )
     *  * @OA\Parameter(
     *     name="id",
     *     description="The product id to be deleted",
     *     in="query",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
     */
    public function deleteAction(int $id,
                               EntityManagerInterface $entityManager,
                               ProductRepository $productRepository,
                               Request $request
    )
    {
        $product = $productRepository->find($id);
        if(!$product){
            throw $this->createNotFoundException('Product not found');
        }
        $productRepository->remove($product,true);
        return $product;

    }
}