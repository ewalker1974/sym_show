<?php

namespace App\Customer\Wishlist;

use App\Document\Product;
use App\Model\Response\PaginatedListResponseInterface;

interface WishlistInterface
{
    public function getItems(int $page = 1, int $perPage = 10): PaginatedListResponseInterface;
    public function isInWishList(Product $product): bool;
    public function addWishListItem(Product $product): void;
    public function deleteWishListItem($productId): void;
}
