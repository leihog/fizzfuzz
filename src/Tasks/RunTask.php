<?php

namespace FizzFuzz\Tasks;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use GuzzleHttp\Client as Client;
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
               'directory',
               InputArgument::REQUIRED,
               'Directory of rulesets'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(str_repeat(PHP_EOL, 2));

        $output->writeln('  ______ _         ______
 |  ____(_)       |  ____|
 | |__   _ _______| |__ _   _ ________
 |  __| | |_  /_  /  __| | | |_  /_  /
 | |    | |/ / / /| |  | |_| |/ / / /
 |_|    |_/___/___|_|   \__,_/___/___|');
        $output->writeln('                        by @alexbilbie');
        $directory = $input->getArgument('directory');
        $files = $this->finder->files()->in($directory)->name('*.yml');

        $output->write(str_repeat(PHP_EOL, 2));

        foreach ($files as $file) {

            $output->writeln(str_repeat('*', 80));
            $output->write(PHP_EOL);

            $output->writeln('<comment>Parsing '.$file->getRealpath().'</comment>');

            $rules = YamlRuleset::parse($file->getRealpath());
            $payloads = (new RequestGenerator($this->client, $rules))->generateRequests();
            $numPayloads = count($payloads);

            $output->writeln('<comment>Generated '.$numPayloads.' payload</comment>');

            $progress = $this->getHelper('progress');
            $output->writeln('Running rules...');
            $progress->start($output, $numPayloads);

            foreach ($payloads as $payload) {
                try {
                    $response = $this->client->send($payload->getRequest());
                } catch (ClientException $e) {
                    $response = $e->getResponse();
                } finally {
                    $output->write(PHP_EOL);
                    $output->writeln('Testing payload: '.$payload->getDescription());

                    $errors = $payload->evaluateResponse($response);
                    if (count($errors) > 0) {
                        foreach ($errors as $error) {
                            $output->writeln('<error>* '.$error.'</error>');
                        }
                    }

                    $progress->advance();
                }
            }

            $progress->finish();
            $output->write(PHP_EOL);
        }

        $output->writeln('<info>Done</info>');
    }
}
