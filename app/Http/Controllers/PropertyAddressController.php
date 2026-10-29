<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PropertyAddressController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate(['address' => 'required|string|min:3|max:300']);
        $address = trim($data['address']);
        try {
            $results = Cache::remember('property-address:'.hash('sha256', $address), now()->addDay(), function () use ($address) {
                $response = Http::acceptJson()->connectTimeout(5)->timeout(12)
                    ->get(config('oniki.address_search_url'), ['q' => $address])->throw();
                if (! is_array($response->json())) {
                    throw new \RuntimeException('Invalid address response');
                }

                return collect($response->json())->filter(function ($item) {
                    $coordinates = data_get($item, 'geometry.coordinates');

                    return data_get($item, 'geometry.type') === 'Point'
                        && is_array($coordinates) && count($coordinates) === 2
                        && is_numeric($coordinates[0]) && is_numeric($coordinates[1])
                        && $coordinates[0] >= 122 && $coordinates[0] <= 154
                        && $coordinates[1] >= 20 && $coordinates[1] <= 46;
                })->take(5)->map(fn ($item) => [
                    'label' => (string) data_get($item, 'properties.title', $address),
                    'latitude' => (float) $item['geometry']['coordinates'][1],
                    'longitude' => (float) $item['geometry']['coordinates'][0],
                ])->values()->all();
            });
        } catch (\Throwable $error) {
            return response()->json(['message' => 'Chưa kết nối được dịch vụ tìm địa chỉ. Bạn có thể chọn vị trí trực tiếp trên bản đồ.'], 503);
        }

        return response()->json(['results' => $results]);
    }
}
