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
use Symfony\Component\Routing\RouterInterface;

#[Route('/ajax/trick')]
final class TrickAjax extends AbstractController
{
   private $filesDirectory, $imageLocation;
   function __construct(RequestStack $requestStack, $filesDirectory)
   {
      $this->imageLocation = $filesDirectory;
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
      $hash_first_file = null;
      $form->handleRequest($request);
      if ($form->isSubmitted()) {
         if ($form->isValid()) {
            // Vérification que le nom n'est pas déjà enregistrer
            $title_trick = $trick->getTitle();
            $slug = $this->slugify($title_trick);
            $trick->setSlug($slug);
            $occurence_title = $trickRepository->findBy(['slug' => $slug]);
            // Si l'occurence est trouver 
            if (!empty($occurence_title)) {
               $this->addFlash('error', 'Le titre de cette article est déjà utilisé.');
               $create_html = $this->render('trick/new.html.twig', [
                  'trick' => $trick,
                  'form' => $form,
                  'first_file_defined' => null,
                  'error' => true
               ]);

               return new JsonResponse(['edit_html' => $create_html->getContent(), 'error' => true]);
            }
            $primaryImage = $request->files->get('primary_image');
            if ($primaryImage) {
               // pour traiter l'image principale 
               $newFilename = uniqid() . '.' . $primaryImage->guessExtension();
               $imageContent = file_get_contents($primaryImage->getPathname());
               // Hashage pour supprimer le doublon lors de l'enregistrement plus bas
               $hash_first_file = hash('sha256', $imageContent);
            }
            // Définition de la date de création
            date_default_timezone_set('Europe/Paris');
            $date_creation = new \DateTime(date('Y-m-d H:i:s'));
            $trick->setDateCreate($date_creation);
            // Récupération des liens du formulaires
            if (isset($request->get('trick')['links'])) {
               $links = $request->get('trick')['links'];
            } else {
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

                  $imageContent = file_get_contents($image->getPathname());
                  $hash_file = hash('sha256', $imageContent);
                  // Vérifier si l'image défini comme mise en avant et l'image traiter ne sont pas identique            
                  if ($hash_file != $hash_first_file) {
                     $files_entity[] = $newFilename;
                     $trick->setFiles($files_entity);
                  } else {
                     $trick->setFirstFile([$newFilename]);
                  }
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
            $path = $this->imageLocation . 'tricks\\' . $trick->getId();
            // Création du dossier du trick
            mkdir($path);

            // Traitement si il n'y a pas eu d'image mis en avant
            if (!$image_empty) {
               for ($i = 0; $i < count($files_entity); $i++) {
                  $file =  $files_entity[$i];
                  $images[$i]->move($path, $file);
               }
            }
            if ($primaryImage) {
               $first_file = $trick->getFirstFile();

               $primaryImage->move(
                  $path,
                  $first_file[0]
               );
            }
            $this->addFlash('success', 'Article correctement crée');
            // Récupération des tricks
            $tricks = $trickRepository->findAll();
            // Retour JSON
            $tricks_html = $this->render('trick/tricks-partial.html.twig', [
               "tricks" => $tricks,
               'date_create' => $date_creation

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
         'first_file_defined' => null,
      ]);

      return new JsonResponse(['create_html' => $create_html->getContent()]);
   }


   #[IsGranted('ROLE_USER')]
   #[Route('/{id}/edit', name: 'app_ajax_trick_edit', methods: ['GET', 'POST'])]
   public function edit(Request $request, Trick $trick, EntityManagerInterface $entityManager, TrickRepository $trickRepository, SluggerInterface $slugger, RouterInterface $router): Response
   {
      $form = $this->createForm(TrickType::class, $trick);
      $form->handleRequest($request);
      $link_referer = $request->headers->get('referer');
      // Récupération du paramètre d'URl
      $refererPathInfo = Request::create($link_referer)->getPathInfo();
      $refererPathInfo = str_replace($request->getScriptName(), '', $refererPathInfo);
      $filesystem = new Filesystem();
      $first_file_arr = null;
      $existingFiles = $trick->getFiles();

      if ($form->isSubmitted() && $form->isValid()) {
         // Vérification que le nom n'est pas déjà enregistrer
         // ***************************************************************
         // ***************         GOOD        ***************************
         // ***************************************************************
         $title_trick = $trick->getTitle();

         $occurence_title = $trickRepository->findByTitle($title_trick);

         $enter = false;
         foreach ($occurence_title as $occurence) {
            if ($trick->getId() == $occurence->getId()) {
               $enter = true;
            }
         }

         // Si l'occurence trouvé n'est pas vide et que ils n'ont pas le même id
         if (!empty($occurence_title) && !$enter) {
            $this->addFlash('error', 'Le titre de cette article est déjà utilisé.');
            $first_file = $trick->getFirstFile();

            $edit_html = $this->render('trick/edit-ajax.html.twig', [
               'trick' => $trick,
               'form' => $form,
               'first_file_defined' => is_null($first_file) || empty($first_file) ? null : true,
               'first_file' => is_null($first_file) || empty($first_file) ? null : $first_file[0],
            ]);

            return new JsonResponse(['edit_html' => $edit_html->getContent(), 'error' => true]);
         }

         $path = $this->imageLocation . 'tricks\\' . $trick->getId();
         // n'est pas null quand l'image viens d'être rentré et est coché comme mise en Avant
         $primaryImage = $request->files->get('primary_image');
         // Si l'images n'est pas envoyer sous format images mais text (qu'elle est déjà enregistré)
         
         if (is_null($primaryImage)) {
            $primaryImage = $request->request->get('primary_image');
            // Si aucune image est défini comme mise en Avant 
            if (!is_null($primaryImage)) {
               $hash_first_file = hash('sha256', $primaryImage);
               // Si l'image est retrouvé dans le dossier
               if ($filesystem->exists($path . '\\' . $primaryImage)) {
                  $first_file_arr = $trick->getFirstFile();
                  if (!empty($first_file_arr)) {
                     // Si ce n'est pas l'image déjà mis en avant
                     if ($primaryImage !== $first_file_arr[0]) {
                        unset($existingFiles[array_search($primaryImage, $existingFiles)]);
                        $existingFiles[] = $first_file_arr[0];
                        
                        $trick->setFirstFile([$primaryImage]);
                     }
                  } else if (empty($first_file_arr)) {
                    
                     $trick->setFirstFile([$primaryImage]);
                  }
               }
            } else {
               $trick->setFirstFile(NULL);
            }
         } else {
           
            // pour traiter l'image principale 
            $originalFilename = pathinfo($primaryImage->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $primaryImage->guessExtension();
            $imageContent = file_get_contents($primaryImage->getPathname());
            // Hashage pour supprimer le doublon lors de l'enregistrement plus bas
            $hash_first_file = hash('sha256', $imageContent);

         }
         // Récupération des images envoyées via le formulaire 
         $images = $form->get('files')->getData();
         // ***************************************************************
         // ***************************************************************
         // ***************************************************************

         // Récupération des fichiers existants dans l'entité Trick
         // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
         // !!!!!!!!!!!!!!!!!!!!!!!!!!   A FAIRE PLUS TARD     !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
         // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
         // Récupération des liens du formulaires
         if (isset($request->get('trick')['links'])) {
            $links = $request->get('trick')['links'];
         } else {
            $links = [];
         }
         $existingLinks = $trick->getLinks();

         // Récupération des fichiers que l'utilisateur souhaite supprimer
         $deletedFiles = json_decode($request->get('deleted_files', [])); // Tableau des fichiers à supprimer

         // Dossier de stockage
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
         date_default_timezone_set('Europe/Paris');
         $date_modify = new \DateTime(date('Y-m-d H:i:s'));
         $trick->setDateModify($date_modify);
         // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
         // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
         // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

         $files_entity = [];

         // Si ajouts de nouvelles images
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
               $imageContent = file_get_contents($image->getPathname());
               $hash_file = hash('sha256', $imageContent);

               if (!in_array($newFilename, $existingFiles) && !in_array($newFilename, $existingFilesInFolder)) {
                  // Vérifier si l'image défini comme mise en avant et l'image traiter ne sont pas identique 
                  if (isset($hash_first_file)) {
                     if ($hash_file != $hash_first_file) {
                        $files_entity[] = $newFilename;
                        $trick->setFiles($files_entity);
                     } else {
                        $new_first_file = [$newFilename];
                        $trick->setFirstFile($new_first_file);
                     }
                  } else {
                     $files_entity[] = $newFilename;
                     $trick->setFiles($files_entity);
                  }
                  // Stockage de l'image
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
         if (!is_null($existingFiles)) {
            foreach ($existingFiles as $key => $value) {
               $files_entity[] = $value;
            }
         }
         // Mise à jour de l'entité Trick avec les fichiers actualisés
         $trick->setFiles($files_entity);
         $title_trick = $trick->getTitle();
         $slug = $this->slugify($title_trick);
         $trick->setSlug($slug);
         $entityManager->persist($trick);
         $entityManager->flush();
         $first_file = $trick->getFirstFile();
         // Si on est sur la page des tricks
         if ($refererPathInfo == "/trick") {
            $tricks = $trickRepository->findAll();
            $tricks_html = $this->render('trick/tricks-partial.html.twig', [
               "tricks" => $tricks,
               'date_modify' => $date_modify,
               'first_file_defined' => is_null($first_file) || empty($first_file) ? null : true,
               'first_file' => is_null($first_file) || empty($first_file) ? null : $first_file[0],
            ]);
            return new JsonResponse(['tricks_html' => $tricks_html->getContent(), 'page' => 'tricks']);
            // Si on est sur la page d'accueil
         } else if ($refererPathInfo == "/") {
            $tricks = $trickRepository->findAll();
            $tricks_html = $this->render('default/tricks-show-more.html.twig', [
               "tricks" => $tricks,
               'date_modify' => $date_modify,
               'first_file_defined' => is_null($first_file) || empty($first_file) ? null : true,
               'first_file' => is_null($first_file) || empty($first_file) ? null : $first_file[0],
               "number_tricks" => 6,
            ]);
            return new JsonResponse(['tricks_html' => $tricks_html->getContent(), 'page' => 'home']);
         }
         // Si on est sur la page d'un trick
         else {
            $trick_html = $this->render('trick/show-partial.html.twig', [
               "trick" => $trick,
               'date_modify' => $date_modify,
               'first_file_defined' => is_null($first_file) || empty($first_file) ? null : true,
               'first_file' => is_null($first_file) || empty($first_file) ? null : $first_file[0],
            ]);
            return new JsonResponse(['tricks_html' => $trick_html->getContent(), 'page' => 'trick']);
         }
      }
      $first_file = $trick->getFirstFile();

      $edit_html = $this->render('trick/edit-ajax.html.twig', [
         'trick' => $trick,
         'form' => $form,
         'first_file_defined' => is_null($first_file) || empty($first_file) ? null : true,
         'first_file' => is_null($first_file) || empty($first_file) ? null : $first_file[0],
      ]);

      return new JsonResponse(['edit_html' => $edit_html->getContent()]);
   }

   #[IsGranted('IS_AUTHENTICATED')]
   #[Route('/{id}', name: 'app_ajax_trick_delete', methods: ['POST'])]
   public function delete(Request $request, Trick $trick, EntityManagerInterface $entityManager, TrickRepository $trickRepository): Response
   {

      if ($this->isCsrfTokenValid('delete' . $trick->getId(), $request->getPayload()->getString('_token'))) {
         $slug = $trick->getSlug();
         $entityManager->remove($trick);
         $entityManager->flush();
         $tricks = $trickRepository->findAll();
         $link_referer = $request->headers->get('referer');
         $refererPathInfo = Request::create($link_referer)->getPathInfo();
         $refererPathInfo = str_replace($request->getScriptName(), '', $refererPathInfo);

         if ($refererPathInfo == ('/trick/' . $slug)) {

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

   function slugify($text)
   {
      // Remplacer les accents par leur équivalent ASCII
      $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
      // Remplacer les espaces par des tirets
      $text = str_replace(' ', '-', $text);
      // Supprimer les caractères non-alphabétiques et non-numériques sauf les tirets
      $text = preg_replace('/[^a-zA-Z0-9\-]/', '', $text);
      // Remplacer plusieurs tirets consécutifs par un seul
      $text = preg_replace('/-+/', '-', $text);
      // Supprimer les tirets en début et fin de chaîne
      $text = trim($text, '-');
      // Passer en minuscules
      $text = strtolower($text);
      return $text;
   }
}
