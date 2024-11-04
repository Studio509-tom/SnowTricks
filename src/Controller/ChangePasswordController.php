<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ChangePasswordType;
use App\Form\ForgotPasswordType;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use App\Form\ResetPasswordType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ChangePasswordController extends AbstractController
{
    #[Route('/nouveau-mots-de-passe', name: 'app_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Créer un formulaire pour changer le mot de passe
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            // Vérifier si l'ancien mot de passe est correct
            if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {

                $this->addFlash('error', 'L\'ancien mot de passe est incorrect.');
                return $this->redirectToRoute('app_change_password');
            }

            // Hasher et mettre à jour le nouveau mot de passe
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

            // Sauvegarder les modifications dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe modifié avec succès.');

            // redirection vers le profil utilisateur
            return $this->redirectToRoute('app_personal_index');
        }

        return $this->render('change_password/index.html.twig', [
            'changePasswordForm' => $form->createView(),
        ]);
    }

    #[Route('/oublie-mots-de-passe', 'app_forgot_password')]
    public function forgotPassword(Request $request, UserRepository $userRepository, TokenGeneratorInterface $tokenGenerator, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        // Création du formulaire pour saisir l'adresse e-mail

        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);
        // Vérifie si le formulaire a été soumis et est valide

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupère l'email saisi dans le formulaire
            $email = $form->get('email')->getData();
            // Recherche l'utilisateur associé à cet email
            $user = $userRepository->findOneBy(['email' => $email]);
            // Si aucun utilisateur n'est trouvé, affiche un message d'erreur et redirige vers la même page
            if (!$user) {
                $this->addFlash('danger', 'Vérifier votre boite mail !');
                return $this->redirectToRoute('app_forgot_password');
            }
            // Génère un token de réinitialisation de mot de passe
            $token = $tokenGenerator->generateToken();
            // Attribue le token à l'utilisateur et sauvegarde en base de données
            $user->setResetToken($token);
            $entityManager->flush();
            // Génère une URL vers la page de réinitialisation du mot de passe avec le token
            $url = $this->generateUrl('app_reset_password', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);
            // Prépare l'email pour la réinitialisation du mot de passe
            $email_message = (new Email())
                ->from(new Address('tom@studio509.fr', 'TomCorp'))
                ->to((string) $user->getEmail())
                ->subject("Confirmation de l'adresse email")
                ->html('<p>Pour réinitialiser votre mot de passe, veuillez cliquer sur le lien suivant : <a href="' . $url .
                    '">Réinitialiser mon mot de passe</a></p>');
            // Envoyer l'email
            $mailer->send($email_message);
        }

        return  $this->render('security/take-email.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/reinitialise-mots-de-passe/{token}', 'app_reset_password')]

    public function resetPassword(Request $request, UserPasswordHasherInterface $passwordHasher, string $token, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \App\Entity\User $user */
            // Vérifier si le token correspond
            $user = $userRepository->findOneBy(['resetToken' => $token]);

            if ($user === NULL) {
                $this->addFlash('danger', 'Mot de passe invalide');
                return $this->render('security/reset-password.html.twig', ['token' => $token, "form" => $form]);
            }
            $password = $form->get('password')->getData();
            $confirm_password = $form->get('confirmPassword')->getData();
            // Vérifier si les deux mots de passe rentré sont identique
            if (strcmp($password, $confirm_password) === 0) {
                $user->setResetToken(null);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe modifié avec succès.');

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('danger', 'Les mots de passe doivent être identique !');

            return $this->render('security/reset-password.html.twig', ['token' => $token, "form" => $form]);
        } else {

            return $this->render('security/reset-password.html.twig', ['token' => $token, "form" => $form]);
        }
    }
}
