<?php

namespace Faceted\Search;

use PHPUnit\Framework\TestCase;

class FacetExtraDataTest extends TestCase
{
    private const FACET_EXTRA_DATA = [
        'color' => [
            'white' => 'some data',
            'red' => [1, 2, 3]
        ]
    ];

    public function testGetDataForPropertyValue()
    {
        $facetExtraData = new FacetExtraData(static::FACET_EXTRA_DATA);
        $this->assertEquals(
            'some data',
            $facetExtraData->getDataForPropertyValue('color', 'white')
        );
        $this->assertEquals(
            [1, 2, 3],
            $facetExtraData->getDataForPropertyValue('color', 'red')
        );
        $this->assertNull(
            $facetExtraData->getDataForPropertyValue('color', 'blue')
        );

    }

    public function testSetDataForPropertyValue()
    {
        $facetExtraData = new FacetExtraData(static::FACET_EXTRA_DATA);
        $facetExtraData->setDataForPropertyValue('size', 'S', [4, 3, 2]);
        $this->assertEquals([
            'color' => [
                'white' => 'some data',
                'red' => [1, 2, 3]
            ],
            'size' => [
                'S' => [4, 3, 2]
            ]
        ], $facetExtraData->jsonSerialize());
    }

    public function testJsonSerialize()
    {
        $facetExtraData = new FacetExtraData(static::FACET_EXTRA_DATA);
        $this->assertEquals(static::FACET_EXTRA_DATA, $facetExtraData->jsonSerialize());
    }

    public function testHasDataForPropertyValue()
    {
        $facetExtraData = new FacetExtraData(static::FACET_EXTRA_DATA);
        $this->assertTrue($facetExtraData->hasDataForPropertyValue('color', 'white'));
        $this->assertTrue($facetExtraData->hasDataForPropertyValue('color', 'red'));
        $this->assertFalse($facetExtraData->hasDataForPropertyValue('color', 'blue'));
    }

    public function testRemoveDataForPropertyValue()
    {
        $facetExtraData = new FacetExtraData(static::FACET_EXTRA_DATA);
        $facetExtraData->removeDataForPropertyValue('color', 'red');
        $this->assertEquals([
            'color' => [
                'white' => 'some data',
            ]
        ], $facetExtraData->jsonSerialize());
    }
}
