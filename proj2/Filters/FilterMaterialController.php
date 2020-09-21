<?php

namespace App\Controller\API\Filters;

use App\Controller\API\Annotations\ApiAnnotationsInterface;
use App\Controller\API\Annotations\IncludeRestAnnotations;
use App\Controller\API\Traits\ProductFilterAggregationSearch;
use App\Document\Product;
use App\Elasticsearch\ManagerCollection;
use App\ElasticsearchDSL\ProductSearch;
use App\Filter\FilterData;
use App\Model\Filter\MaterialGroupType;
use App\Model\Filter\MaterialType;
use App\Model\Response\MaterialFilterPaginatedListResponse;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

class FilterMaterialController
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
     * @Route("/filters/material_groups/", name="get_material_groups", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Material groups list",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=MaterialGroupType::class))
     *      )
     * )
     * @Rest\QueryParam(name="st", description="Search string of filter  values (for filter browser)")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getMaterialsGroupsRangeAction(ParamFetcherInterface $paramFetcher)
    {
        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(MaterialGroupType::class);

        $search->setMaterialsGroupsRangeAggregation($repository->getCount());
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
     * @Route("/filters/material_groups/{group}/materials", name="get_materials_list_by_group", methods={"GET"})
     * @SWG\Tag(name="Filter")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="Materials list",
     *     @Model(type=MaterialFilterPaginatedListResponse::class)
     * )
     * @SWG\Parameter(
     *     name="group",
     *     in="path",
     *     type="string",
     *     description="Group Id"
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="pagination")
     * @Rest\QueryParam(name="st", description="Search string of filter  values (for filter browser)")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     */
    public function getMaterialsByGroupRangeAction(MaterialGroupType $group, ParamFetcherInterface $paramFetcher)
    {
        $page = $paramFetcher->get('pg');
        $pageSize = $paramFetcher->get('ps');

        $search = $this->getProductSearch($paramFetcher);
        $repository = $this->filtersRanges->getRepository(MaterialType::class);

        $search->setMaterialsRangeAggregation($repository->getCount());
        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);
        $ids = $this->getAggregationValues($result->getAggregation('item')->getIterator());
        $repository
            ->addFilter('groupId', $group->getId())
            ->addFilter('id', array_keys($ids))
            ->setLimits(($page-1)*$pageSize, $pageSize-1);
        $filterSearch = $paramFetcher->get('st');
        if (strlen($filterSearch)) {
            $repository->addSearch('name', '/'.$filterSearch.'/i');
        }
        $totalItems = $repository->getCount();
        $result = $this->setProductCount($repository->getData(), $ids);

        if ($page == 1) {
            $group->setNumProducts($this->getProductsCount($group->getId()));
            $result = array_merge([$group], $result);
        }

        $response = (new MaterialFilterPaginatedListResponse())
            ->setPageSize($pageSize)
            ->setPageNumber($page)
            ->setTotalItems($totalItems+1)
            ->setItems($result);

        return $response;
    }

    private function getProductsCount($id)
    {
        $search = new ProductSearch();
        $search->setMaterialGroups([$id]);

        return $this->managerCollection->getManager()->getRepository(Product::class)->count($search);
    }
}
