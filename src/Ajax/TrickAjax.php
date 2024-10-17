<?php

   namespace App\Ajax;

   use App\Entity\Trick;
   use App\Form\TrickType;
   use App\Repository\TrickRepository;
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

   #[Route('/ajax/trick')]
   final class TrickAjax extends AbstractController
   {

      function __construct(RequestStack $requestStack)
      {

         if (!$requestStack->getCurrentRequest()->isXmlHttpRequest()) {
            throw new \TypeError("Accès refuser");
         }
      }

   #[IsGranted('IS_AUTHENTICATED')]
   #[Route('/new', name: 'app_ajax_trick_new', methods: ['GET', 'POST'])]
   public function new(Request $request, EntityManagerInterface $entityManager, TrickRepository $trickRepository, SluggerInterface $slugger): Response
   {
      $this->denyAccessUnlessGranted('ROLE_USER');

      $trick = new Trick();
      $trick->setUser($this->getUser());

      $form = $this->createForm(TrickType::class, $trick);

      $form->handleRequest($request);
      if ($form->isSubmitted()) {
         if ($form->isValid()) {
            
            // Récupération des liens du formulaires
            if(isset($request->get('trick')['links'])){
               $links = $request->get('trick')['links'];
            }
            else{
               $links = [];
            }
            $setLink = [];
            // Gestion des images
            $images = $form->get('files')->getData();

            $image_empty = TRUE;
            $files_entity = [];
            if ($images) {
               $image_empty = FALSE;
               foreach ($images as $image) {
                  $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                  $safeFilename = $slugger->slug($originalFilename);
                  $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                  $files_entity[] = $newFilename;
                  $trick->setFiles($files_entity);
               }
            }

            // Boucler sur les lien récupéré du formulaire
            foreach ($links as $link) {
               // Si pas vide
               if (!empty($link)) {
                  // Si le lien n'existe pas 
                  if ((array_search($link, $setLink) === false)) {
                     // Stocker dans le tableau de l'entité
                     $setLink[] = $link;
                  }
               }
            }

            $trick->setLinks($setLink);
            
            $entityManager->persist($trick);
            $entityManager->flush();
            if (!$image_empty) {
               $path = 'C:\Users\Tom\Documents\Sites_internet\SnowTricks\Site\SnowTricks\assets\files\tricks\\' . $trick->getId();
               for ($i = 0; $i < count($images); $i++) {
                  $file =  $files_entity[$i];
                  $images[$i]->move($path, $file);
               }
            }
            // Récupération des tricks
            $tricks = $trickRepository->findAll();
            $tricks_html = $this->render('trick/tricks-partial.html.twig', [
               "tricks" => $tricks
            ]);

            return new JsonResponse(['tricks_html' => $tricks_html->getContent(), "page" => "tricks"]);
         } else {
            // Récupération des erreurs en cas de validation échouée
            $errors = [];
            foreach ($form->getErrors(true, false) as $error) {
               $errors[] = $error->getMessage();
            }

            return new JsonResponse(['status' => 'error', 'errors' => $errors], 400);
         }
      }

      $create_html = $this->render('trick/new.html.twig', [
         'trick' => $trick,
         'form' => $form,
      ]);

      return new JsonResponse(['create_html' => $create_html->getContent()]);
   }
   #[IsGranted('IS_AUTHENTICATED')]
   #[Route('/{id}/edit', name: 'app_ajax_trick_edit', methods: ['GET', 'POST'])]
   public function edit(Request $request, Trick $trick, EntityManagerInterface $entityManager, TrickRepository $trickRepository, SluggerInterface $slugger): Response
   {
      $form = $this->createForm(TrickType::class, $trick);
      $form->handleRequest($request);
      $link_referer = $request->headers->get('referer');
      $filesystem = new Filesystem();

      if ($form->isSubmitted() && $form->isValid()) {
         // Récupération des images envoyées via le formulaire (nouvelles images)
         $images = $form->get('files')->getData();
         // Récupération des fichiers existants dans l'entité Trick
         $existingFiles = $trick->getFiles();
         // Récupération des liens du formulaires
         if(isset($request->get('trick')['links'])){
            $links = $request->get('trick')['links'];
         }
         else{
            $links = [];
         }

         $existingLinks = $trick->getLinks();
         // Récupération des fichiers que l'utilisateur souhaite supprimer
         $deletedFiles = json_decode($request->get('deleted_files', [])); // Tableau des fichiers à supprimer

         // Dossier de stockage
         $path = 'C:\Users\Tom\Documents\Sites_internet\SnowTricks\Site\SnowTricks\assets\files\tricks\\' . $trick->getId();

         // Gestion des suppressions
         if ($deletedFiles) {
            foreach ($deletedFiles as $fileToDelete) {
               // Supprimer le fichier du tableau de fichiers existants
               if (($key = array_search($fileToDelete, $existingFiles)) !== false) {
                  // Retirer le fichier de l'entité
                  unset($existingFiles[$key]);

                  // Supprimer le fichier
                  $fullFilePath = $path . '\\' . $fileToDelete;
                  if ($filesystem->exists($fullFilePath)) {
                     $filesystem->remove($fullFilePath);
                  }
               }
            }
         }

         // Gestion des ajouts de nouvelles images
         if ($images) {
            // Vérifier si le dossier est vide ou innexistant
            if ((count(glob("$path/*")) === 0)) {
               rmdir($path);
               mkdir($path);
               $existingFilesInFolder = scandir($path);
            } else {
               // Scanner les fichiers déjà présents dans le répertoire
               $existingFilesInFolder = scandir($path);
            }

            foreach ($images as $image) {
               // Traitement du nom de l'image
               $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
               $safeFilename = $slugger->slug($originalFilename);
               $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

               // Vérifier si le fichier n'existe pas déjà en base de données et dans le dossier
               if (!in_array($newFilename, $existingFiles) && !in_array($newFilename, $existingFilesInFolder)) {
                  // Ajouter le fichier à l'entité
                  $existingFiles[] = $newFilename;
                  // Déplacer l'image dans le dossier de stockage
                  $image->move($path, $newFilename);
               }
            }
         }
         // Boucler sur les lien récupéré du formulaire
         foreach ($links as $link) {
            // Si pas vide
            if (!empty($link)) {
               // Si le lien n'existe pas 
               if ((array_search($link, $existingLinks) === false)) {
                  // Stocker dans le tableau de l'entité
                  $existingLinks[] = $link;
               }
            }
         }
         // Boucler sur les liens de l'entité
         foreach ($existingLinks as $key => $existingLink) {
            // Retirer les valeurs null
            if (is_null($existingLink)) {
               unset($existingLinks[$key]);
            }
         }
         // Enregistrement des liens
         $trick->setLinks(array_values($existingLinks));
         if (is_null($existingFiles)) {
            $existingFiles = [];
         }
         // Mise à jour de l'entité Trick avec les fichiers actualisés
         $trick->setFiles(array_values($existingFiles));

         $entityManager->flush();

         if ($link_referer == "https://127.0.0.1:8000/trick") {
            $tricks = $trickRepository->findAll();
            $tricks_html = $this->render('trick/tricks-partial.html.twig', [
               "tricks" => $tricks
            ]);
            return new JsonResponse(['tricks_html' => $tricks_html->getContent(), 'page' => 'tricks']);
         } else {
            $trick_html = $this->render('trick/show-partial.html.twig', [
               "trick" => $trick
            ]);
            return new JsonResponse(['tricks_html' => $trick_html->getContent(), 'page' => 'trick']);
         }
      }
      $edit_html = $this->render('trick/edit-ajax.html.twig', [
         'trick' => $trick,
         'form' => $form,
      ]);

      return new JsonResponse(['edit_html' => $edit_html->getContent()]);
   }

   #[IsGranted('IS_AUTHENTICATED')]
   #[Route('/{id}', name: 'app_ajax_trick_delete', methods: ['POST'])]
   public function delete(Request $request, Trick $trick, EntityManagerInterface $entityManager, TrickRepository $trickRepository): Response
   {

      if ($this->isCsrfTokenValid('delete' . $trick->getId(), $request->getPayload()->getString('_token'))) {
         $id_trick = $trick->getId();
         $entityManager->remove($trick);
         $entityManager->flush();
         $tricks = $trickRepository->findAll();
         $link_referer = $request->headers->get('referer');
         if ($link_referer == ("https://127.0.0.1:8000/trick/" . strval($id_trick))) {
            return new JsonResponse(['url_redirect' => $this->generateUrl('app_trick_index'), "redirection" => TRUE]);
         }
         $tricks_html = $this->render('trick/tricks-partial.html.twig', [
            "tricks" => $tricks,

         ]);
         return new JsonResponse(['tricks_html' => $tricks_html->getContent()]);
      }

      $delete_confirm_html = $this->render('trick/_confirm-partial-delete.html.twig', [
         "trick" => $trick
      ]);

      return new JsonResponse(['confirm_delete' => $delete_confirm_html->getContent()]);
   }

  
}
