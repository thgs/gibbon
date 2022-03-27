#!/usr/bin/env php
<?php

require "vendor/autoload.php";

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Dice\Dice;
use Gibbon\Gibbon;
use Gibbon\GibbonHandler;
use Monolog\Logger;

Amp\Loop::run(static function () {
    $cert = new Socket\Certificate(__DIR__ . '/../test/server.pem');

    // $context = (new Socket\BindContext)
    //     ->withTlsContext((new Socket\ServerTlsContext)->withDefaultCertificate($cert));

    $servers = [
        Socket\Server::listen("0.0.0.0:80"),
        Socket\Server::listen("[::]:80"),
        // Socket\Server::listen("0.0.0.0:1338", $context),
        // Socket\Server::listen("[::]:1338", $context),
    ];

    $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);

    $gibbon = new Gibbon(
        container: new Dice(),
        app_root: __DIR__ . '/content'
    );
    $server = new HttpServer($servers, new GibbonHandler($gibbon), $logger);

    yield $server->start();

    // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
    Amp\Loop::onSignal(\SIGINT, static function (string $watcherId) use ($server) {
        Amp\Loop::cancel($watcherId);
        yield $server->stop();
    });
});