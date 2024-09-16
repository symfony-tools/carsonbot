<?php

namespace App\Controller;

use App\Service\RepositoryProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/')]
    public function index(RepositoryProvider $repositoryProvider): Response
    {
        $repositories = $repositoryProvider->getAllRepositories();

        return $this->render('default/homepage.html.twig', [
            'repositories' => $repositories,
        ]);
    }
}
