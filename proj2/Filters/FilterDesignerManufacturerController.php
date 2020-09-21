<?php

namespace App\Controller\API\Filters;

use App\Controller\API\Annotations\ApiAnnotationsInterface;
use App\Controller\API\Annotations\IncludeRestAnnotations;
use App\Controller\API\Traits\ProductFilterAggregationSearch;
use App\Document\Product;
use App\Elasticsearch\ManagerCollection;
use App\ElasticsearchDSL\ProductSearch;
use App\Filter\FilterData;
use App\Filter\TopDesignerMakerRepository;
use App\Model\Filter\DesignerMakerFilterType;
use App\Model\Filter\DesignerFilterType;
use App\Model\Filter\MakerFilterType;
use App\Filter\Repository;
use App\Model\Response\DesignerMakerFilterPaginatedListResponse;
use App\Model\Response\Util\ManufacturerDesignerListMaker;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

class FilterDesignerManufacturerController
{
    use ProductFilterAggregationSearch;

    private $managerCollection;
    private $filtersRanges;
    private $topDesignerMakers;

    public function __construct(ManagerCollection $managerCollection, FilterData $filtersRanges, TopDesignerMakerRepository $topDesigners)
    {
        $this->managerCollection = $managerCollection;
        $this->filtersRanges = $filtersRanges;
        $this->topDesignerMakers = $topDesigners;
    }

    /**
     * @Route("/filters/designer_makers/top", name="get_designer_maker_top", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Designer Makers top list",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=DesignerMakerFilterType::class))
     *      )
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getDesignersMakersTopAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);

        $designerRepository = $this->filtersRanges->getRepository(DesignerFilterType::class);
        $makerRepository = $this->filtersRanges->getRepository(MakerFilterType::class);

        $totalCount = $designerRepository->getCount() + $makerRepository->getCount();

        $search->setDesignersRangeAggregation($totalCount);
        $search->setMakersRangeAggregation($totalCount);

        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $idsDesigner = $this->getAggregationValues($result->getAggregation('itemDesigner')->getIterator());
        $idsMaker = $this->getAggregationValues($result->getAggregation('itemMaker')->getIterator());

        $ids = array_merge($idsDesigner, $idsMaker);

        $filterData = $this->topDesignerMakers->getItems(array_keys($ids));

        return $this->setProductCount($filterData, $ids);
    }

    /**
     * @Route("/filters/designer_makers/", name="get_designer_maker_list", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Designer Makers list",
     *     @Model(type=DesignerMakerFilterPaginatedListResponse::class)
     *
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="pagination")
     * @Rest\QueryParam(name="st", description="Search string of filter  values (for filter browser)")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getDesignersMakersAction(ParamFetcherInterface $paramFetcher)
    {
        $page = $paramFetcher->get('pg');
        $pageSize = $paramFetcher->get('ps');
        $amount = $page*$pageSize;
        $search = $this->getProductSearch($paramFetcher);

        $designerRepository = $this->filtersRanges->getRepository(DesignerFilterType::class);
        $makerRepository = $this->filtersRanges->getRepository(MakerFilterType::class);

        $totalCount = $designerRepository->getCount() + $makerRepository->getCount();

        $search->setDesignersRangeAggregation($totalCount);
        $search->setMakersRangeAggregation($totalCount);

        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $idsDesigner = $this->getAggregationValues($result->getAggregation('itemDesigner')->getIterator());
        $idsMaker = $this->getAggregationValues($result->getAggregation('itemMaker')->getIterator());

        $filterSearch = $paramFetcher->get('st');
        $designersFilters = $this->getFilterValues(
            $designerRepository,
            $idsDesigner,
            null,
            $filterSearch,
            $amount
        );
        $makersFilters = $this->getFilterValues(
            $makerRepository,
            $idsMaker,
            null,
            $filterSearch,
            $amount
        );
        $totalData = (new ManufacturerDesignerListMaker($makersFilters, $designersFilters, $amount))->getItems();
        $totalData = array_slice($totalData, ($page-1)*$pageSize, $pageSize);
        $response  = new DesignerMakerFilterPaginatedListResponse();
        return $response
                    ->setItems($totalData)
                    ->setTotalItems(count($idsDesigner)+ count($idsMaker))
                    ->setPageNumber($page)
                    ->setPageSize($pageSize);
    }

    /**
     * @Route("/filters/designer_makers/letters/{letter}", name="get_designer_maker_list_letter", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Designer Makers list",
     *     @Model(type=DesignerMakerFilterPaginatedListResponse::class)
     *
     * )
     * @SWG\Parameter(
     *     name="letter",
     *     in="path",
     *     type="string",
     *     description="Letter name (1,A-Z) 1 for non alphabetic characers like &New"
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="pagination")
     * @Rest\QueryParam(name="st", description="Search string of filter  values (for filter browser)")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getDesignersMakersLetterAction(string $letter, ParamFetcherInterface $paramFetcher)
    {
        $page = $paramFetcher->get('pg');
        $pageSize = $paramFetcher->get('ps');
        $amount = $page*$pageSize;
        $search = $this->getProductSearch($paramFetcher);

        $designerRepository = $this->filtersRanges->getRepository(DesignerFilterType::class);
        $makerRepository = $this->filtersRanges->getRepository(MakerFilterType::class);

        $totalCount = $designerRepository->getCount() + $makerRepository->getCount();

        $search->setDesignersRangeAggregation($totalCount);
        $search->setMakersRangeAggregation($totalCount);

        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $idsDesigner = $this->getAggregationValues($result->getAggregation('itemDesigner')->getIterator());
        $idsMaker = $this->getAggregationValues($result->getAggregation('itemMaker')->getIterator());

        $filterSearch = $paramFetcher->get('st');
        $designersFilters = $this->getFilterValues(
            $designerRepository,
            $idsDesigner,
            $letter,
            $filterSearch,
            $amount
        );
        $makersFilters = $this->getFilterValues(
            $makerRepository,
            $idsMaker,
            $letter,
            $filterSearch,
            $amount
        );
        $totalData = (new ManufacturerDesignerListMaker($makersFilters, $designersFilters, $amount))->getItems();
        $totalData = array_slice($totalData, ($page-1)*$pageSize, $pageSize);
        $response  = new DesignerMakerFilterPaginatedListResponse();
        return $response
            ->setItems($totalData)
            ->setTotalItems(count($idsDesigner)+ count($idsMaker))
            ->setPageNumber($page)
            ->setPageSize($pageSize);
    }

    private function getFilterValues(Repository $repository, array $ids, ?string $letter, ?string $filterSearch, int $amount): array
    {
        $repository
            ->addFilter('id', array_keys($ids));
        if (strlen($letter)) {
            if ($letter === '1') {
                $repository->addSearch('name', '/^[1..9,~!@#$%^&*\"\']/');
            } else {
                $repository->addSearch('name', '/^'.$letter.'/i');
            }
        }
        if (strlen($filterSearch)) {
            $repository->addSearch('name', '/'.$filterSearch.'/i');
        }
        $repository->setLimits(0, $amount);
        return $this->setProductCount($repository->getData(), $ids);
    }
}
