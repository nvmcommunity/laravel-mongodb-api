<?php

namespace Nvmcommunity\LaravelMongodbApi;

use Illuminate\Database\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;
use Nvmcommunity\Alchemist\RestfulApi\AlchemistRestfulApi;
use Nvmcommunity\Alchemist\RestfulApi\Common\Exceptions\AlchemistRestfulApiException;
use Nvmcommunity\Alchemist\RestfulApi\Common\Integrations\AlchemistQueryable;
use Nvmcommunity\Alchemist\RestfulApi\Common\Notification\ErrorBag;
use Nvmcommunity\Alchemist\RestfulApi\FieldSelector\Handlers\FieldSelector;
use Nvmcommunity\Alchemist\RestfulApi\ResourceFilter\Handlers\ResourceFilter;
use Nvmcommunity\Alchemist\RestfulApi\ResourcePaginations\OffsetPaginator\Handlers\ResourceOffsetPaginator;
use Nvmcommunity\Alchemist\RestfulApi\ResourceSearch\Handlers\ResourceSearch;
use Nvmcommunity\Alchemist\RestfulApi\ResourceSort\Handlers\ResourceSort;

class LaravelMongodbBuilder
{
    private ?Builder $builder = null;

    /**
     * @param AlchemistRestfulApi $alchemistRestfulApi
     * @param Model $subject
     */
    public function __construct(protected AlchemistRestfulApi $alchemistRestfulApi, protected Model $subject)
    {
        if ($alchemistRestfulApi->isComponentUses(FieldSelector::class)
            && $alchemistRestfulApi->fieldSelector()->validate()->passes()
        ) {
            $this->handleFieldSelector();
        }

        if ($alchemistRestfulApi->isComponentUses(ResourceFilter::class)
            && $alchemistRestfulApi->resourceFilter()->validate()->passes()
        ) {
            $this->handleResourceFilter();
        }

        if ($alchemistRestfulApi->isComponentUses(ResourceOffsetPaginator::class)
            && $alchemistRestfulApi->resourceOffsetPaginator()->validate()->passes()
        ) {
            $this->handleOffsetPaginator();
        }

        if ($alchemistRestfulApi->isComponentUses(ResourceSort::class)
            && $alchemistRestfulApi->resourceSort()->validate()->passes()
        ) {
            $this->handleResourceSort();
        }

        if ($alchemistRestfulApi->isComponentUses(ResourceSearch::class)
            && $alchemistRestfulApi->resourceSearch()->validate()->passes()
        ) {
            $this->handleResourceSearch();
        }
    }

    /**
     * @param Model|string $subject
     * @param AlchemistQueryable|string $apiClass
     * @param array $input
     *
     * @return LaravelMongodbBuilder
     * @throws AlchemistRestfulApiException
     */
    public static function for(Model|string $subject, AlchemistQueryable|string $apiClass, array $input): LaravelMongodbBuilder
    {
        if (is_subclass_of($subject, Model::class) && ! is_a($subject, Model::class)) {
            $subject = new $subject;
        }

        $alchemistRestfulApi = AlchemistRestfulApi::for($apiClass, $input);

        return new static($alchemistRestfulApi, $subject);
    }

    /**
     * @param ErrorBag|null $errorBag
     * @return ErrorBag
     */
    public function validate(?ErrorBag &$errorBag = null): ErrorBag
    {
        return $this->alchemistRestfulApi->validate($errorBag);
    }

    /**
     * @return AlchemistRestfulApi
     */
    public function getAlchemistRestfulApi(): AlchemistRestfulApi
    {
        return $this->alchemistRestfulApi;
    }

    /**
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->subject;
    }

    /**
     * @return Builder
     */
    public function getBuilder(): Builder
    {
        if (! $this->builder) {
            $this->builder = $this->subject::query();
        }

        return $this->builder;
    }

    /**
     * @return void
     */
    protected function handleFieldSelector(): void
    {
        $rootNamespace = '$';

        $fields = $this->alchemistRestfulApi->fieldSelector()->fields($rootNamespace);

        foreach ($fields as $field) {
            $fieldStructure = $this->alchemistRestfulApi->fieldSelector()->getFieldStructure("$rootNamespace.{$field->getName()}");

            if (! $fieldStructure) {
                continue;
            }

            if ($fieldStructure['type'] === 'atomic') {
                $this->getBuilder()->addSelect($field->getName());
            }
        }
    }

    /**
     * @return void
     */
    protected function handleResourceFilter(): void
    {
        foreach ($this->alchemistRestfulApi->resourceFilter()->filtering() as $filteringObj) {
            match ($filteringObj->getOperator()) {
                'in' => $this->getBuilder()->whereIn($filteringObj->getFiltering(), $filteringObj->getFilteringValue()),
                'not_in' => $this->getBuilder()->whereNotIn($filteringObj->getFiltering(), $filteringObj->getFilteringValue()),
                'between' => $this->getBuilder()->whereBetween($filteringObj->getFiltering(), $filteringObj->getFilteringValue()),
                'not_between' => $this->getBuilder()->whereNotBetween($filteringObj->getFiltering(), $filteringObj->getFilteringValue()),
                'contains' => $this->getBuilder()->where(
                    $filteringObj->getFiltering(), 'like', "%{$filteringObj->getFilteringValue()}%"
                ),
                default => $this->getBuilder()->where(
                    $filteringObj->getFiltering(), $filteringObj->getOperator(), $filteringObj->getFilteringValue()
                ),
            };
        }
    }

    /**
     * @return void
     */
    protected function handleOffsetPaginator(): void
    {
        $offsetPaginate = $this->alchemistRestfulApi->resourceOffsetPaginator()->offsetPaginate();

        if (! empty($offsetPaginate->getLimit())) {
            $this->getBuilder()->limit($offsetPaginate->getLimit());
        }

        if (! empty($offsetPaginate->getOffset())) {
            $this->getBuilder()->offset($offsetPaginate->getOffset());
        }
    }

    /**
     * @return void
     */
    protected function handleResourceSort(): void
    {
        $sort = $this->alchemistRestfulApi->resourceSort()->sort();

        if (! empty($sort->getSortField())) {
            $this->getBuilder()->orderBy($sort->getSortField(), $sort->getDirection());
        }
    }

    /**
     * @return void
     */
    protected function handleResourceSearch(): void
    {
        $search = $this->alchemistRestfulApi->resourceSearch()->search();

        if (! empty($search->getSearchCondition())) {
            $this->getBuilder()->where($search->getSearchCondition(), 'like', "%{$search->getSearchValue()}%");
        }
    }
}