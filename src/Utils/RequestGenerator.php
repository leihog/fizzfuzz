<?php

namespace FizzFuzz\Utils;

use GuzzleHttp\Client as Client;
use FizzFuzz\Payload;
use FizzFuzz\Expectation;

class RequestGenerator
{
    protected $client;
    protected $rules;

    public function __construct(Client $client, array $rules)
    {
        $this->client = $client;
        $this->rules = $rules;
    }

    protected function generateRequest($propertyType = null, $key = null, $errorType = null)
    {
        $options = [];
        $expectations = [];

        switch ($errorType) {

            case 'missing':

                if (isset($this->rules['request'][$propertyType])) {

                    foreach ($this->rules['request'][$propertyType] as $item) {

                        // Only mangle a specific item with the same key
                        if ($key === $item['key']) {

                            $description = sprintf('Missing request %s item `%s`', ($propertyType === 'body') ? 'body' : 'header', $item['key']);

                            // Leave out the item altogether
                            if (isset($item['missing'])) {
                                foreach ($item['missing'] as $key => $value) {
                                    $parts = explode('.', $key, 2);
                                    $expectations[] = (new Expectation($parts[0], strtolower($parts[1])))->setValue($value);
                                }
                            }

                        // Items that we're not mangling
                        } else {
                            $options[$propertyType][$item['key']] = $item['value'];
                        }

                    }

                }

                break;

            case 'invalid':

                if (isset($this->rules['request'][$propertyType])) {

                    foreach ($this->rules['request'][$propertyType] as $item) {

                        // Mangle a header with the same key
                        if ($key === $item['key']) {

                            // Change the type
                            switch (gettype($item['value'])) {
                                case 'string':
                                case 'boolean':
                                    $options[$propertyType][$item['key']] = rand(2, 1000);
                                    break;
                                case 'integer':
                                case 'double':
                                case 'float':
                                    $options[$propertyType][$item['key']] = uniqid();
                                    break;
                            }

                            $description = sprintf('Invalid request %s item `%s`', ($propertyType === 'body') ? 'body' : 'header', $item['key']);

                            if (isset($item['invalid'])) {
                                foreach ($item['invalid'] as $key => $value) {
                                    $parts = explode('.', $key, 2);
                                    $expectations[] = (new Expectation($parts[0], strtolower($parts[1])))->setValue($value);
                                }
                            }

                        // Items that we're not mangling
                        } else {
                            $options[$propertyType][$item['key']] = $item['value'];
                        }

                    }

                }
                break;

            default:

                if (isset($this->rules['request']['headers'])) {
                    foreach ($this->rules['request']['headers'] as $item) {
                        $options['headers'][$item['key']] = $item['value'];
                    }
                }

                if (isset($this->rules['request']['body'])) {
                    foreach ($this->rules['request']['body'] as $item) {
                        $options['body'][$item['key']] = $item['value'];
                    }
                }

                // Expect a specific status code
                if (isset($this->rules['response']['statusCode'])) {
                    $expectations[] = (new Expectation('response', 'statusCode'))->setValue($this->rules['response']['statusCode']);
                }

                // Expect headers
                if (isset($this->rules['response']['headers'])) {
                    foreach ($this->rules['response']['headers'] as $headerItem) {
                        if (isset($bodyItem['value'])) {
                            $expectations[] = (new Expectation('headers', strtolower($headerItem['key'])))->setValue($headerItem['value']);
                        } elseif (isset($headerItem['valueType'])) {
                            $expectations[] = (new Expectation('headers', strtolower($headerItem['key'])))->setValueType($headerItem['valueType']);
                        } elseif (isset($headerItem['valueRegex'])) {
                            $expectations[] = (new Expectation('headers', strtolower($headerItem['key'])))->setValueRegex($headerItem['valueRegex']);
                        }
                    }
                }

                // Expect body items
                if (isset($this->rules['response']['body'])) {
                    foreach ($this->rules['response']['body'] as $bodyItem) {
                        if (isset($bodyItem['value'])) {
                            $expectations[] = (new Expectation('body', $bodyItem['key']))->setValue($bodyItem['value']);
                        } elseif (isset($bodyItem['valueType'])) {
                            $expectations[] = (new Expectation('body', $bodyItem['key']))->setValueType($bodyItem['valueType']);
                        } elseif (isset($bodyItem['valueRegex'])) {
                            $expectations[] = (new Expectation('body', $bodyItem['key']))->setValueRegex($bodyItem['valueRegex']);
                        }
                    }
                }

                $description = 'Expected response';

                break;

        }

        $request = $this->client->createRequest($this->rules['request']['method'], $this->rules['url'], $options);
        $payload = new Payload($description, $request, $expectations, $output);
        return $payload;
    }

    public function generateRequests()
    {
        $payloads = [];

        // Generate an expected request
        $payloads[] = $this->generateRequest();

        // Loop over headers
        if (isset($this->rules['request']['header'])) {
            foreach ($this->rules['request']['header'] as $headerItem) {
                if (isset($headerItem['missing'])) {
                    $payloads[] = $this->generateRequest('header', $headerItem['key'], 'missing');
                }
                if (isset($headerItem['invalid'])) {
                    $payloads[] = $this->generateRequest('header', $headerItem['key'], 'invalid');
                }
            }
        }

        // Loop over body
        if (isset($this->rules['request']['body'])) {
            foreach ($this->rules['request']['body'] as $bodyItem) {
                if (isset($bodyItem['missing'])) {
                    $payloads[] = $this->generateRequest('body', $bodyItem['key'], 'missing');
                }
                if (isset($bodyItem['invalid'])) {
                    $payloads[] = $this->generateRequest('body', $bodyItem['key'], 'invalid');
                }
            }
        }

        return $payloads;
    }
}