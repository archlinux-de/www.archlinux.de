<?php

namespace App\Tests\OpenSearchMock;

use Nyholm\Psr7\Response;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ResponseNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): Response {
        return new Response($data['status'], $data['headers'], $data['body'], $data['version'], $data['reason']);
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === Response::class;
    }

    /**
     * @param Response $data
     * @return array{string, mixed}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return [
            'status' => $data->getStatusCode(),
            'headers' => $this->normalizeHeaders($data->getHeaders()),
            'body' => $this->normalizeBody($data->getBody()->getContents()),
            'version' => $data->getProtocolVersion(),
            'reason' => $data->getReasonPhrase(),
        ];
    }

    /**
     * @param string[][] $headers
     * @return string[][]
     */
    private function normalizeHeaders(array $headers): array
    {
        return ['content-type' => $headers['content-type'],];
    }

    private function normalizeBody(string $body): string
    {
        $tmpBody = json_decode($body, true);
        if (isset($tmpBody['took'])) {
            $tmpBody['took'] = 1;
            return json_encode($tmpBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $body;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Response;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Response::class => '*'];
    }
}
