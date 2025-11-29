<?php

namespace App\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Request\RepositoryRequest;

readonly class RepositoryValueResolver implements ValueResolverInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @return iterable<RepositoryRequest>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$argument->getType() || !is_a($argument->getType(), RepositoryRequest::class, true)) {
            return [];
        }

        $repository = $request->query->get('repository', '');
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

        return [$repositoryRequest];
    }
}
