<?php

namespace Gibbon;

// class Application is responsible for

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Dice\Dice;

use function Amp\call;

class GibbonHandler implements RequestHandler
{
    protected $configuration;
    protected $matchedRules;

    public function __construct(private Gibbon $gibbon)
    {
    }

    public function handleRequest(Request $request): Promise
    {
        return call(function () use ($request) {
            $data = yield $this->gibbon->handle($request->getUri());

            return new Response(Status::OK, [], $data);
        });
    }
}
