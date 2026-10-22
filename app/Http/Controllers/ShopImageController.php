<?php

namespace App\Http\Controllers;

use App\Models\ShopItem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShopImageController extends Controller
{
    public function __invoke(ShopItem $shopItem): StreamedResponse
    {
        abort_unless($shopItem->image_path && Storage::disk('local')->exists($shopItem->image_path), 404);

        return Storage::disk('local')->response($shopItem->image_path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
