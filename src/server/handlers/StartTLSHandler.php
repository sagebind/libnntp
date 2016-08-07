<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class StartTLSHandler implements Handler
{
    const CRYPTO_METHOD = STREAM_CRYPTO_METHOD_ANY_SERVER;

    public function handle(Command $command, ClientContext $context): Generator
    {
        yield from $context->writeResponse(new Response(382, 'Continue with TLS negotiation'));

        yield from $context->getSocket()->enableCrypto(self::CRYPTO_METHOD);
    }
}
