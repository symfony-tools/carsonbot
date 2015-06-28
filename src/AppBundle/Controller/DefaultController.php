<?php

namespace AppBundle\Controller;

use AppBundle\GitHub\StatusManager;
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
            'needsReviewUrl' => sprintf(
                'https://github.com/%s/%s/labels/%s',
                $this->container->getParameter('repository_username'),
                $this->container->getParameter('repository_name'),
                urlencode(StatusManager::getLabelForStatus(StatusManager::STATUS_NEEDS_REVIEW))
            )
        ]);
    }
}
