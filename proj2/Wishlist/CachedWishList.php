<?php

namespace App\Customer\Wishlist;

use App\Document\Product;
use Psr\SimpleCache\CacheInterface;

final class CachedWishList extends Wishlist
{
    /** @var CacheInterface */
    private $cache;

    protected function fetchAll($force = false): void
    {
        $key = $this->getUserCacheKey();
        if ($force) {
            $this->cache->delete($key);
        }

        if (!$this->cache->has($key)) {
            parent::fetchAll();
            $this->cache->set($key, $this->items);
        }

        $this->items = $this->cache->get($key);
    }

    public function addWishListItem(Product $product): void
    {
        parent::addWishListItem($product);
        $this->fetchAll(true);
        $items = $this->items->getItems();
        array_unshift($items, $product->entity_id);
        $this->items->setItems($items);
        $this->cache->set($this->getUserCacheKey(), $this->items);
    }

    public function deleteWishListItem($productId): void
    {
        parent::deleteWishListItem($productId);
        $this->fetchAll(true);
        $items = $this->items->getItems();
        foreach ($items as $key => $id) {
            if ($productId == $id) {
                unset($items[$key]);
            }
        }
        $this->items->setItems($items);
        $this->cache->set($this->getUserCacheKey(), $this->items);
    }

    /**
     * @required
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    private function getUserCacheKey()
    {
        return 'wishlist.'.$this->getUser()->getId();
    }
}
