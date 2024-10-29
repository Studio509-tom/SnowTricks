<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Entity\User;
use App\Form\TrickType;
use App\Repository\CommentRepository;
use App\Repository\TrickRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\CommentType;
use App\Repository\UserRepository;

#[Route('/trick')]
final class TrickController extends AbstractController
{
    #[Route(name: 'app_trick_index', methods: ['GET'])]
    public function index(TrickRepository $trickRepository): Response
    {
        return $this->render('trick/index.html.twig', [
            'tricks' => $trickRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_trick_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($trick);
            $entityManager->flush();

            return $this->redirectToRoute('app_trick_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trick/new.html.twig', [
            'trick' => $trick,
            'form' => $form,
            

        ]);
    }

    #[Route('/{id}', name: 'app_trick_show', methods: ['GET'])]
    public function show(Trick $trick, CommentRepository $commentRepository, UserRepository $userRepository): Response
    {
        $comment = new Comment();
        $comment->setTrick($trick);
        $comments = $commentRepository->findBy(['trick' => $trick->getId()] , array('id' => 'DESC'));
        $form_comment = $this->createForm(CommentType::class, $comment, [
            "trick" => $trick,
        ]);
        $users = $userRepository->findAll();
        $arr_users = [];
        foreach ($comments as $com) {
            foreach ($users as $user) {
                if ($user->getId() == $com->getUser()->getId()) {
                    $arr_users[$user->getId()] = $user;
                }
            }
        }
        
        $date_modify = $trick->getDateModify();
        $date_create = $trick->getDateCreate();
        $first_file = $trick->getFirstFile();
        return $this->render('trick/show.html.twig', [
            'trick' => $trick,
            'comments' => $comments,
            'users' => $arr_users,
            'form_comment' => $form_comment->createView(),
            "modify" => FALSE,
            'date_modify' => $date_modify,
            'date_create' => $date_create,
            'first_file_defined' => is_null($trick->getFirstFile()) ? true : false,
            'first_file' => is_null($first_file) || empty($first_file) ? null : $first_file[0],

        ]);
    }

    #[Route('/{id}/edit', name: 'app_trick_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Trick $trick, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_trick_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trick/edit.html.twig', [
            'trick' => $trick,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_trick_delete', methods: ['POST'])]
    public function delete(Request $request, Trick $trick, EntityManagerInterface $entityManager): Response
    {

        if ($this->isCsrfTokenValid('delete' . $trick->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($trick);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_trick_index', [], Response::HTTP_SEE_OTHER);
    }
}
