<?php

namespace App\ElasticsearchDSL;

use App\Model\Filter\ProductType;
use App\ElasticsearchDSL\Utils\Annotation\SearchMap;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\NestedAggregation;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Serializer\OrderedSerializer;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Aggregation\Metric\MinAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\MaxAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;

class ProductSearch extends AbstractSearch
{
    const MORE_TO_LOVE_CATEGORY = 1437;
    const IS_FOR_SALE = 1;
    const IS_NOT_FOR_SALE = 0;

    const ALLOWED_SALE_STATUS = 0;
    const ALLOWED_VISIBILITY = [4,2,3];
    const ALLOWED_STATUS = 1;
    /**
     * @var OrderedSerializer
     */
    protected static $serializer;

    public function __construct()
    {
        parent::__construct();
        $this->productPreFilter();
    }

    /**
     * @param $query
     * @return ProductSearch
     * @SearchMap(name="q")
     */
    public function addSearchQuery($query): self
    {
        if (!$query) {
            return $this;
        }

        return $this->addQuery(new QueryStringQuery($query));
    }

    public function addSortOrder($sort): self
    {
        if (!$sort) {
            return $this;
        }

        switch ($sort) {
            case 'ft':
                return $this->addSort(new FieldSort('bucket_position', FieldSort::ASC));
            case 'ra':
                return $this->addSort(new FieldSort('created_at', FieldSort::DESC));
            case 'pl':
                return $this->addSort(new FieldSort('price', FieldSort::ASC));
            case 'ph':
                return $this->addSort(new FieldSort('price', FieldSort::DESC));
        }

        throw new \RuntimeException(sprintf('"%s" sort order not implemented', $sort));
    }

    /**
     * @param $categories
     * @return ProductSearch
     * @SearchMap(name="ct")
     */
    public function addCategoriesQuery($categories): self
    {
        if (!$categories) {
            return $this;
        }

        return $this->addQuery(
            new NestedQuery(
                "categories__n",
                new TermsQuery(
                    "categories__n.id",
                    $categories
                )
            )
        );
    }

    public function setMoreToLove(): self
    {
        return $this->addCategoriesQuery([self::MORE_TO_LOVE_CATEGORY]);
    }

    /**
     * @param string $scope
     * @return $this
     * @SearchMap(name="sc")
     */
    public function setScope($scope = 'a')
    {
        // new
        if ('n' === $scope) {
            $this->addSortOrder('ra');
        }

        // selected
        // todo: make implementation of selected product
        if ('s' === $scope) {
            $this->addSortOrder(['ft', 'ra', 'pl', 'ph'][rand(0, 3)]);
        }

        return $this;
    }

    /**
     * @param string $scope
     * @return $this|ProductSearch
     * @SearchMap(name="is")
     */
    public function setIsSale($scope = 'a')
    {
        if ($scope === 'y') {
            return $this->addQuery(
                new TermQuery(
                    'is_salable',
                    self::IS_FOR_SALE
                )
            );
        } elseif ($scope === 'n') {
            return $this->addQuery(
                new TermQuery(
                    'is_salable',
                    self::IS_NOT_FOR_SALE
                )
            );
        }
        return $this;
    }

    /**
     * @param $type
     * @return ProductSearch
     * @SearchMap(name="t")
     */
    public function setType($type): self
    {
        if (!$type) {
            return $this;
        }

        if (isset(ProductType::PRODUCT_TYPES[$type])) {
            return $this->addQuery(
                new TermQuery(
                    'itmq_product_type',
                    ProductType::PRODUCT_TYPES[$type]
                )
            );
        }

        throw new \RuntimeException(sprintf('"%s" not supported, use "v" or "c"', $type));
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="l")
     */
    public function setLocation($ids)
    {
        if (is_array($ids)) {
            $normIds = array_map(function ($val) {
                return strtoupper($val);
            }, $ids);
        } else {
            $normIds = strtoupper($ids);
        }
        return $this->setFieldIds('location', $normIds);
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="cd")
     */
    public function setDesigners($ids): self
    {
        return $this->setFieldIds('designer', $ids);
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="cm")
     */
    public function setMakers($ids): self
    {
        return $this->setFieldIds('manufacturer_new', $ids);
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="sku")
     */
    public function setSkus($ids): self
    {
        return $this->setFieldIds('sku', $ids);
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="id")
     */
    public function setIds($ids): self
    {
        return $this->setFieldIds('_id', $ids);
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="s")
     */
    public function setStyles($ids)
    {
        return $this->setFieldIds('style', $ids);
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="cr")
     */
    public function setColors($ids)
    {
        return $this->setFieldIds('color_group', $ids);
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="mg")
     */
    public function setMaterialGroups($ids)
    {
        return $this->setFieldIds('materials_group', $ids);
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="ms")
     */
    public function setMaterials($ids)
    {
        return $this->setFieldIds('materials_multiselect', $ids);
    }

    /**
     * @param $filterRange
     * @return ProductSearch
     * @SearchMap(name="dh")
     */
    public function setHeightRangeFilter($filterRange): self
    {
        return $this->setFieldRangeFilter('height__f', $filterRange);
    }

    /**
     * @param $filterRange
     * @return ProductSearch
     * @SearchMap(name="dp")
     */
    public function setDepthRangeFilter($filterRange): self
    {
        return $this->setFieldRangeFilter('length__f', $filterRange);
    }

    /**
     * @param $filterRange
     * @return ProductSearch
     * @SearchMap(name="dw")
     */
    public function setWidthRangeFilter($filterRange): self
    {
        return $this->setFieldRangeFilter('width__f', $filterRange);
    }

    /**
     * @param $filterRange
     * @return ProductSearch
     * @SearchMap(name="p")
     */
    public function setPriceRangeFilter($filterRange): self
    {
        return $this->setFieldRangeFilter('price', $filterRange);
    }

    protected function setFieldRangeFilter($field, $filterRange)
    {
        if (!$filterRange) {
            return $this;
        }

        preg_match('/(\d*):(\d*)/', $filterRange, $matches);
        list($_, $heightFrom, $heightTo) = $matches;

        if (!$heightFrom && !$heightTo) {
            return $this;
        }

        $range = [];
        if ($heightFrom) {
            $range[RangeQuery::GTE] = $heightFrom;
        }
        if ($heightTo) {
            $range[RangeQuery::LTE] = $heightTo;
        }

        return $this->addQuery(new RangeQuery($field, $range));
    }

    /**
     * @param $ids
     * @return ProductSearch
     * @SearchMap(name="dp")
     */
    public function setDesignPeriods($ids)
    {
        return $this->setFieldIds('design_period_new', $ids);
    }

    public function setPriceRangeAggregation()
    {
        $minAggregation = new MinAggregation('priceMin', 'price');
        $maxAggregation = new MaxAggregation('priceMax', 'price');
        $this->addAggregation($minAggregation);
        $this->addAggregation($maxAggregation);
        return $this;
    }

    public function setDimensionsRangeAggregation()
    {
        $dimensionsFields = ['width' => 'width__f', 'length' => 'length__f', 'height' => 'height__f'];
        foreach ($dimensionsFields as $name => $field) {
            $minAggregation = new MinAggregation('min'.$name, $field);
            $maxAggregation = new MaxAggregation('max'.$name, $field);
            $this->addAggregation($minAggregation);
            $this->addAggregation($maxAggregation);
        }
        return $this;
    }

    public function setTypeRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('item', 'itmq_product_type');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setColorRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('item', 'color_group');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setDesignPeriodRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('item', 'design_period_new');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setCountryRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('item', 'location');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setStyleRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('item', 'style');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setMaterialsGroupsRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('item', 'materials_group');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setMaterialsRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('item', 'materials_multiselect');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setDesignersRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('itemDesigner', 'designer');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setMakersRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('itemMaker', 'manufacturer_new');
        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($typeAggregation);
    }

    public function setCategoriesRangeAggregation($size)
    {
        $typeAggregation = new TermsAggregation('item', 'categories__n.id');
        $nestedAggregation = new NestedAggregation('categories', 'categories__n');
        $nestedAggregation->addAggregation($typeAggregation);

        $typeAggregation->setParameters(['size' => $size]);
        return $this->addAggregation($nestedAggregation);
    }

    //private function set

    public function productPreFilter()
    {
        $this->addQuery(
            new TermQuery(
                'status',
                self::ALLOWED_STATUS
            )
        );

        $this->addQuery(
            new TermQuery(
                'sale_status',
                self::ALLOWED_SALE_STATUS
            )
        );

        $this->setFieldIds('visibility', self::ALLOWED_VISIBILITY);
    }
}
