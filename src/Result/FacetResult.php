<?php

namespace Faceted\Search\Result;

class FacetResult
{
    /**
     * @var FacetPropertyResult[]
     */
    public array $propertyResultList = [];
    public array $pkList = [];

    public function getPropertyValues(string $propertyName): array
    {
        $propetyResult = $this->getResultByProperty($propertyName);
        return $propetyResult instanceof FacetPropertyResult ? $propetyResult->getValues() : [];
    }

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
