<?php

namespace Faceted\Search;

use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\QueryCriteria;
use Exception;
use Faceted\Search\Result\FacetResult;
use PHPUnit\Framework\TestCase;

class FacetTest extends TestCase
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

    public function testJsonSerialize()
    {
        $facet = new Facet(static::FACET_DATA);
        $this->assertEquals(static::FACET_DATA, $facet->jsonSerialize());
    }

    /**
     * @throws Exception
     */
    public function testGetActualPropertyValuesForCompareRuleList()
    {
        $facet = new Facet(static::FACET_DATA);

        $query = new QueryCriteria();
        $query->addCriteria('gender', CompareRuleInterface::EQUAL, 'female');
        $dataResult = $facet->getActualPropertyValuesForCompareRuleList(...$query->getCriteriaList());
        $this->assertEquals([
            'color' => [
                'white' => 1,
                'blue' => 1,
                'red' => 3,
            ],
            'size' => [
                'S' => 1,
                'M' => 2,
                'L' => 2,
            ],
            'type' => [
                'hat' => 1,
                'pants' => 2,
                't-shirt' => 2,
            ],
            'gender' => [
                'male' => 5,
                'female' => 5,
            ]
        ], $dataResult);

        $facet->disableSelectMode();
        $dataResult = $facet->getActualPropertyValuesForCompareRuleList(...$query->getCriteriaList());
        $this->assertEquals([
            'color' => [
                'white' => 1,
                'blue' => 1,
                'red' => 3,
            ],
            'size' => [
                'S' => 1,
                'M' => 2,
                'L' => 2,
            ],
            'type' => [
                'hat' => 1,
                'pants' => 2,
                't-shirt' => 2,
            ],
            'gender' => [
                'female' => 5,
            ]
        ], $dataResult);


        $query->addCriteria('size', CompareRuleInterface::IN, ['S', 'M']);
        $facet->setOperationForSelectMode(CompareRuleInterface::EQUAL, CompareRuleInterface::IN);
        $dataResult = $facet->getActualPropertyValuesForCompareRuleList(...$query->getCriteriaList());
        $this->assertEquals([
            'color' => [
                'white' => 1,
                'blue' => 1,
                'red' => 1,
            ],
            'size' => [
                'S' => 1,
                'M' => 2,
                'L' => 2,
            ],
            'type' => [
                'pants' => 2,
                't-shirt' => 1,
            ],
            'gender' => [
                'male' => 3,
                'female' => 3,
            ]
        ], $dataResult);

        $facet->disableSelectMode();
        $dataResult = $facet->getActualPropertyValuesForCompareRuleList(...$query->getCriteriaList());
        $this->assertEquals([
            'color' => [
                'white' => 1,
                'blue' => 1,
                'red' => 1,
            ],
            'size' => [
                'S' => 1,
                'M' => 2,
            ],
            'type' => [
                'pants' => 2,
                't-shirt' => 1,
            ],
            'gender' => [
                'female' => 3,
            ]
        ], $dataResult);
    }

    public function testGetValuesByProperty()
    {
        $facet = new Facet(static::FACET_DATA);
        $this->assertEquals([
            'white',
            'blue',
            'red',
        ], $facet->getValuesByProperty('color'));

        $this->assertEquals([
            'S',
            'M',
            'L',
        ], $facet->getValuesByProperty('size'));

        $this->assertEquals([
            'hat',
            'pants',
            't-shirt',
        ], $facet->getValuesByProperty('type'));

        $this->assertEquals([
            'male',
            'female',
        ], $facet->getValuesByProperty('gender'));
    }

    /**
     * @throws Exception
     */
    public function testGetFacetResultForCompareRuleList()
    {
        $facet = new Facet(static::FACET_DATA);

        $query = new QueryCriteria();
        $query->addCriteria('gender', CompareRuleInterface::EQUAL, 'female');
        $facetResult = $facet->getFacetResultForCompareRuleList(...$query->getCriteriaList());

        $this->assertValuePropertyResult($facetResult, 'color', 'white', ['_3' => 3]);
        $this->assertValuePropertyResult($facetResult, 'color', 'blue', ['_4' => 4]);
        $this->assertValuePropertyResult(
            $facetResult,
            'color',
            'red',
            ['_7' => 7, '_8' => 8, '_9' => 9]
        );

        $this->assertValuePropertyResult($facetResult, 'size', 'S', ['_3' => 3]);
        $this->assertValuePropertyResult($facetResult, 'size', 'M', ['_4' => 4, '_7' => 7]);
        $this->assertValuePropertyResult($facetResult, 'size', 'L', ['_8' => 8, '_9' => 9]);

        $this->assertValuePropertyResult($facetResult, 'type', 'hat', ['_9' => 9]);
        $this->assertValuePropertyResult($facetResult, 'type', 'pants', ['_4' => 4, '_7' => 7]);
        $this->assertValuePropertyResult($facetResult, 'type', 't-shirt', ['_3' => 3, '_8' => 8]);

        $this->assertValuePropertyResult(
            $facetResult,
            'gender',
            'male',
            ['_1' => 1, '_6' => 6, '_10' => 10, '_2' => 2, '_5' => 5]
        );
        $this->assertValuePropertyResult(
            $facetResult,
            'gender',
            'female',
            ['_4' => 4, '_8' => 8, '_9' => 9, '_7' => 7, '_3' => 3]
        );

        $facet->disableSelectMode();
        $facetResult = $facet->getFacetResultForCompareRuleList(...$query->getCriteriaList());

        $this->assertValuePropertyResult($facetResult, 'color', 'white', ['_3' => 3]);
        $this->assertValuePropertyResult($facetResult, 'color', 'blue', ['_4' => 4]);
        $this->assertValuePropertyResult(
            $facetResult,
            'color',
            'red',
            ['_7' => 7, '_8' => 8, '_9' => 9]
        );

        $this->assertValuePropertyResult($facetResult, 'size', 'S', ['_3' => 3]);
        $this->assertValuePropertyResult($facetResult, 'size', 'M', ['_4' => 4, '_7' => 7]);
        $this->assertValuePropertyResult($facetResult, 'size', 'L', ['_8' => 8, '_9' => 9]);

        $this->assertValuePropertyResult($facetResult, 'type', 'hat', ['_9' => 9]);
        $this->assertValuePropertyResult($facetResult, 'type', 'pants', ['_4' => 4, '_7' => 7]);
        $this->assertValuePropertyResult($facetResult, 'type', 't-shirt', ['_3' => 3, '_8' => 8]);

        $this->assertValuePropertyResultIsEmpty($facetResult, 'gender', 'male');
        $this->assertValuePropertyResult(
            $facetResult,
            'gender',
            'female',
            ['_4' => 4, '_8' => 8, '_9' => 9, '_7' => 7, '_3' => 3]
        );

        $query->addCriteria('size', CompareRuleInterface::IN, ['S', 'M']);
        $facet->setOperationForSelectMode(CompareRuleInterface::EQUAL, CompareRuleInterface::IN);
        $facetResult = $facet->getFacetResultForCompareRuleList(...$query->getCriteriaList());

        $this->assertValuePropertyResult($facetResult, 'color', 'white', ['_3' => 3]);
        $this->assertValuePropertyResult($facetResult, 'color', 'blue', ['_4' => 4]);
        $this->assertValuePropertyResult($facetResult, 'color', 'red', ['_7' => 7]);

        $this->assertValuePropertyResult($facetResult, 'size', 'S', ['_3' => 3]);
        $this->assertValuePropertyResult($facetResult, 'size', 'M', ['_4' => 4, '_7' => 7]);
        $this->assertValuePropertyResult($facetResult, 'size', 'L', ['_8' => 8, '_9' => 9]);

        $this->assertValuePropertyResultIsEmpty($facetResult, 'type', 'hat');
        $this->assertValuePropertyResult($facetResult, 'type', 'pants', ['_4' => 4, '_7' => 7]);
        $this->assertValuePropertyResult($facetResult, 'type', 't-shirt', ['_3' => 3]);

        $this->assertValuePropertyResult(
            $facetResult,
            'gender',
            'male',
            ['_1' => 1, '_6' => 6, '_2' => 2]
        );
        $this->assertValuePropertyResult(
            $facetResult,
            'gender',
            'female',
            ['_4' => 4, '_7' => 7, '_3' => 3]
        );

        $facet->disableSelectMode();
        $facetResult = $facet->getFacetResultForCompareRuleList(...$query->getCriteriaList());

        $this->assertValuePropertyResult($facetResult, 'color', 'white', ['_3' => 3]);
        $this->assertValuePropertyResult($facetResult, 'color', 'blue', ['_4' => 4]);
        $this->assertValuePropertyResult($facetResult, 'color', 'red', ['_7' => 7]);

        $this->assertValuePropertyResult($facetResult, 'size', 'S', ['_3' => 3]);
        $this->assertValuePropertyResult($facetResult, 'size', 'M', ['_4' => 4, '_7' => 7]);
        $this->assertValuePropertyResultIsEmpty($facetResult, 'size', 'L');

        $this->assertValuePropertyResultIsEmpty($facetResult, 'type', 'hat');
        $this->assertValuePropertyResult($facetResult, 'type', 'pants', ['_4' => 4, '_7' => 7]);
        $this->assertValuePropertyResult($facetResult, 'type', 't-shirt', ['_3' => 3]);

        $this->assertValuePropertyResultIsEmpty($facetResult, 'gender', 'male');
        $this->assertValuePropertyResult(
            $facetResult,
            'gender',
            'female',
            ['_4' => 4, '_7' => 7, '_3' => 3]
        );
    }

    /**
     * @param FacetResult $facetResult
     * @param string $propertyName
     * @param mixed $value
     * @param array $itemIdList
     * @return void
     */
    private function assertValuePropertyResult(
        FacetResult $facetResult,
        string $propertyName,
        $value,
        array $itemIdList
    ): void {
        $propertyResult = $facetResult->getResultByProperty($propertyName);
        $valuePropertyResult = $propertyResult->getResultByValue($value);
        $this->assertEquals(count($itemIdList), $valuePropertyResult->itemsCount);
        $this->assertEquals($itemIdList, $valuePropertyResult->itemIdList);
    }

    /**
     * @param FacetResult $facetResult
     * @param string $propertyName
     * @param mixed $value
     * @return void
     */
    private function assertValuePropertyResultIsEmpty(
        FacetResult $facetResult,
        string $propertyName,
        $value
    ): void {
        $propertyResult = $facetResult->getResultByProperty($propertyName);
        $valuePropertyResult = $propertyResult->getResultByValue($value);
        $this->assertNull($valuePropertyResult);
    }

    /**
     * @throws Exception
     */
    public function testGetCountByCompareRuleList()
    {
        $facet = new Facet(static::FACET_DATA);

        $query = new QueryCriteria();
        $query->addCriteria('gender', CompareRuleInterface::EQUAL, 'female');
        $itemsCount = $facet->getCountByCompareRuleList(...$query->getCriteriaList());
        $this->assertEquals(5, $itemsCount);

        $query->addCriteria('size', CompareRuleInterface::IN, ['S', 'M']);
        $itemsCount = $facet->getCountByCompareRuleList(...$query->getCriteriaList());
        $this->assertEquals(3, $itemsCount);
    }

    public function testClearFacetForItemId()
    {
        $facet = new Facet(static::FACET_DATA);
        $facet->clearFacetForItemId(1);
        $this->assertEquals([
            'color' => [
                'white' => ['_2' => 2, '_3' => 3],
                'blue' => ['_4' => 4, '_5' => 5, '_6' => 6],
                'red' => ['_7' => 7, '_8' => 8, '_9' => 9, '_10' => 10],
            ],
            'size' => [
                'S' => ['_2' => 2, '_3' => 3],
                'M' => ['_4' => 4, '_6' => 6, '_7' => 7],
                'L' => ['_8' => 8, '_9' => 9, '_10' => 10, '_5' => 5],
            ],
            'type' => [
                'hat' => ['_2' => 2, '_6' => 6, '_9' => 9],
                'pants' => ['_4' => 4, '_7' => 7],
                't-shirt' => ['_3' => 3, '_5' => 5, '_10' => 10, '_8' => 8],
            ],
            'gender' => [
                'male' => ['_6' => 6, '_10' => 10, '_2' => 2, '_5' => 5],
                'female' => ['_4' => 4, '_8' => 8, '_9' => 9, '_7' => 7, '_3' => 3],
            ]
        ], $facet->jsonSerialize());

        $facet->clearFacetForItemId(2);
        $this->assertEquals([
            'color' => [
                'white' => ['_3' => 3],
                'blue' => ['_4' => 4, '_5' => 5, '_6' => 6],
                'red' => ['_7' => 7, '_8' => 8, '_9' => 9, '_10' => 10],
            ],
            'size' => [
                'S' => ['_3' => 3],
                'M' => ['_4' => 4, '_6' => 6, '_7' => 7],
                'L' => ['_8' => 8, '_9' => 9, '_10' => 10, '_5' => 5],
            ],
            'type' => [
                'hat' => ['_6' => 6, '_9' => 9],
                'pants' => ['_4' => 4, '_7' => 7],
                't-shirt' => ['_3' => 3, '_5' => 5, '_10' => 10, '_8' => 8],
            ],
            'gender' => [
                'male' => ['_6' => 6, '_10' => 10, '_5' => 5],
                'female' => ['_4' => 4, '_8' => 8, '_9' => 9, '_7' => 7, '_3' => 3],
            ]
        ], $facet->jsonSerialize());

        $facet->clearFacetForItemId(7);
        $this->assertEquals([
            'color' => [
                'white' => ['_3' => 3],
                'blue' => ['_4' => 4, '_5' => 5, '_6' => 6],
                'red' => ['_8' => 8, '_9' => 9, '_10' => 10],
            ],
            'size' => [
                'S' => ['_3' => 3],
                'M' => ['_4' => 4, '_6' => 6],
                'L' => ['_8' => 8, '_9' => 9, '_10' => 10, '_5' => 5],
            ],
            'type' => [
                'hat' => ['_6' => 6, '_9' => 9],
                'pants' => ['_4' => 4],
                't-shirt' => ['_3' => 3, '_5' => 5, '_10' => 10, '_8' => 8],
            ],
            'gender' => [
                'male' => ['_6' => 6, '_10' => 10, '_5' => 5],
                'female' => ['_4' => 4, '_8' => 8, '_9' => 9, '_3' => 3],
            ]
        ], $facet->jsonSerialize());
    }

    public function testAddItemIdForValueByProperty()
    {
        $facet = new Facet(static::FACET_DATA);
        $facet->addItemIdForValueByProperty('color', 'blue', 11);
        $facet->addItemIdForValueByProperty('size', 'L', 11);
        $facet->addItemIdForValueByProperty('type', 'hat', 11);
        $facet->addItemIdForValueByProperty('gender', 'female', 11);
        $this->assertEquals([
            'color' => [
                'white' => ['_1' => 1, '_2' => 2, '_3' => 3],
                'blue' => ['_4' => 4, '_5' => 5, '_6' => 6, '_11' => 11],
                'red' => ['_7' => 7, '_8' => 8, '_9' => 9, '_10' => 10],
            ],
            'size' => [
                'S' => ['_1' => 1, '_2' => 2, '_3' => 3],
                'M' => ['_4' => 4, '_6' => 6, '_7' => 7],
                'L' => ['_8' => 8, '_9' => 9, '_10' => 10, '_5' => 5, '_11' => 11],
            ],
            'type' => [
                'hat' => ['_2' => 2, '_6' => 6, '_9' => 9, '_11' => 11],
                'pants' => ['_1' => 1, '_4' => 4, '_7' => 7],
                't-shirt' => ['_3' => 3, '_5' => 5, '_10' => 10, '_8' => 8],
            ],
            'gender' => [
                'male' => ['_1' => 1, '_6' => 6, '_10' => 10, '_2' => 2, '_5' => 5],
                'female' => ['_4' => 4, '_8' => 8, '_9' => 9, '_7' => 7, '_3' => 3, '_11' => 11],
            ]
        ], $facet->jsonSerialize());
    }

    /**
     * @throws Exception
     */
    public function testGetItemIdListByCompareRuleList()
    {
        $facet = new Facet(static::FACET_DATA);

        $query = new QueryCriteria();
        $query->addCriteria('gender', CompareRuleInterface::EQUAL, 'female');
        $itemIdList = $facet->getItemIdListByCompareRuleList(...$query->getCriteriaList());
        $this->assertEquals(['_4' => 4, '_8' => 8, '_9' => 9, '_7' => 7, '_3' => 3], $itemIdList);

        $query->addCriteria('size', CompareRuleInterface::IN, ['S', 'M']);
        $itemIdList = $facet->getItemIdListByCompareRuleList(...$query->getCriteriaList());
        $this->assertEquals(['_4' => 4, '_7' => 7, '_3' => 3], $itemIdList);
    }
}
