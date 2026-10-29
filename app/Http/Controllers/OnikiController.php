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

    public function index(Request $request, string $locale, string $kind)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'category' => ['nullable', Rule::in(['rent', 'buy', 'fulltime', 'parttime'])]]);
        $query = Listing::where('published', true)->where('kind', $kind);
        if ($request->filled('q')) {
            $q = '%'.mb_substr((string) $request->query('q'), 0, 100).'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('title_'.app()->getLocale(), 'like', $q)->orWhere('location', 'like', $q);
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        return view('oniki.index', ['kind' => $kind, 'listings' => $query->latest()->paginate(9)->withQueryString()]);
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
