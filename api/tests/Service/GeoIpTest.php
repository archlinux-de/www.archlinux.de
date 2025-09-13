<?php

namespace App\Tests\Service;

use App\Service\GeoIp;
use MaxMind\Db\Reader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GeoIpTest extends TestCase
{
    private Reader&MockObject $reader;
    private LoggerInterface&MockObject $logger;
    private GeoIp $geoIp;

    public function setUp(): void
    {
        $this->reader = $this->getMockBuilder(Reader::class)->disableOriginalConstructor()->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->geoIp = new GeoIp($this->reader, $this->logger);
    }

    public function testGeoIpReturnsCountryCode(): void
    {
        $this->reader->method('get')->willReturn(['country' => ['iso_code' => 'DE']]);
        $this->assertEquals('DE', $this->geoIp->getCountryCode('::1'));
    }

    #[DataProvider('provideInvalidData')]
    public function testGeoIpHandlesInvalidGeoIpDate(mixed $data): void
    {
        $this->reader->method('get')->willReturn($data);
        $this->assertNull($this->geoIp->getCountryCode('::1'));
    }

    public function testGeoIpReturnsNullOnError(): void
    {
        $this->reader->method('get')->willThrowException(new \Exception());
        $this->assertNull($this->geoIp->getCountryCode('foo'));
    }

    public function testGeoIpLogsErrors(): void
    {
        $this->reader->method('get')->willThrowException(new \Exception(':-('));
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo(':-('),
                $this->arrayHasKey('exception')
            );
        $this->geoIp->getCountryCode('foo');
    }

    /**
     * @return iterable<array<mixed>>
     */
    public static function provideInvalidData(): iterable
    {
        yield [null];
        yield ['[]'];
        yield [['country']];
        yield [['country' => null]];
        yield [['country' => '[]']];
        yield [['country' => []]];
        yield [['country' => ['iso_code']]];
        yield [['country' => ['iso_code' => null]]];
        yield [['country' => ['iso_code' => []]]];
    }
}
