<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Controller\Admin;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class DashboardController
{
    public ServerRequestInterface $requestReceived;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    )
    {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $this->requestReceived = $request;
        $responseBody = $this->streamFactory->createStream('Admin DashboardController::index called');
        $response = $this->responseFactory->createResponse(200)->withBody($responseBody);
        return $response;
    }

    public function inviteUser(ServerRequestInterface $request): ResponseInterface
    {
        $this->requestReceived = $request;
        $responseBody = $this->streamFactory->createStream('Admin DashboardController::inviteUser called');
        $response = $this->responseFactory->createResponse(200)->withBody($responseBody);
        return $response;
    }
}
