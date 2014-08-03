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
use GuzzleHttp\Exception\ClientException;
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
        $output->write(str_repeat(PHP_EOL, 2));

        $output->writeln('  ______ _         ______
 |  ____(_)       |  ____|
 | |__   _ _______| |__ _   _ ________
 |  __| | |_  /_  /  __| | | |_  /_  /
 | |    | |/ / / /| |  | |_| |/ / / /
 |_|    |_/___/___|_|   \__,_/___/___|');
        $output->writeln('                        by @alexbilbie');
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

            $output->writeln(str_repeat('*', 80));
            $output->write(PHP_EOL);

            $output->writeln('Parsing '.$file->getRealpath());

            $rules = YamlRuleset::parse($file->getRealpath());
            $payloads = (new RequestGenerator($this->client, $rules))->generateRequests();
            $numPayloads = count($payloads);

            $output->writeln('Generated '.$numPayloads.' payload');

            $output->writeln('Running rules...'.PHP_EOL);
            $progress = $this->getHelper('progress');
            $progress->start($output, $numPayloads);

            $tableRows = [];
            $table = new Table($output)->setHeaders(['Task', 'Result']);

            foreach ($payloads as $payload) {

                try {
                    $response = $this->client->send($payload->getRequest());
                } catch (ClientException $e) {
                    $response = $e->getResponse();
                }

                $errors = $payload->evaluateResponse($response);
                $errorCount = count($errors);
                if ($errorCount > 0) {
                    $outputResponse = sprintf('<error>%s %s:</error>', $errorCount, ($errorCount > 1) ?'errors' : 'error');
                    foreach ($errors as $error) {
                        $outputResponse .= PHP_EOL.'<error>* '.$error.'</error>';
                    }
                } else {
                   $outputResponse = '<info>passed</info>';
                }

                $tableRows[] = [
                    $payload->getDescription(),
                    $outputResponse
                ];
                // $tableRows[] = new TableSeparator();

                $progress->advance();
            }

            $progress->finish();
            $output->write(PHP_EOL);
            $table->setRows($tableRows)->render();
            $output->write(PHP_EOL);
        }
    }
}
