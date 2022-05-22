<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ErrorController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    )
    {
    }


    public function notFound(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $responseBody = $this->streamFactory->createStream('Not Found');
        $response = $this->responseFactory->createResponse(404)->withBody($responseBody);
        return $response;
    }
}
