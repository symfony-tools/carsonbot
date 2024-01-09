<?php

namespace App\Controller;

use App\Service\RepositoryProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/')]
    public function index(RepositoryProvider $repositoryProvider)
    {
        $repositories = $repositoryProvider->getAllRepositories();

        return $this->render('default/homepage.html.twig', [
            'repositories' => $repositories,
        ]);
    }
}
