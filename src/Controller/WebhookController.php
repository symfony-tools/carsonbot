<?php

namespace App\Controller;

use App\Service\GitHubRequestHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class WebhookController extends AbstractController
{
    #[Route('/webhooks/github', name: 'webhooks_github', methods: ['POST'])]
    public function github(Request $request, GitHubRequestHandler $requestHandler): JsonResponse
    {
        $responseData = $requestHandler->handle($request);

        return new JsonResponse($responseData);
    }
}
