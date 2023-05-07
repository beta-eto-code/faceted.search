<?php

namespace Faceted\Search\Interfaces;

use Data\Provider\Interfaces\OperationResultInterface;

interface FacetManagerInterface
{
    public function getFacetById(string $facetId): ?FacetInterface;
    public function removeFacetById(string $facetId): OperationResultInterface;
    public function saveFacetById(string $facetId, FacetInterface $facet): OperationResultInterface;
}
