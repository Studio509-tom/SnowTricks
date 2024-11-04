<?php

namespace App\Ajax;

use App\Entity\Trick;
use App\Form\TrickType;
use App\Repository\CommentRepository;
use App\Repository\TrickRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\FormErrorIterator;

#[Route('/ajax/show-more')]
final class ShowMoreAjax extends AbstractController
{

    function __construct(RequestStack $requestStack)
    {

        if (!$requestStack->getCurrentRequest()->isXmlHttpRequest()) {
            throw new \TypeError("Accès refuser");
        }
    }
    #[Route('/default', name: 'app_ajax_default_show_more', methods: ['POST'])]
    public function showMore(Request $request, TrickRepository $trickRepository): JsonResponse
    {
        // Récupération du POST
        $number_length = (int) $request->request->get('trick_count');
        // Vérification du nombre d'élément afficher 
        if ($number_length >= 6) {
            $number_length = $number_length + 6;
            $tricks = $trickRepository->findBy(
                [],
                null,
                $number_length
            );
        } else {
            $tricks = $trickRepository->findBy(
                [],
                null,
                 $number_length
            );
        }
        // Retour JSON
        $html_tricks = $this->render('default/tricks-show-more.html.twig', [
            "tricks" => $tricks,
            "number_tricks" => $number_length
        ]);

        return new JsonResponse(['html_tricks' => $html_tricks->getContent()]);
    }

    #[Route('/comment', name: 'app_ajax_comment_show_more', methods: ['POST'])]
    public function showMoreComments(Request $request,UserRepository $userRepository, CommentRepository $commentRepository, TrickRepository $trickRepository): Response
    {
        // Récupération du POST
        $number_length = (int) $request->request->get('comment_count');
        $id_trick = $request->request->get('id_trick');
        $trick = new Trick();
        $trick = $trickRepository->findById($id_trick);

        if  ($number_length >= 6) {
            $number_length = $number_length + 6;
            $comments = $commentRepository->findBy(
                ['trick' => $trick],
                null,
                $number_length
            );
        } else {
            $comments = $commentRepository->findBy(
                ['trick' => $trick],
                null,
                $number_length
            );
        }
        $users = $userRepository->findAll();
        $arr_users = [];
        foreach ($comments as $com) {
            foreach ($users as $user) {
                if ($user->getId() == $com->getUser()->getId()) {
                    $arr_users[$user->getId()] = $user;
                }
            }
        }
        $html_comments = $this->render('trick/comments-partial.html.twig', [
            "comments" => $comments,
            "users" => $arr_users,
            "modify" => FALSE,
            "number_comments" => (int) $number_length
        ]);

        return new JsonResponse(['html_comments' => $html_comments->getContent()]);
    }
}
