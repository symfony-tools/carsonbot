<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function homepageAction()
    {
        $repositories = $this->get('app.repository_provider')->getAllRepositories();

        return $this->render('default/homepage.html.twig', [
            'repositories' => $repositories,
        ]);
    }
}
