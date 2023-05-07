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
}
