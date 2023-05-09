<?php

namespace Faceted\Search;

use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\QueryCriteria;
use Faceted\Search\Interfaces\FacetInterface;
use Faceted\Search\Interfaces\FacetManagerInterface;

class FacetManger implements FacetManagerInterface
{
    private DataProviderInterface $dataProvider;


    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function getFacetById(string $facetId): ?FacetInterface
    {
        $query = new QueryCriteria();
        $query->addCriteria($this->getPkName(), CompareRuleInterface::EQUAL, $facetId);
        $query->setLimit(1);
        $facetResult = current($this->dataProvider->getData($query));
        if (empty($facetResult)) {
            return null;
        }

        $facetData = ($facetResult['data'] ?? []);
        if (is_string($facetData)) {
            $facetData = json_decode($facetData, true);
        }

        if (empty($facetData)) {
            return null;
        }

        return new Facet($facetData);
    }

    public function removeFacetById(string $facetId): OperationResultInterface
    {
        $query = new QueryCriteria();
        $query->addCriteria($this->getPkName(), CompareRuleInterface::EQUAL, $facetId);
        $query->setLimit(1);
        return $this->dataProvider->remove($query);
    }

    public function saveFacetById(string $facetId, FacetInterface $facet): OperationResultInterface
    {
        $query = null;
        if ($this->hasFacetWithId($facetId)) {
            $query = new QueryCriteria();
            $query->addCriteria($this->getPkName(), CompareRuleInterface::EQUAL, $facetId);
        }

        $dataForSave = [
            'data' => json_encode($facet->jsonSerialize(), JSON_UNESCAPED_UNICODE),
        ];

        return $this->dataProvider->save($dataForSave, $query);
    }

    private function hasFacetWithId(string $facetId): bool
    {
        $pkName = $this->getPkName();
        $query = new QueryCriteria();
        $query->setSelect([$pkName]);
        $query->addCriteria($pkName, CompareRuleInterface::EQUAL, $facetId);
        $query->setLimit(1);
        return $this->dataProvider->getDataCount($query) > 0;
    }

    private function getPkName(): string
    {
        return $this->dataProvider->getPkName() ?: 'id';
    }
}
