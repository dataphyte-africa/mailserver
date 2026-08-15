<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\Platform\Authorization\StatamicUserIdentityBridge;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\Facade;
use LogicException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Statamic\Contracts\Auth\User as StatamicUser;

class StatamicUserIdentityBridgeTest extends TestCase
{
    private StatamicUserIdentityBridge $bridge;

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('users', function ($table) {
            $table->id();
            $table->string('statamic_user_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        $app = new Container;
        $app->instance('config', new Repository([
            'hashing' => [
                'driver' => 'bcrypt',
                'bcrypt' => [
                    'rounds' => 4,
                    'verify' => true,
                    'limit' => null,
                ],
            ],
        ]));
        $app->singleton('hash', fn ($app) => new HashManager($app));
        Facade::setFacadeApplication($app);

        $this->bridge = new StatamicUserIdentityBridge;
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    public function test_resolves_a_single_explicitly_linked_relational_user(): void
    {
        $user = $this->createRelationalUser('operator@example.com', 'statamic-123');

        $resolved = $this->bridge->resolve($this->statamicUser('statamic-123', 'operator@example.com'));

        $this->assertSame($user->getKey(), $resolved?->getKey());
    }

    public function test_runtime_resolution_does_not_fall_back_to_matching_email(): void
    {
        $this->createRelationalUser('operator@example.com');

        $resolved = $this->bridge->resolve($this->statamicUser('statamic-123', 'operator@example.com'));

        $this->assertNull($resolved);
    }

    public function test_runtime_resolution_fails_closed_for_duplicate_links(): void
    {
        $this->createRelationalUser('one@example.com', 'statamic-123');
        $this->createRelationalUser('two@example.com', 'statamic-123');

        $resolved = $this->bridge->resolve($this->statamicUser('statamic-123', 'one@example.com'));

        $this->assertNull($resolved);
    }

    public function test_explicit_provisioning_creates_and_links_a_missing_relational_user(): void
    {
        $operator = $this->statamicUser('statamic-123', 'Operator@Example.com');

        $user = $this->bridge->provision($operator, 'Operator Name');

        $this->assertSame('statamic-123', $user->statamic_user_id);
        $this->assertSame('operator@example.com', $user->email);
        $this->assertSame('Operator Name', $user->name);
        $this->assertNotSame('', $user->password);
        $this->assertSame($user->getKey(), $this->bridge->resolve($operator)?->getKey());
    }

    public function test_explicit_provisioning_links_an_existing_email_match(): void
    {
        $existing = $this->createRelationalUser('operator@example.com');

        $user = $this->bridge->provision(
            $this->statamicUser('statamic-123', 'operator@example.com'),
            'Updated Operator',
        );

        $this->assertSame($existing->getKey(), $user->getKey());
        $this->assertSame('statamic-123', $user->statamic_user_id);
        $this->assertSame('Updated Operator', $user->name);
        $this->assertSame(1, User::query()->count());
    }

    public function test_provisioning_rejects_an_email_linked_to_another_statamic_user(): void
    {
        $existing = $this->createRelationalUser('operator@example.com', 'other-statamic-user');

        try {
            $this->bridge->provision($this->statamicUser('statamic-123', 'operator@example.com'));
            $this->fail('Conflicting identity links must fail.');
        } catch (LogicException) {
            $existing->refresh();

            $this->assertSame('other-statamic-user', $existing->statamic_user_id);
            $this->assertSame(1, User::query()->count());
        }
    }

    private function createRelationalUser(string $email, ?string $statamicUserId = null): User
    {
        return User::query()->create([
            'statamic_user_id' => $statamicUserId,
            'name' => $email,
            'email' => $email,
            'password' => 'test-password',
        ]);
    }

    /**
     * @return StatamicUser&Stub
     */
    private function statamicUser(string $id, string $email): StatamicUser
    {
        $user = $this->createStub(StatamicUser::class);
        $user->method('getAuthIdentifier')->willReturn($id);
        $user->method('email')->willReturn($email);

        return $user;
    }
}
