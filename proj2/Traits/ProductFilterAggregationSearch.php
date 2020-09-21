<?php

namespace App\Controller\API\Traits;

use FOS\RestBundle\Request\ParamFetcherInterface;
use App\ElasticsearchDSL\ProductSearch;
use App\Model\Filter\ProductAmountInterface;

trait ProductFilterAggregationSearch
{
    private function getProductSearch(ParamFetcherInterface $paramFetcher): ProductSearch
    {
        $search = new ProductSearch();
        $search
            ->setSize(0)
            ->addSearchQuery($paramFetcher->get('q'))
            ->addCategoriesQuery($paramFetcher->get('ct'))
            ->setType($paramFetcher->get('t'))
            ->setHeightRangeFilter($paramFetcher->get('dh'))
            ->setDepthRangeFilter($paramFetcher->get('dd'))
            ->setWidthRangeFilter($paramFetcher->get('dw'))
            ->setPriceRangeFilter($paramFetcher->get('p'))
            ->setDesignPeriods($paramFetcher->get('dp'))
            ->setLocation($paramFetcher->get('l'))
            ->setDesigners($paramFetcher->get('cd'))
            ->setMakers($paramFetcher->get('cm'))
            ->setSkus($paramFetcher->get('sku'))
            ->setIds($paramFetcher->get('id'))
            ->setStyles($paramFetcher->get('s'))
            ->setColors($paramFetcher->get('cr'))
            ->setMaterialGroups($paramFetcher->get('mg'))
            ->setMaterials($paramFetcher->get('ms'))
            ->setIsSale($paramFetcher->get('is'))
            ->setScope($paramFetcher->get('sc'))
        ;
        return $search;
    }

    public function getAggregationValues(\Iterator $items)
    {
        $result = [];
        foreach ($items  as $item) {
            $result[$item->getValue('key')] = $item->getValue('doc_count');
        }
        return $result;
    }

    /**
     * @param array | Iterator  $items
     * @param array $ids
     * @return mixed
     */
    public function setProductCount($items, ?array $ids)
    {
        $result = [];
        foreach ($items as $item) {
            if ($item instanceof ProductAmountInterface) {
                $id = $item->getId();
                if (isset($ids[$id])) {
                    $item->setNumProducts($ids[$id]);
                }
            }
            $result[] = $item;
        }
        return $result;
    }
}
