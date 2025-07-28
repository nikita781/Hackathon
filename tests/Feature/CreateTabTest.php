<?php

namespace Tests\Feature;

use App\Models\Hackathon;
use App\Models\Role;
use App\Models\Tab;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateTabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
    }

    /**
     * @throws \JsonException
     */
    public function test_authorized_user_can_submit_valid_tab_request(): void
    {
        $this->withoutExceptionHandling();
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignedRole(Role::ORGANIZER);
        $h = Hackathon::factory()->create(['status' => Hackathon::STATUS_DRAFT, 'user_id' => $user->id]);

        foreach (Tab::defaultStructure() as $tabTitle => $sections) {
            $tab = Tab::factory()->create(['title' => $tabTitle, 'hackathon_id' => $h->id]);
            foreach ($sections as $sectionTitle) {
                $tab->sections()->create(['title' => $sectionTitle]);
            }
        }

        $this->actingAs($user);

        $tab = $h->tabs()->first();

        $sections = $tab->sections()->get();

        $response = $this->patch(route('hackathons.tabs.update', $h), [
            'title' => Tab::TAB_TITLES[0],
            'sections' => [
                [
                    'id' => $sections[0]->id,
                    'title' => $sections[0]->title,
                    'content' => '',
                    'items' => [
                        ['title' => 'ItemTitle1', 'content' => 'content1'],
                        ['title' => 'ItemTitle2', 'content' => ''],
                    ]
                ],
                [
                    'id' => '',
                    'title' => $sections[1]->title,
                    'content' => 'fjadhfkjhadfjkh',
                ],
            ],
            'partners' => [UploadedFile::fake()->image('partner1.jpg'), UploadedFile::fake()->image('partner2.jpg')],
            'files' => [UploadedFile::fake()->create('doc1.pdf'), UploadedFile::fake()->create('doc2.pdf'), UploadedFile::fake()->create('doc3.pdf')],
            'delete_media_ids' => [],
        ]);

        $response->assertOk();

        $sections = $tab->sections()->get();

        $this->assertDatabaseHas('tab_sections', [
            'tab_id' => $tab->id,
            'title' => $sections[0]->title,
        ]);

        $this->assertDatabaseHas('tab_items', [
            'tab_section_id' => $sections[0]->id,
            'title' => 'ItemTitle2',
            'content' => null,
        ]);

        $this->assertDatabaseHas('tab_sections', [
            'tab_id' => $tab->id,
            'title' => $sections[1]->title,
            'content' => 'fjadhfkjhadfjkh',
        ]);

        $response = $this->patch(route('hackathons.tabs.update', $h), [
            'title' => Tab::TAB_TITLES[0],
            'sections' => [
                [
                    'id' => $sections[0]->id,
                    'title' => 'CustomSectionTitle1',
                    'content' => '1',
                    'items' => [
                        ['id' => $sections[0]->items()->get()[0]->id, 'title' => 'CustomItemTitle1', 'content' => 'content1111'],
                        ['id' => $sections[0]->items()->get()[1]->id, 'title' => 'ItemTitle2', 'content' => ''],
                    ]
                ],
                [
                    'id' => $sections[1]->id,
                    'title' => 'CustomSectionTitle2',
                    'content' => '',
                ],
            ],
        ]);

        $tab->refresh();
        $this->assertCount(2, $tab->sections()->get());
        $this->assertCount(2, $sections[0]->items()->get());


        $this->assertDatabaseHas('tab_sections', [
            'tab_id' => $tab->id,
            'title' => 'CustomSectionTitle1',
            'content' => '1',
        ]);

        $this->assertDatabaseHas('tab_items', [
            'id' => $sections[0]->items()->get()[0]->id,
            'tab_section_id' => $sections[0]->id,
            'title' => 'CustomItemTitle1',
            'content' => 'content1111',
        ]);

        $this->assertDatabaseHas('tab_items', [
            'id' => $sections[0]->items()->get()[1]->id,
            'tab_section_id' => $sections[0]->id,
            'title' => 'ItemTitle2',
            'content' => null,
        ]);

        $this->assertDatabaseHas('tab_sections', [
            'tab_id' => $tab->id,
            'title' => 'CustomSectionTitle2',
            'content' => null,
        ]);

        $response = $this->patch(route('hackathons.tabs.update', $h), [
            'title' => Tab::TAB_TITLES[0],
            'sections' => [
                [
                    'id' => $sections[0]->id,
                    'title' => 'CustomSectionTitle1',
                    'content' => '1',
                    'items' => [
                        ['id' => $sections[0]->items()->get()[0]->id, 'title' => 'CustomItemTitle1', 'content' => 'content1111'],
                    ]
                ]
            ],
        ]);

        $tab->refresh();
        $this->assertCount(1, $tab->sections()->get());
        $this->assertCount(1, $sections[0]->items()->get());

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

    public function test_validation_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $user->assignedRole(Role::ORGANIZER);
        $h = Hackathon::factory()->create(['status' => Hackathon::STATUS_DRAFT, 'user_id' => $user->id]);

        foreach (Tab::defaultStructure() as $tabTitle => $sections) {
            $tab = Tab::factory()->create(['title' => $tabTitle, 'hackathon_id' => $h->id]);
            foreach ($sections as $sectionTitle) {
                $tab->sections()->create(['title' => $sectionTitle]);
            }
        }

        $this->actingAs($user);

        $response = $this->patch(route('hackathons.tabs.update', $h), [
            'title' => 'Некорректный таб',
            'partners' => ['не_картинка'],
            'delete_media_ids' => ['не_число'],
        ]);

        $response->assertSessionHasErrors([
            'title',
            'partners.0',
            'delete_media_ids.0',
        ]);
    }

    public function test_unauthorized_user_cannot_submit_tab()
    {
        $user = User::factory()->create();
        $org = User::factory()->create(['id' => 3]);
        $user->assignedRole(Role::ORGANIZER);
        $org->assignedRole(Role::ORGANIZER);
        $h = Hackathon::factory()->create(['status' => Hackathon::STATUS_DRAFT]);

        foreach (Tab::defaultStructure() as $tabTitle => $sections) {
            $tab = Tab::factory()->create(['title' => $tabTitle, 'hackathon_id' => $h->id]);
            foreach ($sections as $sectionTitle) {
                $tab->sections()->create(['title' => $sectionTitle]);
            }
        }

        $this->actingAs($user);

        $response = $this->patch(route('hackathons.tabs.update', $h), [
            'title' => Tab::TAB_TITLES[0],
        ]);

        $response->assertStatus(404);
    }
}
