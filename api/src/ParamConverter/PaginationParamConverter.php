<?php

namespace App\ParamConverter;

use App\Exception\ValidationException;
use App\Request\PaginationRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PaginationParamConverter implements ParamConverterInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $paginationRequest = new PaginationRequest(
            $request->query->getInt('offset', 0),
            $request->query->getInt('limit', 100)
        );

        $errors = $this->validator->validate($paginationRequest);
        if ($errors->count() > 0) {
            throw new BadRequestHttpException('Invalid request', new ValidationException($errors));
        }

        $request->attributes->set(
            $configuration->getName(),
            $paginationRequest
        );

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() == PaginationRequest::class;
    }
}
