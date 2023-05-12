<?php

namespace Faceted\Search;

use Data\Provider\Providers\ArrayDataProvider;
use Faceted\Search\Interfaces\FacetExtraDataInterface;
use PHPUnit\Framework\TestCase;

class FacetBuilderTest extends TestCase
{
    private const PROVIDER_DATA = [
        ['id' => 1, 'color' => 'white', 'size' => 'S', 'type' => 'pants', 'gender' => 'male'],
        ['id' => 2, 'color' => 'white', 'size' => 'S', 'type' => 'hat', 'gender' => 'male'],
        ['id' => 3, 'color' => 'white', 'size' => 'S', 'type' => 't-shirt', 'gender' => 'female'],
        ['id' => 4, 'color' => 'blue', 'size' => 'M', 'type' => 'pants', 'gender' => 'female'],
        ['id' => 5, 'color' => 'blue', 'size' => 'L', 'type' => 't-shirt', 'gender' => 'male'],
        ['id' => 6, 'color' => 'blue', 'size' => 'M', 'type' => 'hat', 'gender' => 'male'],
        ['id' => 7, 'color' => 'red', 'size' => 'M', 'type' => 'pants', 'gender' => 'female'],
        ['id' => 8, 'color' => 'red', 'size' => 'L', 'type' => 't-shirt', 'gender' => 'female'],
        ['id' => 9, 'color' => 'red', 'size' => 'L', 'type' => 'hat', 'gender' => 'female'],
        ['id' => 10, 'color' => 'red', 'size' => 'L', 'type' => 't-shirt', 'gender' => 'male'],
    ];

    public function testBuild()
    {
        $dataProvider = new ArrayDataProvider(static::PROVIDER_DATA, 'id');
        $facet = FacetBuilder::init($dataProvider)
            ->setItemIdKey('id')
            ->registerProperty('color')
            ->registerProperty('size')
            ->registerProperty('type')
            ->registerProperty('gender')
            ->build();

        $this->assertEquals([
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
        ], $facet->jsonSerialize());

        $facet = FacetBuilder::init($dataProvider)
            ->setItemIdKey('id')
            ->setLimitByStepIndex(3)
            ->registerProperty('color')
            ->registerProperty('size')
            ->registerProperty('type')
            ->registerProperty('gender')
            ->build();

        $this->assertEquals([
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
        ], $facet->jsonSerialize());

        $facet = FacetBuilder::init($dataProvider)
            ->setItemIdKey('id')
            ->registerProperty('color')
            ->build();

        $this->assertEquals([
            'color' => [
                'white' => ['_1' => 1, '_2' => 2, '_3' => 3],
                'blue' => ['_4' => 4, '_5' => 5, '_6' => 6],
                'red' => ['_7' => 7, '_8' => 8, '_9' => 9, '_10' => 10],
            ]
        ], $facet->jsonSerialize());

        $facet = FacetBuilder::init($dataProvider)
            ->setItemIdKey('id')
            ->registerProperty('customKey', 'color')
            ->build();

        $this->assertEquals([
            'customKey' => [
                'white' => ['_1' => 1, '_2' => 2, '_3' => 3],
                'blue' => ['_4' => 4, '_5' => 5, '_6' => 6],
                'red' => ['_7' => 7, '_8' => 8, '_9' => 9, '_10' => 10],
            ]
        ], $facet->jsonSerialize());

        $facetExtraData = new FacetExtraData();
        $facetExtraDataProcessor = new FacetExtraDataProcessor($facetExtraData);
        $facetExtraDataProcessor->registerHandler(function (
            array $data,
            FacetExtraDataInterface $facetExtraData
        ): void {
            $color = $data['color'] ?? '';
            if (!empty($color)) {
                $facetExtraData->setDataForPropertyValue('color', $color, "Цвет: $color");
            }
        });

        $colorMap = [
            'white' => 'белый',
            'blue' => 'синий',
            'red' => 'красный',
            'undefined' => 'не определен',
        ];

        $facet = FacetBuilder::init($dataProvider)
            ->setItemIdKey('id')
            ->setFacetExtraDataProcessor($facetExtraDataProcessor)
            ->registerProperty('цвет', null, function (array $itemData) use ($colorMap): string {
                $color = $itemData['color'] ?? 'undefined';
                return $colorMap[$color] ?? $color;
            })
            ->build();

        $this->assertEquals([
            'цвет' => [
                'белый' => ['_1' => 1, '_2' => 2, '_3' => 3],
                'синий' => ['_4' => 4, '_5' => 5, '_6' => 6],
                'красный' => ['_7' => 7, '_8' => 8, '_9' => 9, '_10' => 10],
            ]
        ], $facet->jsonSerialize());

        $this->assertEquals([
            'color' => [
                'white' => 'Цвет: white',
                'blue' => 'Цвет: blue',
                'red' => 'Цвет: red',
            ]
        ], $facetExtraData->jsonSerialize());
    }
}
