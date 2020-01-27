<?php

namespace App\Serializer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\String\ByteString;

class PacmanDatabaseDecoder implements DecoderInterface
{
    /**
     * @param string $data
     * @param string $format
     * @param array<mixed> $context
     * @return array<mixed>
     */
    public function decode(string $data, string $format, array $context = []): array
    {
        $key = null;
        $result = [];

        /** @var ByteString $line */
        foreach ((new ByteString($data))->split("\n") as $line) {
            if ($line->match('/\S+/')) {
                if ($line->length() > 2 && $line->startsWith('%') && $line->endsWith('%')) {
                    $key = $line->slice(1, -1)->toString();
                    $result[$key] = [];
                } elseif ($key) {
                    $value = $line->trim();
                    if ($value->match('/^[0-9]+$/')) {
                        $value = (int)$value->toString();
                    } else {
                        $value = $value->toString();
                    }
                    $result[$key][] = $value;
                }
            }
        }

        $result = array_map(
            function (array $entry) {
                if (empty($entry)) {
                    return null;
                }
                if (count($entry) == 1) {
                    return $entry[0];
                }
                return $entry;
            },
            $result
        );

        return $result;
    }

    /**
     * @param string $format
     * @return bool
     */
    public function supportsDecoding(string $format): bool
    {
        return $format === 'pacman-database';
    }
}
