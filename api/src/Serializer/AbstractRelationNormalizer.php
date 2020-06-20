<?php

namespace App\Serializer;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AbstractRelationNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var RepositoryNormalizer */
    private $repositoryNormalizer;

    /**
     * @param RepositoryNormalizer $repositoryNormalizer
     */
    public function __construct(RepositoryNormalizer $repositoryNormalizer)
    {
        $this->repositoryNormalizer = $repositoryNormalizer;
    }

    /**
     * @param AbstractRelation $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize($object, string $format = null, array $context = [])
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

    /**
     * @param mixed $data
     * @param string|null $format
     * @return bool
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof AbstractRelation && $format == 'json';
    }

    /**
     * @return bool
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
