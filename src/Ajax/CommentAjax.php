<?php

namespace App\Ajax;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Entity\User;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\DoctrineType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('/ajax/comment')]
final class CommentAjax extends AbstractController
{
    function __construct(RequestStack $requestStack)
    {
        // Vérification si requête Ajax
        if (!$requestStack->getCurrentRequest()->isXmlHttpRequest()) {
            throw new \TypeError("Accès refuser");
        }
    }

    #[Route('/new/{trick}', name: 'app_ajax_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Trick $trick, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, CommentRepository $commentRepository, UserRepository $userRepository): Response
    {
        $comment = new Comment();
        // Associer le commentaire à l'entité User
        $comment->setUser($this->getUser());
        // Récupérer le formulaire
        $form_comment = $this->createForm(CommentType::class, $comment);
        $form_comment->handleRequest($request);

        if ($form_comment->isSubmitted() && $form_comment->isValid()) {
            // Associer le commentaire à l'entité Trick
            $comment->setTrick($trick);

            $entityManager->persist($comment);
            $entityManager->flush();
            // Récupération des commentaires du plus récent au plus vieux
            $comments = $commentRepository->findBy(['trick' => $trick->getId()], array('id' => 'DESC'));
            // Récupération de tout les users
            $users = $userRepository->findAll();
            $arr_users = [];

            // Récupération des users qui on déjà commenter
            foreach ($comments as $com) {
                foreach ($users as $user) {
                    if ($user->getId() == $com->getUser()->getId()) {
                        $arr_users[$user->getId()] = $user;
                    }
                }
            }
            // Retour
            $comments_html = $this->render('trick/comments.html.twig', [
                "trick" => $trick,
                "comments" => $comments,
                "form_comment" => $form_comment,
                'users' => $arr_users,
                'modify' => FALSE,
            ]);
            return new JsonResponse(['comments_html' => $comments_html->getContent()]);
        }


        $create_html = $this->render('trick/new.html.twig', [
            'comment' => $comment,
            'form_comment' => $form_comment,
            'modify' => FALSE,
        ]);

        return new JsonResponse(['create_html' => $create_html->getContent()]);
    }

    #[Route('/{id}/edit', name: 'app_ajax_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, ManagerRegistry $doctrine, EntityManagerInterface $entityManager, CommentRepository $commentRepository, UserRepository $userRepository): Response
    {
        // Récupération de tous les commentaires

        $form_comment = $this->createForm(CommentType::class, $comment);
        $form_comment->handleRequest($request);
        $users = $userRepository->findAll();
        $arr_users = [];
        $trick = $comment->getTrick();

        // Récupérer l'entité Trick correspondante 
        $comments = $commentRepository->findBy(['trick' => $trick->getId()], array('id' => 'DESC'));

        foreach ($comments as $com) {
            foreach ($users as $user) {
                if ($user->getId() == $com->getUser()->getId()) {
                    $arr_users[$user->getId()] = $user;
                }
            }
        }

        if ($form_comment->isSubmitted() && $form_comment->isValid()) {
            // Récupérer l'ID du Trick à partir du champ caché
            $trickId = $trick->getId();
            // Récupérer l'entité Trick correspondante 
            $trick = $doctrine->getRepository(Trick::class)->find($trickId);
            $entityManager->flush();
            // Récupérer les commentaires actualisés
            $comments = $commentRepository->findBy(['trick' => $trick->getId()], array('id' => 'DESC'));

            $comments_html = $this->render('trick/comments.html.twig', [
                'modify' => FALSE,
                'comments' => $comments,
                'users' =>  $arr_users,
                'form_comment' => $form_comment->createView(),
            ]);

            // Retourner une réponse JSON après la mise à jour
            return new JsonResponse(['comments_html' => $comments_html->getContent()]);
        }

        // Tableau de formulaires individuels pour chaque commentaire
        $forms = [];
        foreach ($comments as $com) {
            $forms[$com->getId()] = $this->createForm(CommentType::class, $com)->createView();
        }

        // Rendu de la vue Twig avec le formulaire spécifique pour la modification
        $edit_html = $this->render('trick/comments.html.twig', [
            'comments' => $comments,
            'form_comment' => $form_comment->createView(),  // Formulaire pour créer un nouveau commentaire
            'modify' => true,
            'forms' => $forms,
            'users' =>  $arr_users,
            'id_comment' => $request->request->get('id_comment'),
        ]);

        return new JsonResponse(['edit_html' => $edit_html->getContent()]);
    }

    #[Route('/{id}', name: 'app_ajax_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager, CommentRepository $commentRepository, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->getPayload()->getString('_token'))) {
            // Supprimer l'entité
            $entityManager->remove($comment);
            $entityManager->flush();
            // Récupération du trick
            $trick = $comment->getTrick();
            // Récupération des commentaires correspondant au trick
            $comments = $commentRepository->findBy(['trick' => $trick->getId()], array('id' => 'DESC'));
            $users = $userRepository->findAll();
            $arr_users = [];
            foreach ($comments as $com) {
                foreach ($users as $user) {
                    if ($user->getId() == $com->getUser()->getId()) {
                        $arr_users[$user->getId()] = $user;
                    }
                }
            }
            // Créer le formulaire pour le commentaire
            $form_comment = $this->createForm(CommentType::class, $comment, [
                "trick" => $trick,
            ]);

            $form_comment->handleRequest($request);

            $comments_html = $this->render('trick/comments.html.twig', [
                'trick' => $trick,
                'modify' => FALSE,
                'comments' => $comments,
                "users" => $arr_users,
                'form_comment' => $form_comment->createView(),
            ]);

            // Retourner une réponse JSON après la mise à jour
            return new JsonResponse(['comments_html' => $comments_html->getContent(), 'is_comment' => TRUE]);
        }

        $delete_confirm_html = $this->render('trick/_confirm-partial-delete-comment.html.twig', [
            "comment" => $comment,
        ]);

        return new JsonResponse(['confirm_delete' => $delete_confirm_html->getContent()]);
    }
}
