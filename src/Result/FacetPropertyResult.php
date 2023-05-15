<?php

namespace Faceted\Search\Result;

class FacetPropertyResult
{
    public string $name = '';
    /**
     * @var FacetPropertyValueResult[]
     */
    public array $valueResultList = [];

    /**
     * @param mixed $value
     * @return FacetPropertyValueResult
     */
    public function getResultByValue($value): ?FacetPropertyValueResult
    {
        foreach ($this->valueResultList as $valueResult) {
            if ($valueResult->value === $value) {
                return $valueResult;
            }
        }
        return null;
    }

    public function getPkList(): array
    {
        $pkList = [];
        foreach ($this->valueResultList as $valueResult) {
            $pkList = array_merge($pkList, $valueResult->itemIdList);
        }
        return $pkList;
    }

    public function getValues(): array
    {
        return array_keys($this->valueResultList);
    }
}
