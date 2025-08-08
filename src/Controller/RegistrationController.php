<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use App\Repository\UserRepository;
class RegistrationController extends AbstractController
{
   public function __construct(private EmailVerifier $emailVerifier) {}

   #[Route('/register', name: 'app_register')]
   public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
   {
      $user = new User();
      $form = $this->createForm(RegistrationFormType::class, $user);
      $form->handleRequest($request);
      
      if ($form->isSubmitted() && $form->isValid()) {
         /** @var string $plainPassword */
         $plainPassword = $form->get('plainPassword')->getData();
         
         // encode the plain password
         $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
         $entityManager->persist($user);
         $entityManager->flush();

         try {
            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
               'app_verify_email',
               $user,
               (new TemplatedEmail())
                  ->from(new Address('tom@studio509.fr', 'TomCorp'))
                  ->to((string) $user->getEmail())
                  ->subject('Confirmer votre email')
                  ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            
            $this->addFlash('success', 'Inscription réussie ! Vérifiez votre email pour confirmer votre compte.');
         } catch (\Exception $e) {
            $this->addFlash('warning', 'Inscription réussie, mais l\'email de confirmation n\'a pas pu être envoyé. Vous pouvez vous connecter directement.');
         }

         return $this->redirectToRoute('app_default');
      }
      
      // Gérer les erreurs de validation uniquement si le formulaire a été soumis
      if ($form->isSubmitted() && !$form->isValid()) {
         if ($form->get('email')->getErrors()->count() > 0) {
            $this->addFlash('error', "Erreur avec l'email : " . $form->get('email')->getErrors()[0]->getMessage());
         } else {
            $this->addFlash('error', "Veuillez corriger les erreurs dans le formulaire.");
         }
      }
      
      return $this->render('registration/register.html.twig', [
         'registrationForm' => $form,
      ]);
   }

   #[Route('/verify/email', name: 'app_verify_email')]
   public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
   {
      $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

      // validate email confirmation link, sets User::isVerified=true and persists
      try {
         /** @var User $user */
         $user = $this->getUser();
         $this->emailVerifier->handleEmailConfirmation($request, $user);
      } catch (VerifyEmailExceptionInterface $exception) {
         $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

         return $this->redirectToRoute('app_register');
      }

      // @TODO Change the redirect on success and handle or remove the flash message in your templates
      $this->addFlash('success', 'Your email address has been verified.');

      return $this->redirectToRoute('app_register');
   }
}
