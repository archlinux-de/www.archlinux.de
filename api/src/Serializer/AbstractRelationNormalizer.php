<?php

namespace App\Serializer;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AbstractRelationNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(private RepositoryNormalizer $repositoryNormalizer)
    {
    }

    /**
     * @param AbstractRelation $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $data = [
            'name' => $object->getTargetName(),
            'version' => $object->getTargetVersion(),
        ];

        if ($object->getTarget() instanceof Package) {
            $target = $object->getTarget();
            $data['target'] = [
                'name' => $target->getName(),
                'repository' => $this->repositoryNormalizer->normalize($target->getRepository(), $format, $context)
            ];
        } else {
            $data['target'] = null;
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof AbstractRelation && $format == 'json';
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
