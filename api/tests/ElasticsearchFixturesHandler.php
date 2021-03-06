<?php

namespace App\Tests;

use Elasticsearch\ClientBuilder;
use GuzzleHttp\Ring\Future\CompletedFutureArray;
use GuzzleHttp\Ring\Future\CompletedFutureValue;

class ElasticsearchFixturesHandler
{
    /** @var callable */
    private $defaultHandler;

    /** @var string */
    private $mode;

    /** @var string */
    private $fixturesDirectory = __DIR__ . '/ElasticsearchFixtures';

    /**
     * @param string $mode
     */
    public function __construct(string $mode)
    {
        $this->mode = $mode;
        $this->defaultHandler = ClientBuilder::defaultHandler();
    }

    /**
     * @param array $request
     * @return CompletedFutureArray
     */
    public function __invoke(array $request): CompletedFutureArray
    {
        if ($this->mode == 'off') {
            return ($this->defaultHandler)($request);
        }

        $requestIdentifier = [
            $request['http_method'],
            $request['uri'],
            $request['body']
        ];
        $filename = $this->fixturesDirectory . '/' . hash('sha256', implode(':', $requestIdentifier)) . '.json';

        if ($this->mode == 'read') {
            if (!file_exists($filename)) {
                throw new \RuntimeException(
                    sprintf('Fixture not found for request "%s"', implode('; ', $requestIdentifier))
                );
            }
            $result = json_decode((string)file_get_contents($filename), true);
        } elseif ($this->mode == 'write') {
            /** @var CompletedFutureArray $response */
            $response = ($this->defaultHandler)($request);
            $result = $response->wait();

            if (is_resource($result['body'])) {
                $result['body'] = stream_get_contents($result['body']);
            }
            file_put_contents($filename, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            throw new \RuntimeException(sprintf('Unsupported mode %s', $this->mode));
        }

        $result['body'] = fopen('data:text/plain;base64,' . base64_encode($result['body']), 'rb');

        return new CompletedFutureArray($result);
    }
}
