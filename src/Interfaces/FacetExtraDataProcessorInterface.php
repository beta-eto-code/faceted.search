<?php

namespace Faceted\Search\Interfaces;

interface FacetExtraDataProcessorInterface
{
    public static function init(FacetExtraDataInterface $facetExtraData): FacetExtraDataProcessorInterface;

    public function processData(array $data): void;
}
