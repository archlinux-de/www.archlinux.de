<?php

namespace App\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Packages\Architecture;
use App\Request\ArchitectureRequest;

class ArchitectureValueResolver implements ValueResolverInterface
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$argument->getType() || !is_a($argument->getType(), ArchitectureRequest::class, true)) {
            return [];
        }

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

        return [$architectureRequest];
    }
}
