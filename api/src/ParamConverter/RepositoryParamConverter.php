<?php

namespace App\ParamConverter;

use App\Request\RepositoryRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RepositoryParamConverter implements ParamConverterInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $repository = $request->get('repository', '');
        if (!is_string($repository)) {
            throw new BadRequestHttpException('Invalid request');
        }

        $repositoryRequest = new RepositoryRequest($repository);

        $errors = $this->validator->validate($repositoryRequest);
        if ($errors->count() > 0) {
            throw new BadRequestHttpException(
                'Invalid request',
                new ValidationFailedException($repositoryRequest, $errors)
            );
        }

        $request->attributes->set(
            $configuration->getName(),
            $repositoryRequest
        );

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() == RepositoryRequest::class;
    }
}
