<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AuthenticationController
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
        $responseBody = $this->streamFactory->createStream('AuthenticationController::index called');
        $response = $this->responseFactory->createResponse(200)->withBody($responseBody);
        return $response;
    }

    public function signIn(ServerRequestInterface $request): ResponseInterface
    {
        $this->requestReceived = $request;
        $responseBody = $this->streamFactory->createStream('AuthenticationController::signIn called');
        $response = $this->responseFactory->createResponse(200)->withBody($responseBody);
        return $response;
    }
}
