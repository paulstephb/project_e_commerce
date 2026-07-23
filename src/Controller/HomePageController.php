<?php

namespace App\Controller;

use App\Entity\Product;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, ProductRepository $productRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $data = $productRepository->findby([],['id'=>'DESC']);
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page',1),
            8
        );
        return $this->render('home_page/index.html.twig', [
            'controller_name' => 'HomePageController',
            'products' => $productRepository->findAll(),
            'categories'=>$categoryRepository->findAll(),
        ]);
    }

    #[Route('/product/{id}/show',name: 'app_home_product_show', methods: ['GET'])]
    public function showProduct(Product $product, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $lastProductsAdd = $productRepository->findBy([],['id'=>'DESC'],5);
        return $this->render('home_page/show.html.twig', [
            'product'=>$product,
            'categories'=>$categoryRepository->findAll(),

            'products'=>$lastProductsAdd
            ]);
    }

    #[Route('/product/subcategory/{id}/filter',name: 'app_home_product_filter', methods: ['GET'])]
    public function filter($id, CategoryRepository $categoryRepository,SubCategoryRepository $subCategoryRepository):Response
    {
        $product = $subCategoryRepository->find($id)->getProducts();
        $subCategory = $subCategoryRepository->find($id);
        return $this->render('home_page/filter.html.twig',[
            'products'=>$product,
            'subCategory'=>$subCategory,
            'categories'=>$categoryRepository->findAll(),
        ]);
    }

}