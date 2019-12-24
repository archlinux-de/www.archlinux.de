<?php

namespace App\Tests\Datatables;

use App\Datatables\DatatablesResponse;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;

class DatatablesNormalizerTest extends KernelTestCase
{
    /** @var Serializer */
    private $serializer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::$container->get('serializer');
    }

    public function testNormalize(): void
    {
        $response = new DatatablesResponse();
        $response->setData(['foo' => 'bar']);
        $response->setDraw(12);
        $response->setRecordsFiltered(34);
        $response->setRecordsTotal(56);

        $recodedResponse = json_decode(
            $this->serializer->serialize($response, 'json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertEquals(
            [
                'draw' => 12,
                'recordsTotal' => 56,
                'recordsFiltered' => 34,
                'data' => [
                    'foo' => 'bar'
                ]
            ],
            $recodedResponse
        );
    }
}
