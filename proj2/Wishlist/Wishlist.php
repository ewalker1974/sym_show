<?php

namespace App\Customer\Wishlist;

use App\Document\Product;
use App\Elasticsearch\ManagerCollection;
use App\ElasticsearchDSL\ProductSearch;
use App\Model\Response\MagentoWishlistEsResponse;
use App\Model\Response\PaginatedListResponseInterface;
use App\Model\Response\ProductsEsPaginatedListResponse;
use App\OAuth\ResourceOwner\ShopResourceOwner;
use App\Soap\Entity\Customer;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Wishlist implements WishlistInterface
{
    private $shopApi;
    private $tokenStorage;
    protected $managerCollection;
    protected $logger;
    /** @var MagentoWishlistEsResponse */
    protected $items;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ShopResourceOwner $shopApi,
        ManagerCollection $managerCollection,
        LoggerInterface $logger
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->shopApi = $shopApi;
        $this->managerCollection = $managerCollection;
        $this->logger = $logger;
        $this->items = new MagentoWishlistEsResponse();
    }
    
    public function getItems(int $page = 1, int $perPage = 10): PaginatedListResponseInterface
    {
        $this->fetchAll();
        $ids = array_slice($this->items->getItems(), $perPage*($page - 1), $perPage);
        $items = [];

        if (!empty($ids)) {
            $documentIterator = $this->getProducts($ids);
            foreach ($documentIterator as $item) {
                $items[] = $item;
            }
        }

        usort($items, function (Product $product1, Product $product2) use ($ids) {
            return array_search($product1->entity_id, $ids) > array_search($product2->entity_id, $ids) ? 1 : -1;
        });

        return (new ProductsEsPaginatedListResponse())
            ->setItems($items)
            ->setPageSize($perPage)
            ->setPageNumber($page)
            ->setTotalItems(count($ids));
    }

    public function isInWishList(Product $product): bool
    {
        //TODO: quick workaround if user not logged in should be replaced
        if (null === $this->getUser()->getId()) {
            return false;
        }
        $this->fetchAll();
        return array_reduce($this->items->getItems(), function ($curry, $item) use ($product) {
            return $item == $product->entity_id ? true : $curry;
        }, false);
    }

    public function addWishListItem(Product $product): void
    {
        $this->shopApi->addWishListItem($this->getUser(), $product);
    }

    public function deleteWishListItem($productId): void
    {
        $this->shopApi->deleteWishListItem($this->getUser(), $productId);
    }


    protected function getProducts(array $ids): DocumentIterator
    {
        $repo = $this->managerCollection->getManager()->getRepository(Product::class);
        $search = (new ProductSearch())->setIds($ids);

        return $repo->findDocuments($search);
    }

    protected function fetchAll($force = false): void
    {
        if (!empty($this->items->getItems()) && $force === false) {
            return;
        }
        if (null === $this->getUser()) {
            $this->items = new MagentoWishlistEsResponse();
            return;
        }

        try {
            $this->items = $this->shopApi->getWishList($this->getUser(), $page = 1, $perPage = 1000);
        } catch (HttpException $exception) {
            $this->items = new MagentoWishlistEsResponse();
            $this->logger->error($exception->__toString());
            $this->logger->error($exception->getTraceAsString());
        }
    }

    protected function getUser(): Customer
    {
        $token = $this->tokenStorage->getToken();
        if (!$token || 'anon.' === $token->getUser()) {
            return new Customer();
        }

        return $user = $token->getUser();
    }
}
