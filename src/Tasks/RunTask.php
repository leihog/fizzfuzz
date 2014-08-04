<?php

namespace FizzFuzz\Tasks;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use GuzzleHttp\Client as Client;
use GuzzleHttp\Exception\RequestException;
use FizzFuzz\Utils\YamlRuleset;
use FizzFuzz\Utils\RequestGenerator;

class RunTask extends Command
{
    protected $finder;

    protected $client;

    public function __construct(Finder $finder, Client $client)
    {
        $this->finder = $finder;
        $this->client = $client;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Run FizzFuzz')
            ->addArgument(
               'path',
               InputArgument::REQUIRED,
               'Directory of rulesets'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $output->write(str_repeat(PHP_EOL, 2));

        $output->writeln('<fg=magenta>                      ______ _         ______
                     |  ____(_)       |  ____|
                     | |__   _ _______| |__ _   _ ________
                     |  __| | |_  /_  /  __| | | |_  /_  /
                     | |    | |/ / / /| |  | |_| |/ / / /
                     |_|    |_/___/___|_|   \__,_/___/___|</fg=magenta>');
        $output->writeln('<fg=magenta>                                            by @alexbilbie</fg=magenta>');
        $path = $input->getArgument('path');

        if (is_dir($path)) {
            $files = $this->finder->files()->in($path)->name('*.yml');
        } elseif (file_exists($path)) {
            $files = [
                new \SplFileInfo($path)
            ];
        } else {
            $output->writeln('<error>`'.$path.'` is not a directory or file that exists</error>');
            exit(1);
        }

        $output->write(str_repeat(PHP_EOL, 2));

        foreach ($files as $file) {

            $output->writeln(PHP_EOL.str_repeat('<fg=magenta>*</fg=magenta>', 80).PHP_EOL);

            $output->writeln('Parsing '.$file->getRealpath());

            $rules = YamlRuleset::parse($file->getRealpath());
            $payloads = (new RequestGenerator($this->client, $rules))->generateRequests();
            $numPayloads = count($payloads);

            $output->writeln(sprintf('Sending %s %s...', $numPayloads, ($numPayloads === 1) ? 'payload' : 'payloads').PHP_EOL);

            foreach ($payloads as $payload) {

                try {
                    $response = $this->client->send($payload->getRequest());
                } catch (RequestException $e) {
                    $response = $e->getResponse();
                }

                $errors = $payload->evaluateResponse($response);
                $errorCount = count($errors);
                if ($errorCount > 0) {
                    $outputResponse = '<fg=red>errored</fg=red>';
                    foreach ($errors as $error) {
                        $outputResponse .= PHP_EOL.'* <fg=red>'.$error.'</fg=red>';
                    }
                } else {
                   $outputResponse = '<info>passed</info>';
                }

                $output->writeln('<comment>'.$payload->getDescription().'</comment> ... '.$outputResponse);
                $output->write(PHP_EOL);

                // Request
                if ($payload->getRequest()->getHeader('content-type') === 'application/json') {
                    $output->writeln(
                        'Request:'.PHP_EOL.'<fg=blue>'
                        .$payload->getRequest()->getStartLineAndHeaders(
                            $payload->getRequest()
                        )
                        .json_encode(
                            json_decode(
                                $payload->getRequest()->getBody()
                            ),
                            SON_PRETTY_PRINT
                        )
                        .'</fg=blue>');
                } else {
                    $output->writeln('Request:'.PHP_EOL.'<fg=blue>'.implode(PHP_EOL, str_split($payload->getRequest()->__toString(), 80)).'</fg=blue>');
                }

                // Response
                if ($response->getHeader('content-type') === 'application/json') {
                    $output->writeln('Response:'.PHP_EOL.'<fg=blue>'.
                        $response->getStartLineAndHeaders($response)
                        .PHP_EOL.PHP_EOL.
                        json_encode(
                            json_decode(
                                $response->getBody()
                            ),
                            JSON_PRETTY_PRINT
                        )
                        .'</fg=blue>');
                } else {
                    $output->writeln('Response:'.PHP_EOL.'<fg=blue>'.implode(PHP_EOL, str_split($response->__toString(), 80)).'</fg=blue>');
                }
            }
        }

        $output->write(PHP_EOL);
        $output->writeln(str_repeat('<fg=magenta>*</fg=magenta>', 80));
        $output->write(PHP_EOL);
        $output->writeln(sprintf('<info>Completed in %ss</info>', (microtime(true) - $start)));
        $output->write(PHP_EOL);
    }
}
