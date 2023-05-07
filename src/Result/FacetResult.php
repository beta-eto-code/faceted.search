<?php

namespace Faceted\Search\Result;

class FacetResult
{
    /**
     * @var FacetPropertyResult[]
     */
    public array $propertyResultList = [];

    public function getResultByProperty(string $propertyName): ?FacetPropertyResult
    {
        foreach ($this->propertyResultList as $propertyResult) {
            if ($propertyResult->name === $propertyName) {
                return $propertyResult;
            }
        }
        return null;
    }
}
