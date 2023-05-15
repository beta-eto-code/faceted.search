<?php

namespace Faceted\Search\Result;

class FacetPropertyValueResult
{
    /**
     * @var mixed
     */
    public $value;
    private array $valueItemsIdList;
    private array $resultIdList;
    private ?array $actualIdList = null;

    /**
     * @param mixed $value
     * @param array $valueItemsIdList
     * @param array $resultIdList
     */
    public function __construct($value, array $valueItemsIdList, array $resultIdList)
    {
        $this->value = $value;
        $this->valueItemsIdList = $valueItemsIdList;
        $this->resultIdList = $resultIdList;
    }

    public function getActualCount(): int
    {
        return count($this->getActualItemIdList());
    }

    public function getActualItemIdList(): array
    {
        if (is_null($this->actualIdList)) {
            $this->actualIdList = array_intersect_key($this->resultIdList, $this->valueItemsIdList);
        }
        return $this->actualIdList;
    }
}
