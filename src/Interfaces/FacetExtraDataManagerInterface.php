<?php

namespace Faceted\Search\Interfaces;

use Data\Provider\Interfaces\OperationResultInterface;

interface FacetExtraDataManagerInterface
{
    public function getFacetById(string $facetId): ?FacetExtraDataInterface;
    public function removeFacetById(string $facetId): OperationResultInterface;
    public function saveFacetById(string $facetId, FacetExtraDataInterface $facet): OperationResultInterface;
}
