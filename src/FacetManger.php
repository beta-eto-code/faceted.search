<?php

namespace Faceted\Search;

use Data\Provider\Interfaces\OperationResultInterface;
use Faceted\Search\Interfaces\FacetInterface;
use Faceted\Search\Interfaces\FacetManagerInterface;

class FacetManger extends BaseManager implements FacetManagerInterface
{
    public function getFacetById(string $facetId): ?FacetInterface
    {
        $facetData = $this->getDataById($facetId);
        if (empty($facetData)) {
            return null;
        }

        return new Facet($facetData);
    }

    public function removeFacetById(string $facetId): OperationResultInterface
    {
        return $this->removeDataById($facetId);
    }

    public function saveFacetById(string $facetId, FacetInterface $facet): OperationResultInterface
    {
        return $this->saveObjectData($facetId, $facet);
    }
}
