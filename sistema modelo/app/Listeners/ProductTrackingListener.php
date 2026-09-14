<?php

namespace App\Listeners;

use App\Events\ProductTrackingEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ProductHistory;

class ProductTrackingListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProductTrackingEvent $event): void
    {
        $product = $event->product;
        $user = $event->user;

        $changes = $product->getDirty();
        $original = $product->getOriginal();

        $data = [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_key_before' => $original['product_key'],
            'product_key_after' => isset($changes['product_key']) ? $changes['product_key'] : $original['product_key'],
            'description_before' => $original['description'],
            'description_after' => isset($changes['description']) ? $changes['description'] : $original['description'],
            'qty_before' => $original['qty'],
            'qty_after' => isset($changes['qty']) ? $changes['qty'] : $original['qty'],
            'min_qty_before' => $original['min_qty'],
            'min_qty_after' => isset($changes['min_qty']) ? $changes['min_qty'] : $original['min_qty'],
            'max_qty_before' => $original['max_qty'],
            'max_qty_after' => isset($changes['max_qty']) ? $changes['max_qty'] : $original['max_qty'],
            'cost_before' => $original['cost'],
            'cost_after' => isset($changes['cost']) ? $changes['cost'] : $original['cost'],
            'price_before' => $original['price'],
            'price_after' => isset($changes['price']) ? $changes['price'] : $original['price'],
            'brand_id_before' => $original['brand_id'],
            'brand_id_after' => isset($changes['brand_id']) ? $changes['brand_id'] : $original['brand_id'],
            'vendor_id_before' => $original['vendor_id'],
            'vendor_id_after' => isset($changes['vendor_id']) ? $changes['vendor_id'] : $original['vendor_id'],
            'category_id_before' => $original['category_id'],
            'category_id_after' => isset($changes['category_id']) ? $changes['category_id'] : $original['category_id'],
        ];
        ProductHistory::create($data);
    }
}
