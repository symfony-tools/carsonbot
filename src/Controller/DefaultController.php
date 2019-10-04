<?php

namespace App\Controller;

use App\Repository\Provider\RepositoryProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function homepage(RepositoryProviderInterface $repositoryProvider)
    {
        return $this->render('default/homepage.html.twig', [
            'repositories' => $repositoryProvider->getAllRepositories(),
        ]);
    }
}