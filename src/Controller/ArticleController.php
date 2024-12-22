<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    #[Route('/article/creer', name: 'app_article_create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
                $article->setImage($newFilename);
            }

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article créé avec succès !');
            return $this->redirectToRoute('app_article_fetch');
        }

        return $this->render('article/creer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/article/fetch', name: 'app_article_fetch')]
    public function fetch(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search', ''); 
    
        $queryBuilder = $entityManager->getRepository(Article::class)->createQueryBuilder('a');
    
        if ($search) {
            $queryBuilder->where('a.titre LIKE :search OR a.texte LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }
    
        $articles = $queryBuilder->getQuery()->getResult();
    
        return $this->render('article/fetch.html.twig', [
            'articles' => $articles,
            'search' => $search,
        ]);
    }
    
    #[Route('/article/update/{id}', name: 'app_article_update')]
    public function update(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }

        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article modifié avec succès !');
            return $this->render('article/update.html.twig',[
                'form' => $form->createView(),
            ]);
        }

        return $this->render('article/update.html.twig', [
            'form' => $form->createView(),
        ]);
    }

        #[Route('/article/delete/{id}', name: 'app_article_delete')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('delete', 'Article supprimé avec succès !');

        return $this->redirectToRoute('app_article_fetch');
    }

        #[Route('/article/{id}', name: 'app_article_show')]
    public function show(EntityManagerInterface $entityManager, id $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'Article introuvable pour l\'ID ' . $id
            );
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    public function creatingForm(Request $request): Response
    {
        $task = new Article();

        $form = $this->createForm(ArticleType::class, $task);

        return $this->render('article/creer.html.twig', [
            'form' => $form,
        ]);
    }
}