<?php

namespace Faceted\Search\Interfaces;

use Data\Provider\Interfaces\CompareRuleInterface;
use Faceted\Search\Result\FacetResult;
use JsonSerializable;

interface FacetInterface extends JsonSerializable
{
    public function getValuesByProperty(string $propertyName): array;

    public function getCountByCompareRuleList(CompareRuleInterface ...$compareRuleList): int;

    public function getItemIdListByCompareRuleList(CompareRuleInterface ...$compareRuleList): array;

    /**
     * @param CompareRuleInterface ...$compareRuleList
     * @return array<string, array>
     */
    public function getActualPropertyValuesForCompareRuleList(CompareRuleInterface ...$compareRuleList): array;

    /**
     * @param CompareRuleInterface ...$compareRuleList
     * @return array<string, array>
     */
    public function getActualPropertyValuesForCompareRuleListWithCount(CompareRuleInterface ...$compareRuleList): array;

    public function getFacetResultForCompareRuleList(CompareRuleInterface ...$compareRuleList): FacetResult;

    /**
     * @param mixed $itemId
     * @param bool $strictMode
     * @return void
     */
    public function clearFacetForItemId($itemId, bool $strictMode = false): void;

    /**
     * @param string $propertyName
     * @param mixed $value
     * @param mixed $itemId
     * @return void
     */
    public function addItemIdForValueByProperty(string $propertyName, $value, $itemId): void;
}
