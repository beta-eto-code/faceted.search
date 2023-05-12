<?php

namespace Faceted\Search\Interfaces;

use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\QueryCriteria;

interface FacetBuilderInterface
{
    public static function init(DataProviderInterface $dataProvider): FacetBuilderInterface;

    public function setItemIdKey(string $idKey): FacetBuilderInterface;

    public function registerProperty(
        string $name,
        ?string $sourceKey = null,
        ?callable $valueGetter = null
    ): FacetBuilderInterface;

    public function setLimitByStepIndex(int $limit): FacetBuilderInterface;

    public function setQuery(QueryCriteriaInterface $query): FacetBuilderInterface;

    public function build(): FacetInterface;
}
