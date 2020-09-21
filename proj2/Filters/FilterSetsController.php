<?php

namespace App\Controller\API\Filters;

use App\ElasticsearchDSL\ProductSearch;
use App\ElasticsearchDSL\Utils\QuerySetBuilder;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use App\Controller\API\Annotations\ApiAnnotationsInterface;
use App\Controller\API\Annotations\IncludeRestAnnotations;
use App\Model\Response\FilterSetResponse;
use App\Model\Response\Exception\BadRequestHttpExceptionResponse;
use App\Document\Product;

class FilterSetsController
{
    const FULL_FILTER_SET = ['ct', 't', 'sc', 'dh', 'dd', 'dw', 'p','dp', 'l', 'cd', 'cm', 'sku', 'id', 's', 'cr', 'mg', 'ms', 'is'];

    /**
     * @Route("/filters/sets/{filter}", name="get_filtersset", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Allowed filter set",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=FilterSetResponse::class))
     *      )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="In case of invalid filter name",
     *     @SWG\Schema(ref=@Model(type=BadRequestHttpExceptionResponse::class))
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     * @QueryParam(name="fv", description="Filter added")
     * @QueryParam(name="fv", map=true, description="Filter added")
     */
    public function getFilterSetAction($filter, ParamFetcherInterface $paramFetcher, QuerySetBuilder $builder)
    {
        foreach (self::FULL_FILTER_SET as $setFilter) {
            if ($setFilter !== $filter) {
                $builder->setFilter($setFilter, $paramFetcher->get($setFilter));
            }
        }
        return $builder->getAllowedFilterSet(ProductSearch::class, Product::class, $filter, $paramFetcher->get('fv'));
    }
}
