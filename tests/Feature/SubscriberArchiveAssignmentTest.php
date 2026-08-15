<?php

namespace Tests\Feature;

use App\Http\Controllers\CP\Newsletter\ImportController;
use App\Http\Controllers\CP\Newsletter\SubscriberController;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Widgets\NewsletterWidget;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tests\TestCase;

class SubscriberArchiveAssignmentTest extends TestCase
{
    public function test_create_form_only_lists_assignable_sub_groups(): void
    {
        $active = $this->createSubGroup();
        $archived = $this->createSubGroup(['archived_at' => now()]);
        $childOfArchivedGroup = $this->createSubGroup([], ['archived_at' => now()]);

        $response = (new SubscriberController)->create();
        $ids = $response->getData()['subGroups']->modelKeys();

        $this->assertSame([$active->id], $ids);
        $this->assertNotContains($archived->id, $ids);
        $this->assertNotContains($childOfArchivedGroup->id, $ids);
    }

    public function test_store_rejects_archived_sub_group_assignment(): void
    {
        $archived = $this->createSubGroup(['archived_at' => now()]);

        $this->expectException(ValidationException::class);

        (new SubscriberController)->store($this->request('POST', [
            'email' => 'archived-store@example.test',
            'first_name' => 'Archived',
            'last_name' => 'Store',
            'status' => 'active',
            'sub_groups' => [$archived->id],
        ]));
    }

    public function test_update_rejects_archived_sub_group_and_preserves_existing_membership(): void
    {
        $active = $this->createSubGroup();
        $archived = $this->createSubGroup(['archived_at' => now()]);
        $subscriber = Subscriber::factory()->create(['email' => 'subscriber@example.test']);
        $subscriber->subGroups()->attach($active->id, ['subscribed_at' => now()]);

        try {
            (new SubscriberController)->update($this->request('PUT', [
                'email' => 'subscriber@example.test',
                'first_name' => 'Current',
                'last_name' => 'Subscriber',
                'status' => 'active',
                'sub_groups' => [$active->id, $archived->id],
            ]), $subscriber);

            $this->fail('Expected archived subgroup assignment validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sub_groups', $exception->errors());
        }

        $this->assertDatabaseHas('subscriber_sub_group', [
            'subscriber_id' => $subscriber->id,
            'subscriber_sub_group_id' => $active->id,
            'unsubscribed_at' => null,
        ]);
        $this->assertDatabaseMissing('subscriber_sub_group', [
            'subscriber_id' => $subscriber->id,
            'subscriber_sub_group_id' => $archived->id,
        ]);
    }

    public function test_index_can_filter_pending_subscribers(): void
    {
        Subscriber::factory()->pending()->create(['email' => 'pending@example.test']);
        Subscriber::factory()->active()->create(['email' => 'active@example.test']);

        $response = (new SubscriberController)->index($this->request('GET', [
            'status' => 'pending',
        ]));

        $subscribers = $response->getData()['subscribers'];

        $this->assertSame(1, $subscribers->total());
        $this->assertSame('pending@example.test', $subscribers->first()->email);
        $this->assertArrayHasKey('pending', $response->getData()['statuses']);
    }

    public function test_update_preserves_pending_subscriber_status(): void
    {
        $active = $this->createSubGroup();
        $subscriber = Subscriber::factory()->pending()->create(['email' => 'pending-update@example.test']);
        $subscriber->subGroups()->attach($active->id, ['subscribed_at' => now()]);

        $this->callUpdateIgnoringRedirect($this->request('PUT', [
            'email' => 'pending-update@example.test',
            'first_name' => 'Pending',
            'last_name' => 'Subscriber',
            'status' => 'pending',
            'sub_groups' => [$active->id],
        ]), $subscriber);

        $this->assertSame('pending', $subscriber->fresh()->status);
    }

    public function test_export_includes_pending_subscribers_when_filtered(): void
    {
        Subscriber::factory()->pending()->create(['email' => 'pending-export@example.test']);
        Subscriber::factory()->active()->create(['email' => 'active-export@example.test']);

        $response = (new ImportController)->export($this->request('GET', [
            'status' => 'pending',
        ]));

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('pending-export@example.test', $csv);
        $this->assertStringContainsString(',pending,', $csv);
        $this->assertStringNotContainsString('active-export@example.test', $csv);
    }

    public function test_widget_counts_pending_separately_from_active_subscribers(): void
    {
        Subscriber::factory()->pending()->create();
        Subscriber::factory()->active()->create();
        Subscriber::factory()->unsubscribed()->create();

        $widget = new NewsletterWidget;
        $widget->setConfig([]);

        $data = $widget->html()->getData();

        $this->assertSame(1, $data['subscriberStats']['pending']);
        $this->assertSame(1, $data['subscriberStats']['active']);
        $this->assertSame(1, $data['subscriberStats']['unsubscribed']);
    }

    public function test_import_form_only_lists_assignable_default_sub_groups(): void
    {
        $active = $this->createSubGroup();
        $archived = $this->createSubGroup(['archived_at' => now()]);
        $childOfArchivedGroup = $this->createSubGroup([], ['archived_at' => now()]);

        $response = (new ImportController)->form();
        $ids = $response->getData()['subGroups']->modelKeys();

        $this->assertSame([$active->id], $ids);
        $this->assertNotContains($archived->id, $ids);
        $this->assertNotContains($childOfArchivedGroup->id, $ids);
    }

    public function test_import_rejects_archived_default_sub_group_assignment(): void
    {
        $archived = $this->createSubGroup(['archived_at' => now()]);
        $file = $this->csvUpload("email\nreader@example.test\n");

        $this->expectException(ValidationException::class);

        (new ImportController)->import($this->request('POST', [
            'default_sub_groups' => [$archived->id],
        ], ['csv_file' => $file]));
    }

    public function test_import_does_not_assign_archived_csv_slug(): void
    {
        $archived = $this->createSubGroup(['slug' => 'old-interest', 'archived_at' => now()]);
        $file = $this->csvUpload("email,sub_groups\nreader@example.test,old-interest\n");

        $this->callImportIgnoringRedirect($this->request('POST', [], ['csv_file' => $file]));

        $this->assertDatabaseMissing('subscribers', [
            'email' => 'reader@example.test',
        ]);
        $this->assertDatabaseMissing('subscriber_sub_group', [
            'subscriber_sub_group_id' => $archived->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $subGroupAttributes
     * @param  array<string, mixed>  $groupAttributes
     */
    private function createSubGroup(array $subGroupAttributes = [], array $groupAttributes = []): SubscriberSubGroup
    {
        $group = SubscriberGroup::factory()->create($groupAttributes);

        return SubscriberSubGroup::factory()->create(array_merge([
            'subscriber_group_id' => $group->id,
        ], $subGroupAttributes));
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $files
     */
    private function request(string $method, array $parameters = [], array $files = []): Request
    {
        return Request::create('/cp/newsletter/subscribers', $method, $parameters, [], $files);
    }

    private function csvUpload(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'subscriber-import-');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'subscribers.csv', 'text/csv', null, true);
    }

    private function callImportIgnoringRedirect(Request $request): void
    {
        try {
            (new ImportController)->import($request);
        } catch (UrlGenerationException|RouteNotFoundException|\InvalidArgumentException $exception) {
            if (! str_contains($exception->getMessage(), 'Route') && ! str_contains($exception->getMessage(), 'not defined')) {
                throw $exception;
            }
        }
    }

    private function callUpdateIgnoringRedirect(Request $request, Subscriber $subscriber): void
    {
        try {
            (new SubscriberController)->update($request, $subscriber);
        } catch (UrlGenerationException|RouteNotFoundException|\InvalidArgumentException $exception) {
            if (! str_contains($exception->getMessage(), 'Route') && ! str_contains($exception->getMessage(), 'not defined')) {
                throw $exception;
            }
        }
    }
}
