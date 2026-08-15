<?php

namespace App\Support\Platform\Authorization;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Statamic\Contracts\Auth\User as StatamicUser;

class StatamicUserIdentityBridge implements StatamicUserIdentityBridgeInterface
{
    public function resolve(?Authenticatable $operator): ?User
    {
        if (! $operator instanceof StatamicUser) {
            return null;
        }

        try {
            $statamicUserId = $this->statamicUserId($operator);
        } catch (InvalidArgumentException) {
            return null;
        }

        $matches = User::query()
            ->where('statamic_user_id', $statamicUserId)
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function provision(StatamicUser $operator, ?string $name = null): User
    {
        $statamicUserId = $this->statamicUserId($operator);
        $email = $this->statamicEmail($operator);
        $name = $this->displayName($name, $email);

        return User::query()->getConnection()->transaction(function () use ($email, $name, $statamicUserId) {
            $linkedUsers = User::query()
                ->where('statamic_user_id', $statamicUserId)
                ->lockForUpdate()
                ->limit(2)
                ->get();

            if ($linkedUsers->count() > 1) {
                throw new LogicException('The Statamic user identity is linked to multiple relational users.');
            }

            if ($linkedUser = $linkedUsers->first()) {
                $this->assertEmailAvailable($email, $linkedUser);
                $this->synchroniseProfile($linkedUser, $email, $name);

                return $linkedUser;
            }

            $emailUsers = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->limit(2)
                ->get();

            if ($emailUsers->count() > 1) {
                throw new LogicException('The Statamic user email matches multiple relational users.');
            }

            if ($emailUser = $emailUsers->first()) {
                if ($emailUser->statamic_user_id !== null && $emailUser->statamic_user_id !== $statamicUserId) {
                    throw new LogicException('The relational user is already linked to another Statamic user.');
                }

                $emailUser->forceFill([
                    'statamic_user_id' => $statamicUserId,
                    'email' => $email,
                    'name' => $name,
                ])->save();

                return $emailUser;
            }

            return User::query()->create([
                'statamic_user_id' => $statamicUserId,
                'email' => $email,
                'name' => $name,
                'password' => Str::random(64),
            ]);
        });
    }

    private function assertEmailAvailable(string $email, User $linkedUser): void
    {
        $emailBelongsToAnotherUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereKeyNot($linkedUser->getKey())
            ->exists();

        if ($emailBelongsToAnotherUser) {
            throw new LogicException('The Statamic user email belongs to another relational user.');
        }
    }

    private function synchroniseProfile(User $user, string $email, string $name): void
    {
        $user->forceFill([
            'email' => $email,
            'name' => $name,
        ])->save();
    }

    private function statamicUserId(StatamicUser $operator): string
    {
        $identifier = trim((string) $operator->getAuthIdentifier());

        if ($identifier === '' || strlen($identifier) > 255) {
            throw new InvalidArgumentException('A stable Statamic user identifier is required.');
        }

        return $identifier;
    }

    private function statamicEmail(StatamicUser $operator): string
    {
        $email = Str::lower(trim((string) $operator->email()));

        if ($email === '' || strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('A valid Statamic user email is required for provisioning.');
        }

        return $email;
    }

    private function displayName(?string $name, string $email): string
    {
        $name = trim((string) $name);

        return $name !== '' ? $name : $email;
    }
}
