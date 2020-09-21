<?php

namespace App\Controller\API;

use App\Controller\API\Annotations\ApiAnnotationsInterface;
use App\Controller\API\Annotations\IncludeRestAnnotations;
use App\Document\Product;
use App\Elasticsearch\ManagerCollection;
use App\ElasticsearchDSL\ProductSearch;
use App\EventListener\StoreHeaderListener;
use App\Model\Response\Exception\BadRequestHttpExceptionResponse;
use App\Model\Response\Exception\NotFoundException;
use App\Model\Response\ProductsEsPaginatedListResponse;
use App\Model\Response\SearchImageResponse;
use App\OAuth\ResourceOwner\ShopResourceOwner;
use App\Response\Magento\ShippingPriceResponse;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ProductController
{
    private $managerCollection;
    private $magentoResourceOwner;

    public function __construct(ManagerCollection $managerCollection, ShopResourceOwner $resourceOwner)
    {
        $this->managerCollection = $managerCollection;
        $this->magentoResourceOwner = $resourceOwner;
    }

    /**
     * @Route("/products", name="get_products", methods={"GET"})
     * @SWG\Tag(name="Product")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=200,
     *     description="List of products",
     *     @Model(type=ProductsEsPaginatedListResponse::class)
     * )
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productFilters")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="pagination")
     * @IncludeRestAnnotations(class=ApiAnnotationsInterface::class, method="productSorting")
     */
    public function getProductsAction(ParamFetcherInterface $paramFetcher)
    {
        $page = $paramFetcher->get('pg');
        $pageSize = $paramFetcher->get('ps');
        $search = new ProductSearch();
        $search
            ->setSize($pageSize)
            ->setFrom(($page - 1) * $pageSize)
            ->addSearchQuery($paramFetcher->get('q'))
            ->addSortOrder($paramFetcher->get('o'))
            ->addCategoriesQuery($paramFetcher->get('ct'))
            ->setType($paramFetcher->get('t'))
            ->setScope($paramFetcher->get('sc'))
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
        ;

        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);

        $response = (new ProductsEsPaginatedListResponse())
            ->setPageSize($pageSize)
            ->setPageNumber($page)
            ->setDocumentIterator($result);

        return $response;
    }

    /**
     * @Route("/products/more_to_love", name="get_products_more_to_love", methods={"GET"})
     * @SWG\Tag(name="Product")
     * @SWG\Response(
     *     response=200,
     *     description="List of more to love products",
     *     @Model(type=ProductsEsPaginatedListResponse::class)
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Product with provided id not found",
     *     @Model(type=NotFoundException::class)
     * )
     * @QueryParam(name="p", requirements="\d+", description="Product id")
     * @QueryParam(name="pg", requirements="\d+", default="1", description="Page of the overview.")
     * @QueryParam(name="ps", requirements="\d+", default="10", description="Page size.")
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     */
    public function getMoreToLoveAction(ParamFetcherInterface $paramFetcher)
    {
        $productId = $paramFetcher->get('p');
        $page = $paramFetcher->get('pg');
        $pageSize = $paramFetcher->get('ps');

        if ($productId) {
            $product = $this->managerCollection->getManager()->find(Product::class, $productId);
            if (!$product) {
                throw new NotFoundHttpException(sprintf('Product with id "%s" not found', $productId));
            }
        }

        // Current implementation MoreToLove is the Special Category
        $search = new ProductSearch();
        $search
            ->setSize($pageSize)
            ->setFrom($page)
            ->setMoreToLove()
            ->addSortOrder('ft')
        ;

        $result = $this->managerCollection->getManager()
            ->getRepository(Product::class)
            ->findDocuments($search);

        $response = (new ProductsEsPaginatedListResponse())
            ->setPageSize($pageSize)
            ->setPageNumber($page)
            ->setDocumentIterator($result);

        return $response;
    }

    /**
     * @Route("/products/{id}/deliveries/{countryCode}", name="get_products_deliveries", methods={"GET"})
     * @SWG\Tag(name="Product")
     * @SWG\Response(
     *     response=200,
     *     description="Get product delivery cost",
     *     @Model(type=ShippingPriceResponse::class)
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Product with provided id not found",
     *     @Model(type=NotFoundException::class)
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Product with provided id not found",
     *     @Model(type=NotFoundException::class)
     * )
     * @SWG\Parameter(
     *     name="countryCode",
     *     in="path",
     *     type="string",
     *     description="Country code for delivery product, e.g. 'de', 'fr', 'pl' etc."
     * )
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="string",
     *     description="Product id"
     * )
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     */
    public function getProductDeliveriesAction(Product $id, $countryCode)
    {
        $response = $this->magentoResourceOwner->getShippingPrice($id->entity_id, $countryCode);

        if (!$response->price) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                sprintf('Delivery to "%s" is not available', $countryCode)
            );
        }

        return $response;
    }

    /**
     * @Route("/products/{id}", name="get_product", methods={"GET"})
     * @SWG\Tag(name="Product")
     * @SWG\Response(
     *     response=200,
     *     description="Get on product",
     *     @Model(type=Product::class)
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Product with provided id not found",
     *     @Model(type=NotFoundException::class)
     * )
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     */
    public function getProductAction(Product $id, ParamFetcherInterface $paramFetcher)
    {
        return $product = $id;
    }

    /**
     * @Route("/products/search/images", name="get_products_search_image", methods={"POST"})
     * @SWG\Tag(name="Product")
     * @SWG\Response(
     *     response=200,
     *     description="Upload success",
     *     @Model(type=SearchImageResponse::class)
     * )
     * @SWG\Parameter(name="store", in="header", required=true, type="string", default="en", description="Store code")
     * @SWG\Response(
     *     response=400,
     *     description="If request body doesn't comply with validation rules",
     *     @SWG\Schema(ref=@Model(type=BadRequestHttpExceptionResponse::class))
     * )
     * @SWG\Parameter(
     *     name="search_file",
     *     in="formData",
     *     required=true,
     *     type="file",
     *     description="Binary file content to upload"
     * )
     */
    public function getProductsSearchImagesAction()
    {
        throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Not implemented');
    }
}
