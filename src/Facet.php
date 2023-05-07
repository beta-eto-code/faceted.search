<?php

namespace Faceted\Search;

use Data\Provider\AndCompareRuleGroup;
use Data\Provider\CompareRule;
use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\OrCompareRuleGroup;
use Data\Provider\SimpleCompareRule;
use Exception;
use Faceted\Search\Interfaces\FacetInterface;
use Faceted\Search\Result\FacetPropertyResult;
use Faceted\Search\Result\FacetPropertyValueResult;
use Faceted\Search\Result\FacetResult;

class Facet implements FacetInterface
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getValuesByProperty(string $propertyName): array
    {
        $valuesData = (array) ($this->data[$propertyName] ?? []);
        if (empty($valuesData)) {
            return [];
        }

        return array_keys($valuesData);
    }

    /**
     * @throws Exception
     */
    public function getFacetResultForCompareRuleList(CompareRuleInterface ...$compareRuleList): FacetResult
    {
        $facetResult = new FacetResult();
        $itemIdList = $this->getItemIdListByCompareRuleList(...$compareRuleList);
        if (empty($itemIdList)) {
            return $facetResult;
        }

        foreach ($this->data as $propertyName => $valuesData) {
            $facetPropertyResult = new FacetPropertyResult();
            $facetPropertyResult->name = $propertyName;
            foreach ($valuesData as $value => $itemIdList) {
                $intersectResult = array_intersect_key($itemIdList, $itemIdList);
                if (!empty($intersectResult)) {
                    $facetPropertyValueResult = new FacetPropertyValueResult();
                    $facetPropertyValueResult->value = $value;
                    $facetPropertyValueResult->itemIdList = $intersectResult;
                    $facetPropertyResult->valueResultList[$value] = $facetPropertyValueResult;
                }
            }

            if (!empty($facetPropertyResult->valueResultList)) {
                $facetResult->propertyResultList[$propertyName] = $facetPropertyResult;
            }
        }

        return $facetResult;
    }

    /**
     * @throws Exception
     */
    public function getActualPropertyValuesForCompareRuleList(CompareRuleInterface ...$compareRuleList): array
    {
        $itemIdList = $this->getItemIdListByCompareRuleList(...$compareRuleList);
        if (empty($itemIdList)) {
            return [];
        }

        $result = [];
        foreach ($this->data as $propertyName => $valuesData) {
            foreach ($valuesData as $value => $itemIdList) {
                $intersectResult = array_intersect_key($itemIdList, $itemIdList);
                if (!empty($intersectResult)) {
                    $result[$propertyName][$value] = count($intersectResult);
                }
            }
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public function getCountByCompareRuleList(CompareRuleInterface ...$compareRuleList): int
    {
        return count($this->getItemIdListByCompareRuleList(...$compareRuleList));
    }

    /**
     * @param CompareRuleInterface ...$compareRuleList
     * @return array
     * @throws Exception
     */
    public function getItemIdListByCompareRuleList(CompareRuleInterface ...$compareRuleList): array
    {
        $resultItemIdList = [];
        foreach ($compareRuleList as $compareRule) {
            $itemIdList = $this->getItemIdListForProperty($compareRule);
            if (empty($itemIdList)) {
                return [];
            }

            $resultItemIdList = array_intersect_key($resultItemIdList, $itemIdList);
        }
        return $resultItemIdList;
    }

    /**
     * @throws Exception
     */
    private function getItemIdListForProperty(CompareRuleInterface $compareRule): array
    {
        if ($compareRule instanceof AndCompareRuleGroup) {
            return $this->getItemIdListForAndGroup($compareRule);
        }

        if ($compareRule instanceof OrCompareRuleGroup) {
            return $this->getItemIdListForOrGroup($compareRule);
        }

        $propertyName = $compareRule->getKey();
        $valuesData = (array) ($this->data[$propertyName] ?? []);
        if (empty($valuesData)) {
            return [];
        }

        $operation = $compareRule->getOperation();
        $propertyValue = $compareRule->getCompareValue();
        if ($this->isListValuesForCompare($operation, $propertyValue)) {
            $result = [];
            foreach ($propertyValue as $value) {
                $newCompareValue = new SimpleCompareRule($propertyName, $operation, $value);
                $result = array_merge($result, $this->getItemIdListForProperty($newCompareValue));
            }

            return $result;
        }

        if ($compareRule instanceof CompareRule) {
            return $this->getItemIdListForCompareRule($compareRule, $valuesData);
        }

        if ($compareRule instanceof SimpleCompareRule) {
            return $this->getItemIdListForSimpleCompareRule($compareRule, $valuesData);
        }

        throw new Exception('Не поддерживаемый класс сравнения данных ' . get_class($compareRule));
    }

    /**
     * @param string $operation
     * @param mixed $value
     * @return bool
     */
    private function isListValuesForCompare(string $operation, $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return !in_array($operation, [
            CompareRuleInterface::BETWEEN,
            CompareRuleInterface::NOT_BETWEEN,
            CompareRuleInterface::IN,
            CompareRuleInterface::NOT_IN
        ]);
    }

    /**
     * @throws Exception
     */
    private function getItemIdListForAndGroup(AndCompareRuleGroup $compareRuleGroup): array
    {
        $resultItemIdList = [];
        foreach ($compareRuleGroup->getList() as $compareRule) {
            $andItemIdList = $this->getItemIdListForProperty($compareRule);
            if (empty($andItemIdList)) {
                return [];
            }

            $resultItemIdList = array_intersect_key($resultItemIdList, $andItemIdList);
        }
        return $resultItemIdList;
    }

    /**
     * @throws Exception
     */
    private function getItemIdListForOrGroup(OrCompareRuleGroup $compareRuleGroup): array
    {
        $resultItemIdList = [];
        foreach ($compareRuleGroup->getList() as $compareRule) {
            $orItemIdList = $this->getItemIdListForProperty($compareRule);
            if (!empty($orItemIdList)) {
                $resultItemIdList = array_merge($resultItemIdList, $orItemIdList);
            }
        }
        return $resultItemIdList;
    }

    /**
     * @throws Exception
     */
    private function getItemIdListForCompareRule(CompareRule $compareRule, array $valuesData): array
    {
        $itemIdList = $this->getItemIdListForSimpleCompareRule($compareRule, $valuesData);
        $isNotEmpty  = !empty($itemIdList);

        $andList = $compareRule->getAndList();
        if ($isNotEmpty && !empty($andList)) {
            foreach ($andList as $andCompareRule) {
                $andItemIdList = $this->getItemIdListForProperty($andCompareRule);
                if (empty($andItemIdList)) {
                    $itemIdList = [];
                    break;
                } else {
                    $itemIdList = array_intersect_key($itemIdList, $andItemIdList);
                }
            }
        }

        $orList = $compareRule->getOrList();
        if (!empty($orList)) {
            foreach ($andList as $orCompareRule) {
                $orItemIdList = $this->getItemIdListForProperty($orCompareRule);
                if (empty($orItemIdList)) {
                    $itemIdList = array_merge($itemIdList, $orItemIdList);
                }
            }
        }

        return $itemIdList;
    }

    /**
     * @throws Exception
     */
    private function getItemIdListForSimpleCompareRule(SimpleCompareRule $compareRule, array $valuesData): array
    {
        $resultItemIdList = [];
        $propertyName = $compareRule->getKey();
        foreach ($valuesData as $currentPropertyValue => $itemIdList) {
            $isSuccess = $compareRule->assertWithData([$propertyName => $currentPropertyValue]);
            if ($isSuccess) {
                $itemIdList = (array) ($itemIdList ?: []);
                if (empty($itemIdList)) {
                    continue;
                }
                $resultItemIdList = array_merge($resultItemIdList, $itemIdList);
            }
        }

        return $resultItemIdList;
    }

    public function clearFacetForItemId($itemId, bool $strictMode = false): void
    {
        foreach ($this->data as &$propertyValues) {
            foreach ($propertyValues as $key => $currentItemId) {
                if ($strictMode && $currentItemId === $itemId) {
                    unset($propertyValues[$key]);
                } elseif ($currentItemId == $itemId) {
                    unset($propertyValues[$key]);
                }
            }
        }
    }

    /**
     * @param string $propertyName
     * @param mixed $value
     * @param mixed $itemId
     * @return void
     */
    public function addItemIdForValueByProperty(string $propertyName, $value, $itemId): void
    {
        if (!isset($this->data[$propertyName][$value])) {
            $this->data[$propertyName][$value] = [];
        }

        $this->data[$propertyName][$value][(string)$itemId] = $itemId;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
