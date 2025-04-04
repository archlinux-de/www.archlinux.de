<?php

namespace App\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Request\PaginationRequest;

readonly class PaginationValueResolver implements ValueResolverInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @return iterable<PaginationRequest>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$argument->getType() || !is_a($argument->getType(), PaginationRequest::class, true)) {
            return [];
        }

        $paginationRequest = new PaginationRequest(
            $request->query->getInt('offset', 0),
            $request->query->getInt('limit', 100)
        );

        $errors = $this->validator->validate($paginationRequest);

        if ($errors->count() > 0) {
            throw new BadRequestHttpException(
                'Invalid request',
                new ValidationFailedException($paginationRequest, $errors)
            );
        }

        return [$paginationRequest];
    }
}
