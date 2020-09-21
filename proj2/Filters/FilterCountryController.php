<?php

namespace App\Controller\API\Filters;

use App\Controller\API\Annotations\ApiAnnotationsInterface;
use App\Controller\API\Annotations\IncludeRestAnnotations;
use App\Elasticsearch\ManagerCollection;
use App\Document\Product;
use App\Model\Filter\CountryType;
use App\Filter\FilterData;
use App\Controller\API\Traits\ProductFilterAggregationSearch;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class FilterCountryController
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
     * @Route("/filters/locations", name="get_locations_list", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Response(
     *     response=200,
     *     description="Countries list",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=CountryType::class))
     *      )
     * )
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @Rest\QueryParam(name="st", description="Search string of filter  values (for filter browser)")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getLocationsRangeAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(CountryType::class);

        $search->setCountryRangeAggregation($repository->getCount());
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
     * @Route("/filters/locations/letters/{letter}", name="get_locations_list_letter", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Response(
     *     response=200,
     *     description="Countries list",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=CountryType::class))
     *      )
     * )
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Parameter(name="letter", in="path", type="string", description="Letter name (A-Z)")
     * @Rest\QueryParam(name="st", description="Search string of filter  values (for filter browser)")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getLocationsByLetterRangeAction(string $letter, ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(CountryType::class);

        $search->setCountryRangeAggregation($repository->getCount());
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
