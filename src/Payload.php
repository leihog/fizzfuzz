<?php

namespace FizzFuzz;

use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use FizzFuzz\Expectation;
use Assert\AssertionFailedException;

class Payload
{
    protected $description;
    protected $request;
    protected $expectations = [];

    public function __construct($description, Request $request, array $expectations)
    {
        $this->description = $description;
        $this->request = $request;

        foreach ($expectations as $expectation) {
            $this->expectations[$expectation->getType().'.'.$expectation->getKey()] = $expectation;
        }
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function evaluateResponse(Response $response)
    {
        $errors = [];
        try {

            // Test response code
            if (array_key_exists('response.statusCode', $this->expectations)) {
                $this->expectations['response.statusCode']->evaluate(
                    (int) $response->getStatusCode(),
                    sprintf('Status code was expected to be `%s` but was actually `%s`', '%s', $response->getStatusCode())
                );
            }

        } catch (AssertionFailedException $e) {
            $errors[] = $e->getMessage();
        }

        // Test headers
        foreach ($this->expectations as $name => $expectation) {
            try {
                if (substr($name, 0, 8) === 'headers.') {
                    $headerName = substr($name, 8);
                    $headerValue = $response->getHeader($headerName);
                    $this->expectations['headers.'.strtolower($headerName)]->evaluate(
                        $headerValue,
                        'Header `'.$headerName.'` expected to be `%s` but was `%s`'
                    );
                }
            } catch (AssertionFailedException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // Test body
        $body = json_decode($response->getBody(), true);
        foreach ($this->expectations as $name => $expectation) {
            try {
                if (substr($name, 0, 5) === 'body.') {
                    $keyName = substr($name, 5);
                    $keyValue = array_get($body, $keyName);
                    $this->expectations['body.'.strtolower($keyName)]->evaluate(
                        $keyValue,
                        'Body key `'.$keyName.'` expected to be `%s` but was `%s`'
                    );
                }
            } catch (AssertionFailedException $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }
}
