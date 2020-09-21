<?php

namespace App\Controller\API\Filters;

use App\Controller\API\Annotations\ApiAnnotationsInterface;
use App\Controller\API\Annotations\IncludeRestAnnotations;
use App\Elasticsearch\ManagerCollection;
use App\Document\Product;
use App\Model\Filter\ProductType;
use App\Model\Filter\ColorType;
use App\Model\Filter\DesignPeriodType;
use App\Filter\FilterData;
use App\Controller\API\Traits\ProductFilterAggregationSearch;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class FilterSimpleListController
{
    use ProductFilterAggregationSearch;

    private $managerCollection;
    private $filtersRanges;

    public function __construct(ManagerCollection $managerCollection, FilterData $filtersRanges)
    {
        $this->managerCollection = $managerCollection;
        $this->filtersRanges = $filtersRanges;
    }

    /**
     * @Route("/filters/types", name="get_types_list", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Types list (like Contemporary and Vintage)",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=ProductType::class))
     *      )
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getProductTypesRangeAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(ProductType::class);

        $search->setTypeRangeAggregation($repository->getCount());
        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $ids = $this->getAggregationValues($result->getAggregation('item')->getIterator());
        $data = $repository
            ->addFilter('id', array_keys($ids))
            ->getData();

        return $this->setProductCount($data, $ids);
    }

    /**
     * @Route("/filters/colors", name="get_colors_list", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Colors list",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=ColorType::class))
     *      )
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getColorTypesRangeAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(ColorType::class);

        $search->setColorRangeAggregation($repository->getCount());
        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $ids = $this->getAggregationValues($result->getAggregation('item')->getIterator());
        $data = $repository
            ->addFilter('id', array_keys($ids))
            ->getData();

        return $this->setProductCount($data, $ids);
    }

    /**
     * @Route("/filters/design_period", name="get_design_period_list", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Design periods",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=DesignPeriodType::class))
     *      )
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getDesignPeriodRangeAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(DesignPeriodType::class);

        $search->setDesignPeriodRangeAggregation($repository->getCount());
        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $ids = $this->getAggregationValues($result->getAggregation('item')->getIterator());
        $data = $repository
            ->addFilter('id', array_keys($ids))
            ->getData();

        return $this->setProductCount($data, $ids);
    }
}
