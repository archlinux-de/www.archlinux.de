<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \RuntimeException
{
    public function __construct(ConstraintViolationListInterface $constraintViolationList)
    {
        parent::__construct(
            implode(
                "\n",
                [...(function () use ($constraintViolationList) {
                    /** @var ConstraintViolationInterface $constraintViolation */
                    foreach ($constraintViolationList as $constraintViolation) {
                        yield sprintf(
                            'Validation of %s failed. %s',
                            json_encode($constraintViolation->getInvalidValue()),
                            (string)$constraintViolation->getMessage()
                        );
                    }
                })()]
            )
        );
    }
}
