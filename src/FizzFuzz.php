<?php

namespace FizzFuzz;

use GuzzleHttp\Client as Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;
use FizzFuzz\Tasks;

include __DIR__.'/../vendor/autoload.php';

// $rules = YamlRuleset::parse(__DIR__.'/Example/cc.yml');
$finder = new Finder();
$client = new Client();
// $payloads = (new RequestGenerator($client, $rules))->generateRequests();

$application = new Application();
$application->add(new Tasks\RunTask($finder, $client));
$application->run();