<?php

namespace Faceted\Search;

use Faceted\Search\Interfaces\FacetExtraDataInterface;
use Faceted\Search\Interfaces\FacetExtraDataProcessorInterface;

class FacetExtraDataProcessor implements FacetExtraDataProcessorInterface
{
    private FacetExtraDataInterface $facetExtraData;

    /**
     * @var callable[]
     */
    private array $handlers = [];

    public static function init(FacetExtraDataInterface $facetExtraData): FacetExtraDataProcessorInterface
    {
        return new FacetExtraDataProcessor($facetExtraData);
    }

    public function __construct(FacetExtraDataInterface $facetExtraData)
    {
        $this->facetExtraData = $facetExtraData;
    }

    /**
     * @param callable $handlerData
     * @return FacetExtraDataProcessorInterface
     */
    public function registerHandler(callable $handlerData): FacetExtraDataProcessorInterface
    {
        $this->handlers[] = $handlerData;
        return $this;
    }

    public function processData(array $data): void
    {
        foreach ($this->handlers as $handler) {
            $handler($data, $this->facetExtraData);
        }
    }
}
