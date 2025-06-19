<?php

namespace Tests\Feature;

use App\Models\Hackathon;
use App\Models\Role;
use App\Models\Tab;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TabRequestTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @throws \JsonException
     */
    public function test_authorized_user_can_submit_valid_tab_request(): void
    {
        $this->withoutExceptionHandling();
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignedRole(Role::ORGANIZER);
        $h = Hackathon::factory()->create(['is_published' => false, 'user_id' => $user->id]);

        foreach (Tab::defaultStructure() as $tabTitle => $sections) {
            $tab = Tab::factory()->create(['title' => $tabTitle, 'hackathon_id' => $h->id]);
            foreach ($sections as $sectionTitle) {
                $tab->sections()->create(['title' => $sectionTitle]);
            }
        }

        $this->actingAs($user);

        $response = $this->patch(route('hackathons.tabs.update', $h), [
            'title' => Tab::TAB_TITLES[0],
            'partners' => [UploadedFile::fake()->image('partner1.jpg'), UploadedFile::fake()->image('partner2.jpg')],
            'files' => [UploadedFile::fake()->create('doc1.pdf'), UploadedFile::fake()->create('doc2.pdf'), UploadedFile::fake()->create('doc3.pdf')],
            'delete_media_ids' => [],
        ]);

        $response->assertRedirect();

        $tab = $h->tabs()->first();

        $this->assertCount(2, $tab->getMedia('partner_images'));
        $this->assertCount(3, $tab->getMedia('files'));

        $mediaIds = [];
        $mediaIds[] = $tab->getMedia('partner_images')->first()->id;
        $mediaIds[] = $tab->getMedia('files')->first()->id;

        $response = $this->patch(route('hackathons.tabs.update', $h), [
            'title' => Tab::TAB_TITLES[0],
            'delete_media_ids' => $mediaIds,
        ]);

        $response->assertSessionHasNoErrors();

        $tab->refresh();

        $this->assertCount(1, $tab->getMedia('partner_images'));
        $this->assertCount(2, $tab->getMedia('files'));
    }

    public function test_validation_fails_with_invalid_data()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $hackathon = Hackathon::factory()->for($user, 'organizer')->create();

        $this->actingAs($user);

        $response = $this->patch(route('hackathons.tabs.update', $hackathon), [
            'title' => 'Некорректный таб',
            'partners' => ['не_картинка'],
            'files' => ['не_файл'],
            'delete_media_ids' => ['не_число'],
        ]);

        $response->assertSessionHasErrors([
            'title',
            'partners.0',
            'files.0',
            'delete_media_ids.0',
        ]);
    }

    public function test_unauthorized_user_cannot_submit_tab()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $hackathon = Hackathon::factory()->create(); // другой организатор

        $this->actingAs($user);

        $response = $this->patch(route('hackathons.tabs.update', $hackathon), [
            'title' => Tab::TAB_TITLES[0],
        ]);

        $response->assertStatus(403);
    }
}
