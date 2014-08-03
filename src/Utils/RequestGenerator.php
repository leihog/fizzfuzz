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

        // Parse headers
        if (isset($this->rules['request']['header'])) {
            $header = [];
            foreach ($this->rules['request']['header'] as $headerItem) {

                // Mangle a header with the same key
                if ($propertyType === 'header' && $key === $headerItem['key']) {

                    // Change the type
                    if ($errorType === 'invalid' && isset($headerItem['invalid'])) {

                        // Change the type
                        switch (gettype($headerItem['value'])) {
                            case 'string':
                            case 'boolean':
                                $header[$headerItem['key']] = rand(2, 1000);
                                break;
                            case 'integer':
                            case 'double':
                            case 'float':
                                $header[$headerItem['key']] = uniqid();
                                break;
                        }

                        foreach ($headerItem['invalid'] as $key => $value) {
                            $expectations[] = (new Expectation('headers', strtolower($key)))->setValue($value);
                        }

                    } elseif ($errorType === 'missing' && isset($headerItem['missing'])) {

                        // Leave out the header altogether
                        foreach ($headerItem['missing'] as $key => $value) {
                            $expectations[] = (new Expectation('headers', strtolower($key)))->setValue($value);
                        }

                    }

                } else {
                    $header[$headerItem['key']] = $headerItem['value'];
                }

            }

            $options['headers'] = $header;
        }

        // Parse body
        if (isset($this->rules['request']['body'])) {
            $body = [];
            foreach ($this->rules['request']['body'] as $bodyItem) {

                // Mangle a body item with the same key
                if ($propertyType === 'body' && $key === $bodyItem['key']) {

                    // Change the type
                    if ($errorType === 'invalid' && isset($bodyItem['invalid'])) {

                        // Change the type
                        switch (gettype($bodyItem['value'])) {
                            case 'string':
                            case 'boolean':
                                $body[$bodyItem['key']] = rand(2, 1000);
                                break;
                            case 'integer':
                            case 'double':
                            case 'float':
                                $body[$bodyItem['key']] = uniqid();
                                break;
                        }

                        foreach ($bodyItem['invalid'] as $key => $value) {
                            $parts = explode('.', $key, 2);
                            $expectations[] = (new Expectation($parts[0], $parts[1]))->setValue($value);
                        }

                    } elseif ($errorType === 'missing' && isset($bodyItem['missing'])) {

                        // Leave out the body item altogether
                        foreach ($bodyItem['missing'] as $key => $value) {
                            $parts = explode('.', $key, 2);
                            $expectations[] = (new Expectation($parts[0], $parts[1]))->setValue($value);
                        }

                    }

                } else {
                    $body[$bodyItem['key']] = $bodyItem['value'];
                }

            }
            $options['body'] = $body;
        }

        // If we're not mangling the request set the "perfect" response expectations
        if ($errorType === null) {

            if (isset($this->rules['response']['statusCode'])) {
                $expectations[] = (new Expectation('response', 'statusCode'))->setValue($this->rules['response']['statusCode']);
            }

            if (isset($this->rules['response']['headers'])) {
                foreach ($this->rules['response']['headers'] as $key => $value) {
                    $expectations[] = (new Expectation('headers', strtolower($key)))->setValue($value);
                }
            }

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

            $description = 'Default request';
        } else {
            $description = ucfirst($errorType).' '.$propertyType.' `'.$key.'`';
        }

        $request = $this->client->createRequest($this->rules['request']['method'], $this->rules['url'], $options);
        $payload = new Payload($description, $request, $expectations);
        return $payload;
    }

    public function generateRequests()
    {
        $payloads = [];

        // Generate a "perfect" request
        $payloads[] = $this->generateRequest();

        // Loop over headers
        if (isset($this->rules['request']['header'])) {
            foreach ($this->rules['request']['header'] as $headerItem) {
                $payloads[] = $this->generateRequest('header', $headerItem['key'], 'missing');
                $payloads[] = $this->generateRequest('header', $headerItem['key'], 'invalid');
            }
        }

        // Loop over body
        if (isset($this->rules['request']['body'])) {
            foreach ($this->rules['request']['body'] as $bodyItem) {
                $payloads[] = $this->generateRequest('body', $bodyItem['key'], 'missing');
                $payloads[] = $this->generateRequest('body', $bodyItem['key'], 'invalid');
            }
        }

        return $payloads;
    }
}