<?php

namespace App\Controller\API\Filters;

use App\Controller\API\Annotations\ApiAnnotationsInterface;
use App\Controller\API\Annotations\IncludeRestAnnotations;
use App\Controller\API\Traits\ProductFilterAggregationSearch;
use App\Document\Product;
use App\Elasticsearch\ManagerCollection;
use App\Filter\FilterData;
use App\Model\Filter\StyleType;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

class FilterStyleController
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
     * @Route("/filters/styles", name="get_styles_list", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Styles list",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=StyleType::class))
     *      )
     * )
     * @Rest\QueryParam(name="st", description="Search string of filter  values (for filter browser)")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getStylesRangeAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(StyleType::class);

        $search->setStyleRangeAggregation($repository->getCount());
        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $ids = $this->getAggregationValues($result->getAggregation('item')->getIterator());
        $repository
            ->addFilter('id', array_keys($ids));
        $filterSearch = $paramFetcher->get('st');
        if (strlen($filterSearch)) {
            $repository->addSearch('name', '/'.$filterSearch.'/i');
        }
        return $this->setProductCount($repository->getData(), $ids);
    }


    /**
     * @Route("/filters/styles/letters/{letter}", name="get_styles_list_letter", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Styles list",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=StyleType::class))
     *      )
     * )
     * @SWG\Parameter(
     *     name="letter",
     *     in="path",
     *     type="string",
     *     description="Letter name (A-Z)"
     * )
     * @Rest\QueryParam(name="st", description="Search string of filter  values (for filter browser)")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getStylesByLetterRangeAction(string $letter, ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(StyleType::class);

        $search->setStyleRangeAggregation($repository->getCount());
        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $ids = $this->getAggregationValues($result->getAggregation('item')->getIterator());
        $repository
            ->addFilter('id', array_keys($ids))
            ->addSearch('name', '/^'.$letter.'/i');
        $filterSearch = $paramFetcher->get('st');
        if (strlen($filterSearch)) {
            $repository->addSearch('name', '/'.$filterSearch.'/i');
        }
        return $this->setProductCount($repository->getData(), $ids);
    }
}
