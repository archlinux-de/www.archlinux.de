<?php

namespace App\ParamConverter;

use App\Entity\Packages\Architecture;
use App\Request\ArchitectureRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArchitectureParamConverter implements ParamConverterInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $architecture = $request->get('architecture', Architecture::X86_64);
        if (!is_string($architecture)) {
            throw new BadRequestHttpException('Invalid request');
        }

        $architectureRequest = new ArchitectureRequest($architecture);

        $errors = $this->validator->validate($architectureRequest);
        if ($errors->count() > 0) {
            throw new BadRequestHttpException(
                'Invalid request',
                new ValidationFailedException($architectureRequest, $errors)
            );
        }

        $request->attributes->set(
            $configuration->getName(),
            $architectureRequest
        );

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() == ArchitectureRequest::class;
    }
}
