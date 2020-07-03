<?php

namespace App\Controller;

use App\Repository\Provider\RepositoryProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(RepositoryProviderInterface $repositoryProvider)
    {
        $repositories = $repositoryProvider->getAllRepositories();

        return $this->render('default/homepage.html.twig', [
            'repositories' => $repositories,
        ]);
    }
}
