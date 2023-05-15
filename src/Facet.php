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

    /**
     * @var string[]
     */
    private array $operationListForSelectMode = [
        CompareRuleInterface::IN,
        CompareRuleInterface::EQUAL
    ];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Используется для расчета не выбранных значений из текущего фильтра,
     * определяются допустимые операции для данного режима
     * @param string ...$compareOperationList
     * @return void
     */
    public function setOperationForSelectMode(string ...$compareOperationList): void
    {
        $this->operationListForSelectMode = $compareOperationList;
    }

    public function disableSelectMode(): void
    {
        $this->operationListForSelectMode = [];
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
        $resultItemIdList = [];
        $facetResult = $this->getFacetResultForSelectMode(...$compareRuleList);
        $itemIdList = $this->getItemIdListByCompareRuleList(...$compareRuleList);
        if (empty($itemIdList) && empty($facetResult->propertyResultList)) {
            return $facetResult;
        }

        foreach ($this->data as $propertyName => $valuesData) {
            if (!empty($facetResult->getResultByProperty($propertyName))) {
                continue;
            }

            $facetPropertyResult = $this->createPropertyResult($propertyName);
            foreach ($valuesData as $value => $currentItemIdList) {
                $intersectResult = array_intersect_key($itemIdList, $currentItemIdList);
                if (!empty($intersectResult)) {
                    $this->addPropertyValueToPropertyResult($value, $intersectResult, $facetPropertyResult);
                    $resultItemIdList = array_merge($resultItemIdList, $intersectResult);
                }
            }

            if (!empty($facetPropertyResult->valueResultList)) {
                $facetResult->propertyResultList[$propertyName] = $facetPropertyResult;
            }
        }

        $facetResult->pkList = $resultItemIdList;
        return $facetResult;
    }

    /**
     * @throws Exception
     */
    private function getFacetResultForSelectMode(CompareRuleInterface ...$compareRuleList): FacetResult
    {
        $resultItemIdList = [];
        $facetResult = new FacetResult();
        if ($this->selectModelIsDisabled()) {
            return $facetResult;
        }

        foreach ($compareRuleList as $compareRule) {
            $propertyName = $compareRule->getKey();
            if (!$this->isKeyHasOnlySelectOperationsInCompareRuleList($propertyName, ...$compareRuleList)) {
                continue;
            }

            $actualCompareRuleList = $this->getCompareRuleListWithExcludeRuleByKeyAndOperation(
                $propertyName,
                $this->getSelectOperations(),
                ...$compareRuleList
            );

            $isEmptyCompareList = empty($actualCompareRuleList);
            $itemIdList = $isEmptyCompareList ? [] : $this->getItemIdListByCompareRuleList(...$actualCompareRuleList);
            if (!$isEmptyCompareList && empty($itemIdList)) {
                return new FacetResult();
            }

            $facetPropertyResult = $this->createPropertyResult($propertyName);
            $propertyValues = $this->data[$propertyName] ?? [];
            foreach ($propertyValues as $value => $currentItemIdList) {
                $intersectResult = $isEmptyCompareList ?
                    $currentItemIdList :
                    array_intersect_key($itemIdList, $currentItemIdList);
                if (!empty($intersectResult)) {
                    $this->addPropertyValueToPropertyResult($value, $intersectResult, $facetPropertyResult);
                    $resultItemIdList = array_merge($resultItemIdList, $intersectResult);
                }
            }

            if (!empty($facetPropertyResult->valueResultList)) {
                $facetResult->propertyResultList[$propertyName] = $facetPropertyResult;
            }
        }

        $facetResult->pkList = $resultItemIdList;
        return $facetResult;
    }

    private function createPropertyResult(string $propertyName): FacetPropertyResult
    {
        $facetPropertyResult = new FacetPropertyResult();
        $facetPropertyResult->name = $propertyName;
        return $facetPropertyResult;
    }

    /**
     * @param mixed $value
     * @param array $itemIdList
     * @param FacetPropertyResult $facetPropertyResult
     * @return void
     */
    private function addPropertyValueToPropertyResult(
        $value,
        array $itemIdList,
        FacetPropertyResult $facetPropertyResult
    ): void {
        $facetPropertyResult->valueResultList[$value] = $this->createPropertyValueResult($value, $itemIdList);
    }

    /**
     * @param mixed $value
     * @param array $itemIdList
     * @return FacetPropertyValueResult
     */
    private function createPropertyValueResult($value, array $itemIdList): FacetPropertyValueResult
    {
        $facetPropertyValueResult = new FacetPropertyValueResult();
        $facetPropertyValueResult->value = $value;
        $facetPropertyValueResult->itemIdList = $itemIdList;
        $facetPropertyValueResult->itemsCount = count($itemIdList);
        return $facetPropertyValueResult;
    }

    /**
     * @throws Exception
     */
    public function getActualPropertyValuesForCompareRuleList(CompareRuleInterface ...$compareRuleList): array
    {
        $resultItemIdList = [];
        $result = $this->getActualPropertyValuesForSelectMode(...$compareRuleList);
        $itemIdList = $this->getItemIdListByCompareRuleList(...$compareRuleList);
        if (empty($itemIdList) && empty($result)) {
            return [];
        }

        foreach ($this->data as $propertyName => $valuesData) {
            if (array_key_exists($propertyName, $result)) {
                continue;
            }

            foreach ($valuesData as $value => $currentItemIdList) {
                $intersectResult = array_intersect_key($itemIdList, $currentItemIdList);
                if (!empty($intersectResult)) {
                    $result[$propertyName][$value] = count($intersectResult);
                    $resultItemIdList = array_merge($resultItemIdList, $intersectResult);
                }
            }
        }

        $result['pk'] = $resultItemIdList;
        return $result;
    }

    private function getActualPropertyValuesForSelectMode(CompareRuleInterface ...$compareRuleList): array
    {
        if ($this->selectModelIsDisabled()) {
            return [];
        }

        $result = [];
        $resultItemIdList = [];
        foreach ($compareRuleList as $compareRule) {
            $propertyName = $compareRule->getKey();
            if (!$this->isKeyHasOnlySelectOperationsInCompareRuleList($propertyName, ...$compareRuleList)) {
                continue;
            }

            $actualCompareRuleList = $this->getCompareRuleListWithExcludeRuleByKeyAndOperation(
                $propertyName,
                $this->getSelectOperations(),
                ...$compareRuleList
            );

            $isEmptyCompareList = empty($actualCompareRuleList);
            $itemIdList = $isEmptyCompareList ? [] : $this->getItemIdListByCompareRuleList(...$actualCompareRuleList);
            if (!$isEmptyCompareList && empty($itemIdList)) {
                return [];
            }

            $propertyValues = $this->data[$propertyName] ?? [];
            foreach ($propertyValues as $value => $currentItemIdList) {
                $intersectResult = $isEmptyCompareList ?
                    $currentItemIdList :
                    array_intersect_key($itemIdList, $currentItemIdList);
                if (!empty($intersectResult)) {
                    $result[$propertyName][$value] = count($intersectResult);
                    $resultItemIdList = array_merge($resultItemIdList, $intersectResult);
                }
            }
        }

        $result['pk'] = $resultItemIdList;
        return $result;
    }

    private function selectModelIsDisabled(): bool
    {
        return empty($this->operationListForSelectMode);
    }

    private function isKeyHasOnlySelectOperationsInCompareRuleList(
        string $key,
        CompareRuleInterface ...$compareRuleList
    ): bool {
        $result = false;
        foreach ($compareRuleList as $compareRule) {
            if ($compareRule->getKey() !== $key) {
                continue;
            }

            $result = in_array($compareRule->getOperation(), $this->getSelectOperations());
            if (!$result) {
                return false;
            }
        }

        return $result;
    }

    private function getSelectOperations(): array
    {
        return $this->operationListForSelectMode;
    }

    private function getCompareRuleListWithExcludeRuleByKeyAndOperation(
        string $key,
        array $operationList,
        CompareRuleInterface ...$compareRuleList
    ): array {
        $newCompareRuleList = [];
        foreach ($compareRuleList as $compareRule) {
            if ($compareRule->getKey() === $key && in_array($compareRule->getOperation(), $operationList)) {
                continue;
            }
            $newCompareRuleList[] = $compareRule;
        }
        return $newCompareRuleList;
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
            $resultItemIdList = $this->getItemIdListByCompareRule($compareRule, $resultItemIdList);
            if (empty($resultItemIdList)) {
                return [];
            }
        }

        return $resultItemIdList;
    }

    /**
     * @throws Exception
     */
    private function getItemIdListByCompareRule(CompareRuleInterface $compareRule, array $currentIdList = []): array
    {
        $itemIdList = $this->getItemIdListForProperty($compareRule);
        if (empty($itemIdList)) {
            return [];
        }

        if (empty($currentIdList)) {
            return $itemIdList;
        }

        return array_intersect_key($currentIdList, $itemIdList);
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

            if (empty($resultItemIdList)) {
                $resultItemIdList = $andItemIdList;
                continue;
            }

            $resultItemIdList = array_intersect_key($resultItemIdList, $andItemIdList);
            if (empty($resultItemIdList)) {
                return [];
            }
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

    /**
     * @param mixed $itemId
     * @param bool $strictMode
     * @return void
     */
    public function clearFacetForItemId($itemId, bool $strictMode = false): void
    {
        foreach ($this->data as &$propertyValues) {
            foreach ($propertyValues as &$itemIdList) {
                foreach ($itemIdList as $key => $currentItemId) {
                    if ($strictMode && $currentItemId === $itemId) {
                        unset($itemIdList[$key]);
                    } elseif ($currentItemId == $itemId) {
                        unset($itemIdList[$key]);
                    }
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

        $this->data[$propertyName][$value]['_' . (string)$itemId] = $itemId;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
