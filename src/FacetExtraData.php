<?php

namespace Faceted\Search;

use Faceted\Search\Interfaces\FacetExtraDataInterface;

class FacetExtraData implements FacetExtraDataInterface
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param string $propertyName
     * @param mixed $value
     * @return mixed
     */
    public function getDataForPropertyValue(string $propertyName, $value)
    {
        $propertyValues = $this->getPropertyValues($propertyName);
        return $propertyValues[$value] ?? null;
    }

    private function getPropertyValues(string $propertyName): array
    {
        return $this->data[$propertyName] ?? [];
    }

    /**
     * @param string $propertyName
     * @param mixed $value
     * @param mixed $data
     * @return void
     */
    public function setDataForPropertyValue(string $propertyName, $value, $data): void
    {
        $this->data[$propertyName][(string)$value] = $data;
    }

    public function removeDataForPropertyValue(string $propertyName, $value): void
    {
        if ($this->hasDataForPropertyValue($propertyName, $value)) {
            unset($this->data[$propertyName][$value]);
        }
    }

    /**
     * @param string $propertyName
     * @param mixed $value
     * @return bool
     */
    public function hasDataForPropertyValue(string $propertyName, $value): bool
    {
        $propertyValues = $this->getPropertyValues($propertyName);
        return array_key_exists($value, $propertyValues);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
