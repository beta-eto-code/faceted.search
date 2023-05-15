<?php

namespace Faceted\Search;

use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\QueryCriteria;
use Exception;
use Faceted\Search\Interfaces\FacetBuilderInterface;
use Faceted\Search\Interfaces\FacetExtraDataProcessorInterface;
use Faceted\Search\Interfaces\FacetInterface;

class FacetBuilder implements FacetBuilderInterface
{
    private DataProviderInterface $dataProvider;
    private int $itemsCountProccessed = 0;
    private string $idKey = 'id';
    /**
     * @var array<string, callable>
     */
    private array $properties = [];
    private int $stepLimit = 0;
    private ?QueryCriteriaInterface $query = null;
    private ?FacetExtraDataProcessorInterface $extraDataProcessor = null;
    /**
     * @var callable|null
     */
    private $valueHandler = null;

    public static function init(DataProviderInterface $dataProvider): FacetBuilderInterface
    {
        return new FacetBuilder($dataProvider);
    }

    private function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
        $this->itemsCountProccessed = 0;
    }

    public function getCountItemsProccessed(): int
    {
        return $this->itemsCountProccessed;
    }

    public function setValueHandler(callable $handler): FacetBuilderInterface
    {
        $this->valueHandler = $handler;
        return $this;
    }

    public function setItemIdKey(string $idKey): FacetBuilderInterface
    {
        $this->idKey = $idKey;
        return $this;
    }

    public function registerProperty(
        string $name,
        ?string $sourceKey = null,
        ?callable $valueGetter = null
    ): FacetBuilderInterface {
        /**
         * @psalm-suppress MissingClosureReturnType
         */
        $this->properties[$name] = $valueGetter ?? function (array $sourceData) use ($name, $sourceKey) {
            $key = $sourceKey ?? $name;
            return $sourceData[$key] ?? null;
        };
        return $this;
    }

    public function setLimitByStepIndex(int $limit): FacetBuilderInterface
    {
        $this->stepLimit = $limit;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function build(): FacetInterface
    {
        $this->itemsCountProccessed = 0;
        if (empty($this->properties)) {
            throw new Exception('Не указаны свойства для индексирования');
        }

        $query = $this->query ?? new QueryCriteria();
        if ($this->stepLimit > 0) {
            $query->setLimit($this->stepLimit);
        }

        $facet = new Facet();
        do {
            $dataList = $this->dataProvider->getData($query);
            foreach ($dataList as $data) {
                $this->itemsCountProccessed++;
                $this->updateExtraData($data);
                foreach ($this->properties as $propertyName => $property) {
                    $id = $data[$this->idKey] ?? null;
                    if (is_null($id)) {
                        continue;
                    }

                    $value = $property($data);
                    $value = is_callable($this->valueHandler) ? ($this->valueHandler)($value) : $value;
                    if (!is_null($value) && is_scalar($id)) {
                        $facet->addItemIdForValueByProperty($propertyName, $value, $id);
                    }
                }
            }
            $query->setOffset($query->getOffset() + $this->stepLimit);
        } while ($this->stepLimit > 0 && count($dataList) === $this->stepLimit);
        return $facet;
    }

    public function setQuery(QueryCriteriaInterface $query): FacetBuilderInterface
    {
        $this->query = $query;
        return $this;
    }

    public function setFacetExtraDataProcessor(
        FacetExtraDataProcessorInterface $extraDataProcessor
    ): FacetBuilderInterface {
        $this->extraDataProcessor = $extraDataProcessor;
        return $this;
    }

    private function updateExtraData(array $data): void
    {
        if ($this->extraDataProcessor instanceof FacetExtraDataProcessorInterface) {
            $this->extraDataProcessor->processData($data);
        }
    }
}
