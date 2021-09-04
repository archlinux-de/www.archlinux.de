<?php

namespace App\ParamConverter;

use App\Exception\ValidationException;
use App\Request\QueryRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QueryParamConverter implements ParamConverterInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $packageQueryRequest = new QueryRequest($request->get('query', ''));

        $errors = $this->validator->validate($packageQueryRequest);
        if ($errors->count() > 0) {
            throw new BadRequestHttpException('Invalid request', new ValidationException($errors));
        }

        $request->attributes->set(
            $configuration->getName(),
            $packageQueryRequest
        );

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() == QueryRequest::class;
    }
}
