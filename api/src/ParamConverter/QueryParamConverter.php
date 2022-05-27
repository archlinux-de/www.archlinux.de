<?php

namespace App\ParamConverter;

use App\Request\QueryRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QueryParamConverter implements ParamConverterInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $query = $request->get('query', '');
        if (!is_string($query)) {
            throw new BadRequestHttpException('Invalid request');
        }

        $queryRequest = new QueryRequest($query);

        $errors = $this->validator->validate($queryRequest);
        if ($errors->count() > 0) {
            throw new BadRequestHttpException('Invalid request', new ValidationFailedException($queryRequest, $errors));
        }

        $request->attributes->set(
            $configuration->getName(),
            $queryRequest
        );

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() == QueryRequest::class;
    }
}
