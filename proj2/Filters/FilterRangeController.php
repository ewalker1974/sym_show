<?php

namespace App\Controller\API\Filters;

use App\Controller\API\Annotations\ApiAnnotationsInterface;
use App\Controller\API\Annotations\IncludeRestAnnotations;
use App\Elasticsearch\ManagerCollection;
use App\Document\Product;
use App\Model\Response\FilterPricesRangeEsResponse;
use App\Model\Response\FilterDimensionsRangeEsResponse;
use App\Controller\API\Traits\ProductFilterAggregationSearch;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class FilterRangeController
{
    use ProductFilterAggregationSearch;

    private $managerCollection;

    public function __construct(ManagerCollection $managerCollection)
    {
        $this->managerCollection = $managerCollection;
    }

    /**
     * @Route("/filters/prices", name="get_price_range", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Min and Max price",
     *     @Model(type=FilterPricesRangeEsResponse::class)
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getPricesRangeAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);

        $search->setPriceRangeAggregation();
        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        return new FilterPricesRangeEsResponse($result);
    }

    /**
     * @Route("/filters/dimensions", name="get_dimensions_range", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Min and Max of length, height and width",
     *     @Model(type=FilterDimensionsRangeEsResponse::class)
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getDimensionsRangeAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);

        $search->setDimensionsRangeAggregation();
        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        return new FilterDimensionsRangeEsResponse($result);
    }
}
