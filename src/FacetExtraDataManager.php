<?php

namespace Faceted\Search;

use Data\Provider\Interfaces\OperationResultInterface;
use Faceted\Search\Interfaces\FacetExtraDataInterface;
use Faceted\Search\Interfaces\FacetExtraDataManagerInterface;

class FacetExtraDataManager extends BaseManager implements FacetExtraDataManagerInterface
{
    public function getFacetById(string $facetId): ?FacetExtraDataInterface
    {
        $facetExtraData = $this->getDataById($facetId);
        if (empty($facetExtraData)) {
            return null;
        }

        return new FacetExtraData($facetExtraData);
    }

    public function removeFacetById(string $facetId): OperationResultInterface
    {
        return $this->removeDataById($facetId);
    }

    public function saveFacetById(string $facetId, FacetExtraDataInterface $facet): OperationResultInterface
    {
        return $this->saveObjectData($facetId, $facet);
    }
}
