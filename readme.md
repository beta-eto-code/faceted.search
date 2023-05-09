# Фасетный поиск

**Пример создания фасетного индекса:**

```php
use Faceted\Search\FacetBuilder;
use Data\Provider\Providers\PdoDataProvider;
use Data\Provider\SqlBuilderMySql;

$clothDataProvider = new PdoDataProvider(
    $someConnection, 
    'cloth_table', 
    new SqlBuilderMySql(),
    'id'
);
$clothFacetIndex = FacetBuilder::init($clothDataProvider)
    ->setItemIdKey('id')            // указываем первичный ключ записи
    ->registerProperty('gender')    // индексируем признак мужской и женской одежды
    ->registerProperty('size')      // индексируем размеры одежды
    ->registerProperty('color')     // индексируем цвет одежды
    ->registerProperty('type')      // индексируем категорию одежды
    ->setLimitByStepIndex(1000)     // указываем количество записией индексируемых за один шаг
    ->build();                      // запускаем сборку индекса
```

**Запись, чтение, удаление фасетного индекса:**

```php
use Faceted\Search\FacetManger;
use Data\Provider\Providers\PdoDataProvider;
use Data\Provider\SqlBuilderMySql;

$facetDataProvider = new PdoDataProvider(
    $someConnection, 
    'facet_index_table', 
    new SqlBuilderMySql(),
    'id'
);
$manager = new FacetManger($facetDataProvider);
$manager->saveFacetById('cloth', $clothFacetIndex); // сохраняем фасетный индекс присвоив ему идентификатор cloth
$userFacetIndex = $manager->getFacetById('user');   // запрашиваем фасетный индекс сохраненный под идентификатором user
$manager->removeFacetById('other');                 // удаляем фасетный индекс с идентификатором other
```
**Работа с фасетным индексом**
```php
use Faceted\Search\FacetManger;
use Data\Provider\Providers\PdoDataProvider;
use Data\Provider\SqlBuilderMySql;
use Data\Provider\QueryCriteria;
use Data\Provider\Interfaces\CompareRuleInterface;

$facetDataProvider = new PdoDataProvider(
    $someConnection, 
    'facet_index_table', 
    new SqlBuilderMySql(),
    'id'
);
$manager = new FacetManger($facetDataProvider);
$clothFacetIndex = $manager->getFacetById('cloth');
$clothFacetIndex->getValuesByProperty('color'); // запрашиваем список всех доступных цветов для одежды

$query = new QueryCriteria();
$query->addCriteria('color', CompareRuleInterface::IN, ['white', 'blue', 'red']);
$query->addCriteria('gender', CompareRuleInterface::IN, ['male', 'female']);
$query->addCriteria('size', CompareRuleInterface::EQUAL, 'M');
$clothFacetIndex->getCountByCompareRuleList(...$query->getCriteriaList()); // запрос количество позиций по заданным критериям

$clothFacetIndex->getItemIdListByCompareRuleList(...$query->getCriteriaList()); // список идентификтов одежды соотвествующей критериям

$clothFacetIndex->getActualPropertyValuesForCompareRuleList(...$query->getCriteriaList()); // список свойств с актуальными значениями (совместимыми с текущими критериями) и количеством позиций по кождому из значений

$clothFacetIndex->getFacetResultForCompareRuleList(...$query->getCriteriaList()); // результат аналогичен предыдущему за исключением того что возвращается объект вместо массива.
```