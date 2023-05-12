<?php

namespace Faceted\Search;

use Data\Provider\Providers\ArrayDataProvider;
use PHPUnit\Framework\TestCase;

class FacetMangerTest extends TestCase
{
    private const FACET_DATA = [
        'color' => [
            'white' => ['_1' => 1, '_2' => 2, '_3' => 3],
            'blue' => ['_4' => 4, '_5' => 5, '_6' => 6],
            'red' => ['_7' => 7, '_8' => 8, '_9' => 9, '_10' => 10],
        ],
        'size' => [
            'S' => ['_1' => 1, '_2' => 2, '_3' => 3],
            'M' => ['_4' => 4, '_6' => 6, '_7' => 7],
            'L' => ['_8' => 8, '_9' => 9, '_10' => 10, '_5' => 5],
        ],
        'type' => [
            'hat' => ['_2' => 2, '_6' => 6, '_9' => 9],
            'pants' => ['_1' => 1, '_4' => 4, '_7' => 7],
            't-shirt' => ['_3' => 3, '_5' => 5, '_10' => 10, '_8' => 8],
        ],
        'gender' => [
            'male' => ['_1' => 1, '_6' => 6, '_10' => 10, '_2' => 2, '_5' => 5],
            'female' => ['_4' => 4, '_8' => 8, '_9' => 9, '_7' => 7, '_3' => 3],
        ]
    ];

    public function testGetFacetById()
    {
        $data = [['id' => 'cloth', 'data' => json_encode(static::FACET_DATA)]];
        $dataProvider = new ArrayDataProvider($data, 'id');
        $facetManger = new FacetManger($dataProvider);
        $facet = $facetManger->getFacetById('cloth');
        $this->assertEquals(static::FACET_DATA, $facet->jsonSerialize());
    }

    public function testSaveFacetById()
    {
        $facet = new Facet(static::FACET_DATA);
        $dataProvider = new ArrayDataProvider([]);
        $facetManger = new FacetManger($dataProvider);
        $facetManger->saveFacetById('cloth', $facet);

        $facetFromManager = $facetManger->getFacetById('cloth');
        $this->assertEquals(static::FACET_DATA, $facetFromManager->jsonSerialize());
    }

    public function testRemoveFacetById()
    {
        $data = [['id' => 'cloth', 'data' => json_encode(static::FACET_DATA)]];
        $dataProvider = new ArrayDataProvider($data, 'id');
        $facetManger = new FacetManger($dataProvider);
        $facet = $facetManger->getFacetById('cloth');
        $this->assertEquals(static::FACET_DATA, $facet->jsonSerialize());

        $facetManger->removeFacetById('cloth');
        $this->assertNull($facetManger->getFacetById('cloth'));
    }
}
