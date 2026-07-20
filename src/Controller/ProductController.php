<?php

namespace App\Controller;

use App\Entity\Stock;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Product;
use App\Form\ProductType;
use App\Form\StockType;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\CssSelector\XPath\Extension\FunctionExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/editor/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('Image')->getData();

            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileImagename = $slugger->slug($originalFilename);
                $newFileImagename = $safeFileImagename . '-' . uniqid() . '.' . $image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('product_images_directory'),
                        $newFileImagename
                    );
                } catch (FileException $exception) {}
                    $product->setImage($newFileImagename);
            }
            
            $entityManager->persist($product);
            $entityManager->flush();

            $stockHistory = new Stock();
            $stockHistory->setQuantity($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new DateTimeImmutable());
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success','product added');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/stock/{id}/',name:'app_product_stock_add', methods: ['GET','POST'])]
    public function stockAdd($id, EntityManagerInterface $entityManager,ProductRepository $productRepository, Product $product, Request $request): Response
    {
        $stockAdd = new Stock();

        $form = $this->createForm(StockType::class, $stockAdd);
        $form->handleRequest($request);

        $product = $productRepository->find($id);

        if($form -> isSubmitted() && $form->isValid()){

            if($stockAdd->getQuantity()>0){

                $newQuantity = $product->getStock() + $stockAdd->getQuantity();
                $product->setStock($newQuantity);

                $stockAdd->setCreatedAt(new DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
                $stockAdd->setProduct($product);

                $entityManager->persist($stockAdd);
                $entityManager->flush();

                $this->addFlash('success', "Product stock has been updated");
                return $this->redirectToRoute('app_product_index');
            }else{
                $this->addFlash('danger', "Product stock can't be below 0");
                return $this->redirectToRoute('app_product_stock_add', ['id' => $product->getId()]);
            }
        }


        return $this->render('product/addStock.html.twig',[
            'form'=> $form->createView(),
            'product' => $product,
        ]);
    }

        #[Route('/add/product/{id}/stock/history',name:'app_product_stock_add_history', methods: ['GET'])]
        public function showHistoryProductStock($id, ProductRepository $productRepository, Stock $stock, StockRepository $stockRepository): Response
        {
            $product = $productRepository->find($id);
            $stock = $stockRepository->findBy(['product'=>$product],['id'=>'DESC']);

            return $this->render('product/addedHistoryStockShow.html.twig',["productsAdded"=>$stock , 'product' => $product,],);
        }

}
