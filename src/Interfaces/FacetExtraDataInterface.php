<?php

namespace Faceted\Search\Interfaces;

use JsonSerializable;

interface FacetExtraDataInterface extends JsonSerializable
{
    /**
     * @param string $propertyName
     * @param mixed $value
     * @return bool
     */
    public function hasDataForPropertyValue(string $propertyName, $value): bool;

    /**
     * @param string $propertyName
     * @param mixed $value
     * @return mixed
     */
    public function getDataForPropertyValue(string $propertyName, $value);

    /**
     * @param string $propertyName
     * @param mixed $value
     * @param mixed $data
     * @return void
     */
    public function setDataForPropertyValue(string $propertyName, $value, $data): void;

    /**
     * @param string $propertyName
     * @param mixed $value
     * @return void
     */
    public function removeDataForPropertyValue(string $propertyName, $value): void;
}
