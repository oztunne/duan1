<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnikiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'staff'): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role])->save();

        return $u;
    }

    private function data(): array
    {
        return ['kind' => 'property', 'title_vi' => 'Nhà tại Osaka', 'title_ja' => '大阪の住まい', 'body_vi' => 'Nội dung bất động sản kiểm thử.', 'body_ja' => 'テスト用の物件情報です。', 'location' => 'Osaka', 'category' => 'rent', 'price_vi' => '100.000 yên/tháng', 'price_ja' => '月額100,000円', 'published' => true];
    }

    private function listing(User $u, bool $published = true): Listing
    {
        $p = new Listing($this->data());
        $p->user_id = $u->id;
        $p->published = $published;
        $p->save();

        return $p;
    }

    public function test_bilingual_public_pages(): void
    {
        $this->get('/')->assertOk()->assertSee('ONIKI');
        $this->get('/vi')->assertOk()->assertSee('Một nơi để an tâm.');
        $this->get('/ja')->assertOk()->assertSee('安心できる場所から。')->assertSee('lang="ja"', false);
        foreach (['/vi/listings/property', '/ja/listings/job', '/vi/about', '/ja/contact', '/ja/privacy'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/en')->assertNotFound();
    }

    public function test_drafts_hidden_and_filters_localized(): void
    {
        $u = $this->user();
        $visible = $this->listing($u);
        $draft = $this->listing($u, false);
        $draft->update(['title_vi' => 'Secret draft']);
        $this->get('/vi/listings/property')->assertOk()->assertSee('Nhà tại Osaka')->assertDontSee('Secret draft');
        $this->get('/ja/listings/property/'.$visible->id)->assertOk()->assertSee('大阪の住まい');
        $this->get('/vi/listings/property/'.$draft->id)->assertNotFound();
        $this->get('/vi/listings/job/'.$visible->id)->assertNotFound();
        $this->get('/vi/listings/property?q=Kyoto')->assertOk()->assertDontSee('Nhà tại Osaka');
        $this->get('/vi/listings/property?category=buy')->assertOk()->assertDontSee('Nhà tại Osaka');
    }

    public function test_admin_publishes_and_staff_cannot_publish_or_edit_others(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user();
        $other = $this->user();
        $this->actingAs($admin)->post('/content', $this->data())->assertRedirect('/content');
        $this->assertDatabaseHas('listings', ['title_vi' => 'Nhà tại Osaka', 'published' => true]);
        $p = Listing::firstOrFail();
        $this->actingAs($staff)->get('/content/'.$p->id.'/edit')->assertForbidden();
        $this->put('/content/'.$p->id, $this->data())->assertForbidden();
        $this->delete('/content/'.$p->id)->assertForbidden();
        $this->post('/content', $this->data())->assertRedirect('/content');
        $own = Listing::where('user_id', $staff->id)->firstOrFail();
        $this->assertFalse($own->published);
        $this->get('/content')->assertOk();
        $this->get('/content/create')->assertOk();
        $this->get('/content/'.$own->id.'/edit')->assertOk();
        $this->actingAs($admin)->put('/content/'.$own->id, $this->data())->assertRedirect('/content');
        $this->assertTrue($own->fresh()->published);
        $this->actingAs($staff)->put('/content/'.$own->id, $this->data())->assertRedirect('/content');
        $this->assertFalse($own->fresh()->published);
    }

    public function test_inquiries_saved_and_admin_only_can_read(): void
    {
        $data = ['name' => 'Test customer', 'email' => 'customer@example.test', 'service' => 'job', 'message' => 'Tôi cần thông tin tư vấn việc làm.', 'consent' => 1];
        $this->post('/ja/contact', $data)->assertRedirect('/ja/contact');
        $this->assertDatabaseHas('inquiries', ['email' => 'customer@example.test', 'locale' => 'ja']);
        $this->get('/inquiries')->assertRedirect('/login');
        $this->actingAs($this->user())->get('/inquiries')->assertForbidden();
        $this->actingAs($this->user('admin'))->get('/inquiries')->assertOk()->assertSee('Test customer');
        $inquiry = Inquiry::firstOrFail();
        $this->patch('/inquiries/'.$inquiry->id)->assertRedirect();
        $this->assertTrue($inquiry->fresh()->handled);
    }

    public function test_inquiry_requires_consent_and_published_listing(): void
    {
        $u = $this->user();
        $draft = $this->listing($u, false);
        $data = ['name' => 'Test', 'email' => 'customer@example.test', 'service' => 'property', 'message' => 'Tôi muốn tư vấn thêm.', 'listing_id' => $draft->id];
        $this->post('/vi/contact', $data)->assertSessionHasErrors(['consent', 'listing_id']);
        $this->assertDatabaseCount('inquiries', 0);
        $data['consent'] = 1;
        unset($data['listing_id']);
        $data['website'] = 'spam';
        $this->post('/vi/contact', $data)->assertSessionHasErrors('website');
        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_image_upload_and_private_draft_image(): void
    {
        Storage::fake('local');
        $admin = $this->user('admin');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6ZkAAAAASUVORK5CYII=');
        $data = $this->data();
        $data['cover'] = UploadedFile::fake()->createWithContent('cover.png', $png);
        $this->actingAs($admin)->post('/content', $data)->assertSessionHasNoErrors()->assertRedirect('/content');
        $listing = Listing::firstOrFail();
        $this->assertNotNull($listing->cover_path);
        Storage::disk('local')->assertExists($listing->cover_path);
        $this->get('/listing-images/'.$listing->id)->assertOk();
        $listing->update(['published' => false]);
        $this->post('/logout');
        $this->get('/listing-images/'.$listing->id)->assertNotFound();
    }
}
