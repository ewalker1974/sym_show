<?php

namespace App\Controller\API\Annotations;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Swagger\Annotations as SWG;

interface ApiAnnotationsInterface
{
    /**
     * @QueryParam(name="pg", requirements="\d+", default="1", description="Page of the overview.")
     * @QueryParam(name="ps", requirements="\d+", default="10", description="Page size.")
     */
    public function pagination();

    /**
     * @QueryParam(name="q", description="Search string by several fields.")
     * @QueryParam(name="t", requirements="v|c", description="Vintage (v) or Contemporary (c). Available values : v, c.")
     * @QueryParam(name="dh", requirements="\d*:\d*", description="Heights range of items (from:to)")
     * @QueryParam(name="dd", requirements="\d*:\d*", description="Depths range of items (from:to)")
     * @QueryParam(name="dw", requirements="\d*:\d*", description="Widths range of items (from:to)")
     * @QueryParam(name="p",  requirements="\d*:\d*", description="Price range of items (from:to)")
     * @QueryParam(map=true, name="ct", requirements="\d+", description="Id of categories to filter.")
     * @QueryParam(map=true, name="l", description="Locations of items")
     * @QueryParam(map=true, name="cd", description="Designers (array of ids)")
     * @QueryParam(map=true, name="cm", description="Makers (array of ids)")
     * @QueryParam(map=true, name="sku", description="Sku(s) (array of sku of products)")
     * @QueryParam(map=true, name="id", description="Id(s) (array of products ids)")
     * @QueryParam(map=true, name="s", description="Styles (array of ids)")
     * @QueryParam(map=true, name="cr", description="Colors (array of ids)")
     * @QueryParam(map=true, name="mg", description="Material groups (array of ids)")
     * @QueryParam(map=true, name="ms", description="Material selection (array of ids)")
     * @QueryParam(map=true, name="dp", description="Design periods (array of design periods ids)")
     * @QueryParam(name="is", requirements="y|n|a", description="Is for sale y|n|a default=a")
     * @QueryParam(name="sc", requirements="n|s|a", description="Scope: New (n) or Selected (s). Available values : n, s. default=a")
     */
    public function productFilters();

    /**
     * @QueryParam(name="o", requirements="ft|ra|pl|ph", description="Sort order (ft: featured, ra- recently added, pl- price low-high, ph- price high low). Available values : ft, ra, pl, ph.")
     */
    public function productSorting();
}
