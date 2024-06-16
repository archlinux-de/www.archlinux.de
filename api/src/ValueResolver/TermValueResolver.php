<?php

namespace App\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Request\TermRequest;

readonly class TermValueResolver implements ValueResolverInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$argument->getType() || !is_a($argument->getType(), TermRequest::class, true)) {
            return [];
        }

        $term = $request->get('term', '');
        if (!is_string($term)) {
            throw new BadRequestHttpException('Invalid request');
        }

        $termRequest = new TermRequest($term);

        $errors = $this->validator->validate($termRequest);
        if ($errors->count() > 0) {
            throw new BadRequestHttpException('Invalid request', new ValidationFailedException($termRequest, $errors));
        }

        return [$termRequest];
    }
}
