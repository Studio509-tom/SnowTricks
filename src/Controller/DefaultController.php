<?php

namespace App\Controller;
use App\Repository\TrickRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(TrickRepository $trickRepository): Response
    {
        $tricks = $trickRepository->findAll();
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'tricks' => $tricks,
        ]);
    }
}
