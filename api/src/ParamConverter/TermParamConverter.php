<?php

namespace App\ParamConverter;

use App\Request\TermRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TermParamConverter implements ParamConverterInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $term = $request->get('term', '');
        if (!is_string($term)) {
            throw new BadRequestHttpException('Invalid request');
        }

        $termRequest = new TermRequest($term);

        $errors = $this->validator->validate($termRequest);
        if ($errors->count() > 0) {
            throw new BadRequestHttpException('Invalid request', new ValidationFailedException($termRequest, $errors));
        }

        $request->attributes->set(
            $configuration->getName(),
            $termRequest
        );

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() == TermRequest::class;
    }
}
