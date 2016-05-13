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
        return $this->render('default/homepage.html.twig', [
            'needsReviewUrl' => $this->get('app.status_api.default')->getNeedsReviewUrl(),
        ]);
    }
}
