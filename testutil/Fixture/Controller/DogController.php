<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class DogController
{
    public ServerRequestInterface $requestReceived;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    )
    {
    }

    public function show(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $this->requestReceived = $request;
        $responseBody = $this->streamFactory->createStream("DogController::show called with id $id");
        $response = $this->responseFactory->createResponse(200)->withBody($responseBody);
        return $response;
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $this->requestReceived = $request;
        $responseBody = $this->streamFactory->createStream('DogController::create called');
        $response = $this->responseFactory->createResponse(200)->withBody($responseBody);
        return $response;
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $this->requestReceived = $request;
        $responseBody = $this->streamFactory->createStream('DogController::store called');
        $response = $this->responseFactory->createResponse(201)->withBody($responseBody);
        return $response;
    }
}
