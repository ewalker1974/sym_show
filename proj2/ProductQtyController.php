<?php

namespace App\Controller\API;

use App\Model\Response\MagentoInventoryResponse;
use App\OAuth\ResourceOwner\ShopResourceOwner;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class ProductQtyController
{
    private $shopResourceOwner;

    public function __construct(ShopResourceOwner $shopResourceOwner)
    {
        $this->shopResourceOwner = $shopResourceOwner;
    }

    /**
     * @Route("/products/qty", name="get_products_qty", methods={"GET"})
     * @SWG\Tag(name="Product")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Parameter(
     *     name="id[]",
     *     in="query",
     *     type="array",
     *     collectionFormat="multi",
     *     @SWG\Items(type="string"),
     *     description="Product ids (array of ids)"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Get products quantity",
     *     @SWG\Items(@Model(type=MagentoInventoryResponse::class))
     * )
     * @QueryParam(map=true, name="id", description="Product ids (array of ids)")
     * @View(statusCode=200)
     */
    public function getQtyAction(ParamFetcherInterface $paramFetcher)
    {
        $ids = $paramFetcher->get('id');
        if ('' === $ids) {
            return [];
        }

        return $this->shopResourceOwner->getInventory($ids);
    }
}
