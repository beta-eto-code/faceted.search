<?php

namespace Faceted\Search;

use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\QueryCriteria;
use JsonSerializable;

abstract class BaseManager
{
    protected DataProviderInterface $dataProvider;

    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    protected function getDataById(string $id): array
    {
        $query = new QueryCriteria();
        $query->addCriteria($this->getPkName(), CompareRuleInterface::EQUAL, $id);
        $query->setLimit(1);
        $selectResult = current($this->dataProvider->getData($query));
        if (empty($selectResult)) {
            return [];
        }

        $data = ($selectResult['data'] ?? []);
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (empty($data)) {
            return [];
        }

        return $data;
    }

    protected function saveObjectData(string $id, JsonSerializable $object): OperationResultInterface
    {
        $query = null;
        if ($this->hasDataWithId($id)) {
            $query = new QueryCriteria();
            $query->addCriteria($this->getPkName(), CompareRuleInterface::EQUAL, $id);
        }

        $dataForSave = [
            $this->getPkName() => $id,
            'data' => json_encode($object->jsonSerialize(), JSON_UNESCAPED_UNICODE),
        ];

        return $this->dataProvider->save($dataForSave, $query);
    }

    protected function removeDataById(string $id): OperationResultInterface
    {
        $query = new QueryCriteria();
        $query->addCriteria($this->getPkName(), CompareRuleInterface::EQUAL, $id);
        $query->setLimit(1);
        return $this->dataProvider->remove($query);
    }

    protected function hasDataWithId(string $id): bool
    {
        $pkName = $this->getPkName();
        $query = new QueryCriteria();
        $query->setSelect([$pkName]);
        $query->addCriteria($pkName, CompareRuleInterface::EQUAL, $id);
        $query->setLimit(1);
        return $this->dataProvider->getDataCount($query) > 0;
    }

    protected function getPkName(): string
    {
        return $this->dataProvider->getPkName() ?: 'id';
    }
}
