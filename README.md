# Laravel Eloquent API

Easily build Eloquent queries from API requests using Alchemist Restful API.

## Description

This is a package that helps you integrate Alchemist Restful API with Laravel Eloquent. for more information about concepts and usage of Alchemist Restful API, please refer to the [Alchemist Restful API documentation](https://github.com/nvmcommunity/alchemist-restful-api)

## Installation

```bash
composer require nvmcommunity/laravel-mongodb-api
```

## Basic usage

### Step 1: Define the API class

```php

use Nvmcommunity\Alchemist\RestfulApi\Common\Exceptions\AlchemistRestfulApiException;
use Nvmcommunity\Alchemist\RestfulApi\Common\Integrations\AlchemistQueryable;
use Nvmcommunity\Alchemist\RestfulApi\FieldSelector\Handlers\FieldSelector;
use Nvmcommunity\Alchemist\RestfulApi\ResourceFilter\Handlers\ResourceFilter;
use Nvmcommunity\Alchemist\RestfulApi\ResourceFilter\Objects\FilteringRules;
use Nvmcommunity\Alchemist\RestfulApi\ResourcePaginations\OffsetPaginator\Handlers\ResourceOffsetPaginator;
use Nvmcommunity\Alchemist\RestfulApi\ResourceSearch\Handlers\ResourceSearch;
use Nvmcommunity\Alchemist\RestfulApi\ResourceSort\Handlers\ResourceSort;

class UserApiQuery extends AlchemistQueryable
{
    /**
     * @param FieldSelector $fieldSelector
     * @return void
     */
    public static function fieldSelector(FieldSelector $fieldSelector): void
    {
        $fieldSelector->defineFieldStructure([
            'id', 'name', 'email', 'created_at'
        ])
        ->defineDefaultFields(['id']);
    }

    /**
     * @param ResourceFilter $resourceFilter
     * @return void
     */
    public static function resourceFilter(ResourceFilter $resourceFilter): void
    {
        $resourceFilter->defineFilteringRules([
            FilteringRules::String('id', ['eq']),
            FilteringRules::String('name', ['eq', 'contains']),
            FilteringRules::String('email', ['eq', 'contains']),
            FilteringRules::Datetime('created_at', ['eq', 'gte', 'lte', 'between']),
        ]);
    }

    /**
     * @param ResourceOffsetPaginator $resourceOffsetPaginator
     * @return void
     */
    public static function resourceOffsetPaginator(ResourceOffsetPaginator $resourceOffsetPaginator): void
    {
        $resourceOffsetPaginator->defineDefaultLimit(10)
            ->defineMaxLimit(1000);
    }

    /**
     * @param ResourceSearch $resourceSearch
     * @return void
     */
    public static function resourceSearch(ResourceSearch $resourceSearch): void
    {
        $resourceSearch->defineSearchCondition('name');
    }

    /**
     * @param ResourceSort $resourceSort
     * @return void
     * @throws AlchemistRestfulApiException
     */
    public static function resourceSort(ResourceSort $resourceSort): void
    {
        $resourceSort->defineDefaultSort('id')
            ->defineDefaultDirection('desc')
            ->defineSortableFields(['id', 'name']);
    }
}
```
### Step 2: Validate & respond to the request

Make sure to validate the input parameters passed in from the request input by using the `$laravelMongodbBuilder->validate()` method before executing the query and responding to the request.

```php

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nvmcommunity\Alchemist\RestfulApi\Common\Exceptions\AlchemistRestfulApiException;
use Nvmcommunity\LaravelMongodbApi\LaravelMongodbBuilder;

class UserController extends Controller
{
    /**
     * @param Request $request
     * @param JsonResponse $response
     * @return JsonResponse
     * @throws AlchemistRestfulApiException
     */
    public function index(Request $request, JsonResponse $response): JsonResponse
    {
        $laravelMongodbBuilder = LaravelMongodbBuilder::for(User::class, UserApiQuery::class, $request->input());

        // Validate the input parameters
        if (! $laravelMongodbBuilder->validate($e)->passes()) {
            return $response->setData($e->getErrors())->setStatusCode(400);
        }

        // It's safe to execute the query now.
        // Return the result as JSON
        return $response->setData($laravelMongodbBuilder->getBuilder()->get());
    }
}
```

## Contributors

### Code Contributors

This project exists thanks to all the people who contribute.

<a href="https://github.com/nvmcommunity/laravel-mongodb-api/graphs/contributors">
<img src = "https://contrib.rocks/image?repo=nvmcommunity/laravel-mongodb-api"/>
</a>

## License

This Project is [MIT](./LICENSE) Licensed