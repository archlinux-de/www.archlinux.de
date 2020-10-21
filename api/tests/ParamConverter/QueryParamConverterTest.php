<?php

namespace App\Tests\ParamConverter;

use App\ParamConverter\QueryParamConverter;
use App\Request\QueryRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QueryParamConverterTest extends TestCase
{
    /** @var ValidatorInterface|MockObject */
    private $validator;

    /** @var QueryParamConverter */
    private $paramConverter;

    public function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->paramConverter = new QueryParamConverter($this->validator);
    }

    public function testSupports(): void
    {
        /** @var ParamConverter|MockObject $configuration */
        $configuration = $this->createMock(ParamConverter::class);
        $configuration
            ->expects($this->once())
            ->method('getClass')
            ->willReturn(QueryRequest::class);

        $this->assertTrue($this->paramConverter->supports($configuration));
    }

    public function testApply(): void
    {
        /** @var ParamConverter|MockObject $configuration */
        $configuration = $this->createMock(ParamConverter::class);
        $configuration
            ->expects($this->once())
            ->method('getName')
            ->willReturn(QueryRequest::class);

        $request = Request::create('/get', 'GET', ['query' => 'foo']);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback(
                fn(QueryRequest $_) => new ConstraintViolationList()
            );

        $this->assertTrue($this->paramConverter->apply($request, $configuration));

        $this->assertInstanceOf(QueryRequest::class, $request->attributes->get(QueryRequest::class));
        /** @var QueryRequest $packageQueryRequest */
        $packageQueryRequest = $request->attributes->get(QueryRequest::class);
        $this->assertEquals('foo', $packageQueryRequest->getQuery());
    }

    public function testDefault(): void
    {
        /** @var ParamConverter|MockObject $configuration */
        $configuration = $this->createMock(ParamConverter::class);
        $configuration
            ->expects($this->once())
            ->method('getName')
            ->willReturn(QueryRequest::class);

        $request = Request::create('/get');

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback(
                fn(QueryRequest $_) => new ConstraintViolationList()
            );

        $this->assertTrue($this->paramConverter->apply($request, $configuration));

        $this->assertInstanceOf(QueryRequest::class, $request->attributes->get(QueryRequest::class));
        /** @var QueryRequest $packageQueryRequest */
        $packageQueryRequest = $request->attributes->get(QueryRequest::class);
        $this->assertEquals('', $packageQueryRequest->getQuery());
    }

    public function testApplyFailsOnValidationErrors(): void
    {
        /** @var ParamConverter|MockObject $configuration */
        $configuration = $this->createMock(ParamConverter::class);

        $request = Request::create('/get');

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback(
                fn(QueryRequest $_) => new ConstraintViolationList([$this->createMock(ConstraintViolation::class)])
            );

        $this->expectException(BadRequestHttpException::class);
        $this->paramConverter->apply($request, $configuration);
    }
}
