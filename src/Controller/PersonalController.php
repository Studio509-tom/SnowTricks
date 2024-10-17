<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;

class PersonalController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/mon-compte', name: 'app_personal_index')]
    public function index(UserRepository $userRepository): Response
    {
        $user = $userRepository->find($this->getUser());
        $avatar_name = $user->getAvatarNameFile();

        return $this->render('personal/index.html.twig', [
            'avatar_file' => $avatar_name,
        ]);
    }

    
}
