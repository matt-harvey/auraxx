<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HomeController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    )
    {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $responseBody = $this->streamFactory->createStream('HomeController::index called');
        $response = $this->responseFactory->createResponse(200)->withBody($responseBody);
        return $response;
    }
}
