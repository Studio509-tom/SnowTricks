<?php

namespace App\Ajax;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/ajax/profile')]
class PersonalAjax extends AbstractController
{
    function __construct(RequestStack $requestStack)
    {
        if (!$requestStack->getCurrentRequest()->isXmlHttpRequest()) {
            throw new \TypeError("Accès refuser");
        }
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/avatar', name: 'app_avatar_ajax', methods: ['POST'])]
    public function changeAvatar(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): JsonResponse
    {

        $filesystem = new Filesystem();

        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();
        $profilePictureFile = $request->files->get('file');
        if ($profilePictureFile) {
            // Récupère le nom de l'image récupéré
            $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            // Crée le nom unique de l'image avec son extension
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();
            $path = 'C:\Users\Tom\Documents\Sites_internet\SnowTricks\Site\assets\files\users\\' . $userId;
            $folder_exist = is_dir($path);
            // Vérifier si le dossier existe
            if (!$folder_exist) {
                mkdir($path);
            } else {
                // Récupérer tous les fichiers dans le dossier
                $files = scandir($path);
                foreach ($files as $file) {
                    // Ignorer les répertoires . et ..
                    if ($file !== '.' && $file !== '..') {
                        $filesystem->remove($path . '/' . $file);
                    }
                }
            }

            $profilePictureFile->move(
                $path,
                $newFilename
            );
            // Enregistrer l'image en BDD
            $user->setAvatarNameFile($newFilename);
            $entityManager->persist($user);
            $entityManager->flush();

            $html_avatar = $this->render('personal/avatar-partial.html.twig', [
                "avatar_file" => $newFilename,
            ]);

            return new JsonResponse(['html_avatar' => $html_avatar->getContent(), 'error' => false]);
        }

        return new JsonResponse(['error' => true]);
    }
}
