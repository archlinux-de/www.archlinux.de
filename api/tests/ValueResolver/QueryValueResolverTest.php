<?php

namespace App\Tests\ValueResolver;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\ValueResolver\QueryValueResolver;
use App\Request\QueryRequest;

class QueryValueResolverTest extends TestCase
{
    /** @var ValidatorInterface|MockObject */
    private mixed $validator;

    private QueryValueResolver $queryValueResolver;

    public function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->queryValueResolver = new QueryValueResolver($this->validator);
    }

    public function testResolve(): void
    {
        /** @var ArgumentMetadata|MockObject $argument */
        $argument = $this->createMock(ArgumentMetadata::class);
        $argument
            ->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn(QueryRequest::class);

        $request = Request::create('/get', 'GET', ['query' => 'foo']);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback(
                fn(QueryRequest $_) => new ConstraintViolationList()
            );

        $values = [...$this->queryValueResolver->resolve($request, $argument)];
        $this->assertCount(1, $values);

        $this->assertInstanceOf(QueryRequest::class, $values[0]);
        /** @var QueryRequest $packageQueryRequest */
        $packageQueryRequest = $values[0];
        $this->assertEquals('foo', $packageQueryRequest->getQuery());
    }

    public function testDefault(): void
    {
        /** @var ArgumentMetadata|MockObject $argument */
        $argument = $this->createMock(ArgumentMetadata::class);
        $argument
            ->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn(QueryRequest::class);

        $request = Request::create('/get');

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback(
                fn(QueryRequest $_) => new ConstraintViolationList()
            );

        $values = [...$this->queryValueResolver->resolve($request, $argument)];
        $this->assertCount(1, $values);

        $this->assertInstanceOf(QueryRequest::class, $values[0]);
        /** @var QueryRequest $packageQueryRequest */
        $packageQueryRequest = $values[0];
        $this->assertEquals('', $packageQueryRequest->getQuery());
    }

    public function testResolveFailsOnValidationErrors(): void
    {
        /** @var ArgumentMetadata|MockObject $argument */
        $argument = $this->createMock(ArgumentMetadata::class);
        $argument
            ->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn(QueryRequest::class);

        $request = Request::create('/get', 'GET', ['query' => 'foo']);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback(
                fn(QueryRequest $_) => new ConstraintViolationList([$this->createMock(ConstraintViolation::class)])
            );

        $this->expectException(BadRequestHttpException::class);
        $this->queryValueResolver->resolve($request, $argument);
    }
}
