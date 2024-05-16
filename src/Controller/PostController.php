<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PostRepository;
use App\Entity\Post;
use App\Entity\Comment;
use App\Form\PostType;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Security;


class PostController extends AbstractController
{
    #[Route('/', name: 'post')]
    public function index(PostRepository $postRepository , PaginatorInterface $paginator, Request $request): Response
    {
        $posts = $paginator->paginate(
            $postRepository->findAll(), 
            $request->query->getInt('page', 1), 
            6 
        );
        return $this->render('post/index.html.twig', [
            'posts' => $posts
        ]);
    }

    #[Route('/mypost', name: 'mypost')]
    public function mypost(PostRepository $postRepository , PaginatorInterface $paginator, Request $request): Response
    {
        $posts = $postRepository->findAll();
        return $this->render('post/mypost.html.twig', [
            'posts' => $posts
        ]);
    }

    #[Route('/post/new', name: 'post_new')]
    public function create(Request $request, ManagerRegistry $doctrine){
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $post->setCreatedAt(new \DateTime());
            $post->setAuthor($this->getUser());
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Votre publication a été ajoutée avec succées'
            );
            return $this->redirectToRoute('post');
        }
        return $this->render('post/new.html.twig', [
            'form' => $form->createView()
        ]);
    }


    #[Route('/post/{id}', name: 'post_show')]
    public function show(Request $request, PostRepository $postRepository,ManagerRegistry $doctrine)
    {
        $postId = $request->attributes->get('id');
        $post = $postRepository->find($postId);
        $comment = new Comment();

        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);
        $this->addComment($commentForm, $comment, $post,$doctrine);
        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm->createView()
        ]);
    }


    #[Route('/post/{id}/edit', name:'post_edit')]
    public function edit(Post $post, Request $request, ManagerRegistry $doctrine)
    {
        if ($this->getUser() !== $post->getAuthor()) {
            throw $this->createAccessDeniedException();
        }
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Votre publication a été modifiée'
            );
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('/post/edit.html.twig', [
            'post' => $post,
            'editForm' => $form->createView()
        ]);
    }

    #[Route('/comment/delete/{id}', name: 'delete_comment')]
    public function delete(Request $request, $id, ManagerRegistry $doctrine)
    {
        $comment = $doctrine->getRepository(Comment::class)->find($id);
        $entityManager = $doctrine->getManager();
        $entityManager->remove($comment);
        $entityManager->flush();
        $this->addFlash(
            'success',
            'Ce commentaire a été modifié avec succés'
        );
        $response = new Response();
        $response->send();
        return $this->redirectToRoute('post');
    }

    #[Route('/adminpage', name: 'adminpage')]
    public function admin(PostRepository $postRepository , PaginatorInterface $paginator, Request $request): Response
    {
        $posts = $paginator->paginate(
            $postRepository->findAll(), 
            $request->query->getInt('page', 1), 
            6 
        );
        return $this->render('post/admin.html.twig', [
            'posts' => $posts
        ]);
    }

    #[Route('/post/delete/{id}', name: 'delete_post')]
    public function deletepost(Request $request, $id, ManagerRegistry $doctrine)
    {   
      
        $post = $doctrine->getRepository(Post::class)->find($id);
        $entityManager = $doctrine->getManager();
        $entityManager->remove($post);
        $entityManager->flush();
        $this->addFlash(
            'success',
            'Cette publication a été supprimée avec succés'
        );
        $response = new Response();
        $response->send();
        return $this->redirectToRoute('mypost');

    }
    

    private function addComment($commentForm, $comment, $post,ManagerRegistry $doctrine)
    {
        if($commentForm->isSubmitted() && $commentForm->isValid()){
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setPost($post);
            $entityManager = $doctrine->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Votre commentaire a été ajoué avec succées'
            );
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }
    }




    

    

    
}
