<?php

namespace FizzFuzz;

use GuzzleHttp\Client as Client;
use GuzzleHttp\Exception\ClientException;
use FizzFuzz\Utils\YamlRuleset;
use FizzFuzz\Utils\RequestGenerator;

include __DIR__.'/../vendor/autoload.php';

$rules = YamlRuleset::parse(__DIR__.'/Example/cc.yml');
$client = new Client();
$payloads = (new RequestGenerator($client, $rules))->generateRequests();

foreach ($payloads as $payload) {
    try {
        $response = $client->send($payload->getRequest());
    } catch (ClientException $e) {
        $response = $e->getResponse();
    } finally {
        var_dump(
            $payload->getDescription(),
            $payload->evaluateResponse($response)
        );
    }
}
