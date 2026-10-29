<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OnikiController extends Controller
{
    public function home()
    {
        return view('oniki.home', ['properties' => Listing::where('published', true)->where('kind', 'property')->latest()->limit(3)->get(), 'jobs' => Listing::where('published', true)->where('kind', 'job')->latest()->limit(3)->get()]);
    }

    private function filtered(Request $request, string $kind)
    {
        $request->validate([
            'region' => ['nullable', Rule::in(array_keys(config('property.regions')))],
            'prefecture' => ['nullable', Rule::in(array_keys(config('property.prefectures')))],
            'district' => 'nullable|string|max:150', 'railway_line' => 'nullable|string|max:150', 'station_name' => 'nullable|string|max:200',
            'property_types' => 'nullable|array|max:8', 'property_types.*' => Rule::in(config('property.property_types')),
            'layouts' => 'nullable|array|max:14', 'layouts.*' => Rule::in(config('property.layouts')),
            'amenities' => 'nullable|array|max:12', 'amenities.*' => Rule::in(config('property.amenities')),
            'topic' => ['nullable', Rule::in(config('property.topics'))],
            'min_area' => 'nullable|numeric|min:1|max:999999', 'max_area' => ['nullable', 'numeric', 'min:1', 'max:999999', Rule::when($request->filled('min_area'), 'gte:min_area')],
            'walk_max' => 'nullable|integer|min:0|max:120', 'age_max' => 'nullable|integer|min:0|max:200',
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc'])],
            'q' => 'nullable|string|max:100', 'category' => ['nullable', Rule::in(['rent', 'buy', 'fulltime', 'parttime'])],
            'city' => ['nullable', Rule::in(['osaka', 'tokyo', 'kyoto', 'kobe', 'fukuoka'])],
            'min_price' => 'nullable|integer|min:0|max:100000000000',
            'max_price' => ['nullable', 'integer', 'min:0', 'max:100000000000', Rule::when($request->filled('min_price'), 'gte:min_price')],
            'floor_plan' => ['nullable', Rule::in(config('property.layouts'))],
            'mode' => ['nullable', Rule::in(['map', 'list'])],
            'north' => 'nullable|required_with:south,east,west|numeric|between:20,46',
            'south' => 'nullable|required_with:north,east,west|numeric|between:20,46|lte:north',
            'east' => 'nullable|required_with:north,south,west|numeric|between:122,154',
            'west' => 'nullable|required_with:north,south,east|numeric|between:122,154|lte:east',
        ]);
        $query = Listing::where('published', true)->where('kind', $kind);
        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(fn ($b) => $b->where('title_'.app()->getLocale(), 'like', $q)->orWhere('location', 'like', $q)->orWhere('station', 'like', $q)->orWhere('district', 'like', $q)->orWhere('railway_line', 'like', $q));
        }
        foreach (['category', 'city', 'floor_plan', 'prefecture', 'district', 'railway_line'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->query($field));
            }
        }
        if ($request->filled('min_price')) {
            $query->where('price_yen', '>=', $request->integer('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price_yen', '<=', $request->integer('max_price'));
        }
        if ($request->filled('north')) {
            $query->whereBetween('latitude', [$request->query('south'), $request->query('north')])->whereBetween('longitude', [$request->query('west'), $request->query('east')]);
        }

        if ($request->filled('region')) {
            $query->whereIn('prefecture', config('property.regions.'.$request->query('region')));
        }
        if ($request->filled('station_name')) {
            $query->where('station', $request->query('station_name'));
        }
        if ($request->filled('property_types')) {
            $query->whereIn('property_type', $request->input('property_types'));
        }
        if ($request->filled('layouts')) {
            $query->whereIn('floor_plan', $request->input('layouts'));
        }
        foreach ($request->input('amenities', []) ?? [] as $amenity) {
            $query->whereJsonContains('amenities', $amenity);
        }
        if ($request->filled('min_area')) {
            $query->where('area_sqm', '>=', $request->input('min_area'));
        }
        if ($request->filled('max_area')) {
            $query->where('area_sqm', '<=', $request->input('max_area'));
        }
        if ($request->filled('walk_max')) {
            $query->where('walk_minutes', '<=', $request->input('walk_max'));
        }
        if ($request->filled('age_max')) {
            $query->where('building_age', '<=', $request->input('age_max'));
        }
        switch ($request->query('topic')) {
            case 'furnished':$query->whereJsonContains('amenities', 'furnished');
                break;
            case 'new_build':$query->where('building_age', '<', 10);
                break;
            case 'near_station':$query->where('walk_minutes', '<=', 10);
                break;
            case 'low_initial':$query->where('initial_cost_yen', '<=', 100000);
                break;
            case 'under_50000':$query->where('category', 'rent')->where('price_yen', '<=', 50000);
                break;
            case 'overseas':$query->where('apply_overseas', true);
                break;
            case 'family':$query->whereIn('floor_plan', ['2DK', '2LDK', '3K', '3DK', '3LDK', '4K', '4DK', '4LDK', '5LDK']);
                break;
        }

        return $query;
    }

    public function index(Request $request, string $locale, string $kind)
    {
        $query = $this->filtered($request, $kind);
        $points = $kind === 'property' ? $this->points((clone $query)) : collect();

        $sort = $request->query('sort');
        if (in_array($sort, ['price_asc', 'price_desc'])) {
            $query->orderByRaw('price_yen IS NULL')->orderBy('price_yen', $sort === 'price_asc' ? 'asc' : 'desc');
        }

        return view('oniki.index', ['kind' => $kind, 'listings' => $query->latest()->paginate($kind === 'property' ? 12 : 9)->withQueryString(), 'points' => $points]);
    }

    private function facets(Request $request)
    {
        $query = Listing::where('published', true)->where('kind', 'property');
        if ($request->filled('region')) {
            $query->whereIn('prefecture', config('property.regions.'.$request->query('region'), []));
        }
        $prefectureQuery = clone $query;
        if ($request->filled('prefecture')) {
            $query->where('prefecture', $request->query('prefecture'));
        }

        return [
            'prefectureCounts' => $prefectureQuery->whereNotNull('prefecture')->selectRaw('prefecture, COUNT(*) as total')->groupBy('prefecture')->pluck('total', 'prefecture'),
            'districts' => (clone $query)->whereNotNull('district')->where('district', '!=', '')->selectRaw('district, COUNT(*) as total')->groupBy('district')->orderBy('district')->get(),
            'stations' => (clone $query)->whereNotNull('station')->where('station', '!=', '')->selectRaw('railway_line, station, COUNT(*) as total')->groupBy('railway_line', 'station')->orderBy('railway_line')->orderBy('station')->get(),
        ];
    }

    public function finder(Request $request)
    {
        if (! $request->has('region')) {
            $request->merge(['region' => 'kansai']);
        }
        $request->validate(['method' => ['nullable', Rule::in(['area', 'station'])]]);
        $this->filtered($request, 'property');

        return view('oniki.finder', $this->facets($request));
    }

    public function mapPage(Request $request)
    {
        $query = $this->filtered($request, 'property');

        return view('oniki.map-page', array_merge($this->facets($request), [
            'listings' => (clone $query)->latest()->paginate(12)->withQueryString(),
            'points' => $this->points(clone $query),
        ]));
    }

    private function points($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude')->latest()->limit(500)->get()->map(fn ($p) => [
            'id' => $p->id, 'lat' => $p->latitude, 'lng' => $p->longitude, 'title' => $p->local('title'),
            'price' => $p->local('price') ?: __('oniki.pricepending'), 'location' => $p->location,
            'url' => route('oniki.show', ['kind' => 'property', 'listing' => $p]),
            'image' => $p->cover_path ? route('content.image', $p) : null,
        ]);
    }

    public function mapPoints(Request $request)
    {
        return response()->json(['points' => $this->points($this->filtered($request, 'property'))]);
    }

    public function show(string $locale, string $kind, Listing $listing)
    {
        abort_unless($listing->published && $listing->kind === $kind, 404);

        return view('oniki.show', compact('listing'));
    }

    public function contact(Request $request)
    {
        $request->validate(['listing' => 'nullable|integer', 'service' => ['nullable', Rule::in(['property', 'job', 'other'])]]);
        $listing = Listing::where('published', true)->find($request->query('listing'));

        return view('oniki.contact', compact('listing'));
    }

    public function inquiry(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255', 'phone' => 'nullable|string|max:40', 'service' => ['required', Rule::in(['property', 'job', 'other'])], 'message' => 'required|string|min:10|max:5000', 'consent' => 'accepted', 'website' => 'nullable|max:0', 'listing_id' => ['nullable', Rule::exists('listings', 'id')->where('published', true)]]);
        Inquiry::create([...collect($data)->only(['name', 'email', 'phone', 'service', 'message', 'listing_id'])->all(), 'locale' => app()->getLocale()]);

        return redirect()->route('oniki.contact')->with('success', __('oniki.sent'));
    }

    public function manage(Request $request)
    {
        $query = Listing::query();
        if ($request->user()->role !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }

        return view('oniki.manage', ['listings' => $query->latest()->paginate(15)]);
    }

    public function edit(Request $request, ?Listing $listing = null)
    {
        if ($listing) {
            abort_unless($request->user()->role === 'admin' || $listing->user_id === $request->user()->id, 403);
        }

        return view('oniki.edit', ['listing' => $listing ?? new Listing]);
    }

    public function save(Request $request, ?Listing $listing = null)
    {
        if ($listing) {
            abort_unless($request->user()->role === 'admin' || $listing->user_id === $request->user()->id, 403);
        }
        $data = $request->validate(['kind' => ['required', Rule::in(['property', 'job'])], 'title_vi' => 'required|string|max:200', 'title_ja' => 'required|string|max:200', 'body_vi' => 'required|string|max:10000', 'body_ja' => 'required|string|max:10000', 'location' => 'required|string|max:150', 'category' => ['required', Rule::in(['rent', 'buy', 'fulltime', 'parttime'])], 'price_vi' => 'nullable|string|max:150', 'price_ja' => 'nullable|string|max:150', 'published' => 'nullable|boolean']);
        if (! in_array($data['category'], $data['kind'] === 'property' ? ['rent', 'buy'] : ['fulltime', 'parttime'])) {
            throw ValidationException::withMessages(['category' => 'Phân loại chưa phù hợp với dịch vụ đã chọn.']);
        }
        $property = $request->validate([
            'city' => ['nullable', Rule::in(['osaka', 'tokyo', 'kyoto', 'kobe', 'fukuoka'])],
            'latitude' => 'nullable|required_with:longitude|numeric|between:20,46',
            'longitude' => 'nullable|required_with:latitude|numeric|between:122,154',
            'price_yen' => 'nullable|integer|min:0|max:100000000000',
            'area_sqm' => 'nullable|numeric|min:1|max:999999',
            'floor_plan' => ['nullable', Rule::in(['1R', '1K', '1DK', '1LDK', '2K', '2DK', '2LDK', '3LDK', '4LDK'])],
            'station' => 'nullable|string|max:200',
        ]);
        foreach (['city', 'latitude', 'longitude', 'price_yen', 'area_sqm', 'floor_plan', 'station'] as $field) {
            $data[$field] = $data['kind'] === 'property' ? ($property[$field] ?? null) : null;
        }

        $conditions = $request->validate([
            'prefecture' => ['nullable', Rule::in(array_keys(config('property.prefectures')))],
            'district' => 'nullable|string|max:150', 'railway_line' => 'nullable|string|max:150',
            'property_type' => ['nullable', Rule::in(config('property.property_types'))],
            'walk_minutes' => 'nullable|integer|min:0|max:120', 'building_age' => 'nullable|integer|min:0|max:200', 'initial_cost_yen' => 'nullable|integer|min:0|max:100000000000',
            'apply_overseas' => 'nullable|boolean', 'amenities' => 'nullable|array|max:12', 'amenities.*' => Rule::in(config('property.amenities')),
        ]);
        foreach (['prefecture', 'district', 'railway_line', 'property_type', 'walk_minutes', 'building_age', 'initial_cost_yen'] as $field) {
            $data[$field] = $data['kind'] === 'property' ? ($conditions[$field] ?? null) : null;
        }
        if ($data['kind'] === 'property' && ! $data['prefecture'] && $data['city']) {
            $data['prefecture'] = config('property.city_prefectures.'.$data['city']);
        }
        $data['amenities'] = $data['kind'] === 'property' ? ($conditions['amenities'] ?? []) : [];
        $data['apply_overseas'] = $data['kind'] === 'property' && $request->boolean('apply_overseas');

        $request->validate(['cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000']]);
        $data['published'] = $request->user()->role === 'admin' && $request->boolean('published');
        $listing ??= new Listing;
        $listing->fill($data);
        if (! $listing->exists) {
            $listing->user_id = $request->user()->id;
        }
        $oldCover = $listing->cover_path;
        if ($request->hasFile('cover')) {
            $listing->cover_path = $request->file('cover')->store('listing-covers', 'local');
        }
        try {
            $listing->save();
        } catch (\Throwable $error) {
            if ($listing->cover_path && $listing->cover_path !== $oldCover) {
                Storage::disk('local')->delete($listing->cover_path);
            }
            throw $error;
        }
        if ($oldCover && $oldCover !== $listing->cover_path) {
            Storage::disk('local')->delete($oldCover);
        }

        return redirect()->route('content.index')->with('success', 'Đã lưu tin. Tin của nhân viên cần quản trị viên duyệt trước khi hiển thị.');
    }

    public function remove(Request $request, Listing $listing)
    {
        abort_unless($request->user()->role === 'admin' || ($listing->user_id === $request->user()->id && ! $listing->published), 403);
        $cover = $listing->cover_path;
        $listing->delete();
        if ($cover) {
            Storage::disk('local')->delete($cover);
        }

        return back()->with('success', 'Đã xóa tin.');
    }

    public function image(Request $request, Listing $listing)
    {
        abort_unless($listing->published || ($request->user()?->active && ($request->user()->role === 'admin' || $request->user()->id === $listing->user_id)), 404);
        abort_unless($listing->cover_path && Storage::disk('local')->exists($listing->cover_path), 404);

        return Storage::disk('local')->response($listing->cover_path, null, ['Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function inquiries()
    {
        return view('oniki.inquiries', ['inquiries' => Inquiry::with('listing')->latest()->paginate(20)]);
    }

    public function handled(Inquiry $inquiry)
    {
        $inquiry->handled = ! $inquiry->handled;
        $inquiry->save();

        return back()->with('success', 'Đã cập nhật yêu cầu.');
    }
}
