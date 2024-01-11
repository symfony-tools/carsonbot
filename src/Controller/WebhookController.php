<?php

namespace App\Controller;

use App\Service\GitHubRequestHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    #[Route('/webhooks/github', name: 'webhooks_github', methods: ['POST'])]
    public function github(Request $request, GitHubRequestHandler $requestHandler)
    {
        $responseData = $requestHandler->handle($request);

        return new JsonResponse($responseData);
    }
}
