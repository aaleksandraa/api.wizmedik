<?php

namespace App\Services;

use App\Models\ApotekaFirma;
use App\Models\Banja;
use App\Models\Doktor;
use App\Models\Dom;
use App\Models\Klinika;
use App\Models\Laboratorija;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Notifications\AdminAccessInvitationNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminProfileAccessService
{
    /**
     * @return string[]
     */
    private function managedRoles(): array
    {
        return ['doctor', 'clinic', 'laboratory', 'spa_manager', 'dom_manager', 'pharmacy_owner'];
    }

    /**
     * Sync or create an access account for an admin-managed profile.
     *
     * @param  array{
     *     relation_column?: string,
     *     role: string,
     *     model_class: class-string<Model>,
     *     entity_label?: string,
     *     invitation_label?: string,
     *     name?: callable|mixed,
     *     ime?: callable|mixed,
     *     prezime?: callable|mixed
     * }  $config
     * @return array{user: User|null, action: string}
     */
    public function sync(Model $profile, array $payload, array $config): array
    {
        $relationColumn = $config['relation_column'] ?? 'user_id';
        $role = $config['role'];
        $modelClass = $config['model_class'];
        $entityLabel = $config['entity_label'] ?? 'profil';

        $currentUser = $profile->getAttribute($relationColumn)
            ? User::find($profile->getAttribute($relationColumn))
            : null;

        $accountEmailProvided = array_key_exists('account_email', $payload);
        $targetEmail = $this->normalizeEmail($payload['account_email'] ?? null);
        $password = trim((string) ($payload['password'] ?? $payload['account_password'] ?? ''));

        if (!$accountEmailProvided && $password === '') {
            return [
                'user' => $currentUser,
                'action' => 'unchanged',
            ];
        }

        if (!$accountEmailProvided && $password !== '') {
            if (!$currentUser) {
                throw ValidationException::withMessages([
                    'password' => ['Lozinka se moze postaviti tek nakon sto unesete pristupni email.'],
                ]);
            }

            $this->hydrateUser($currentUser, $profile, $config, $role);
            $currentUser->password = Hash::make($password);
            $currentUser->save();
            $this->ensureRole($currentUser, $role);

            return [
                'user' => $currentUser->fresh(),
                'action' => 'password_updated',
            ];
        }

        if ($targetEmail === null) {
            return [
                'user' => $currentUser,
                'action' => 'unchanged',
            ];
        }

        $this->assertEmailNotReservedByPendingRegistration($targetEmail, $currentUser);

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$targetEmail])
            ->first();

        if ($existingUser) {
            $this->assertUserCanBeAttached(
                $existingUser,
                $currentUser,
                $profile,
                $config,
                $relationColumn,
                $entityLabel,
                $role
            );

            if ($currentUser && $existingUser->id === $currentUser->id) {
                $this->hydrateUser($currentUser, $profile, $config, $role);
                if ($password !== '') {
                    $currentUser->password = Hash::make($password);
                }
                $currentUser->save();
                $this->syncManagedRole($currentUser, $role);

                return [
                    'user' => $currentUser->fresh(),
                    'action' => 'updated',
                ];
            }

            $this->hydrateUser($existingUser, $profile, $config, $role);
            if ($password !== '') {
                $existingUser->password = Hash::make($password);
            }
            $existingUser->save();
            $this->syncManagedRole($existingUser, $role);

            $profile->forceFill([$relationColumn => $existingUser->id])->save();
            $this->releaseDetachedUser($currentUser, $existingUser, $role);

            return [
                'user' => $existingUser->fresh(),
                'action' => 'attached_existing',
            ];
        }

        if ($currentUser) {
            $newUser = new User([
                'email' => $targetEmail,
                'password' => Hash::make($password !== '' ? $password : $this->generateProvisioningPassword()),
            ]);

            $this->hydrateUser($newUser, $profile, $config, $role);
            if ($password !== '') {
                $newUser->password = Hash::make($password);
            }
            $newUser->save();
            $this->syncManagedRole($newUser, $role);
            $profile->forceFill([$relationColumn => $newUser->id])->save();
            $this->releaseDetachedUser($currentUser, $newUser, $role);

            return [
                'user' => $newUser->fresh(),
                'action' => 'created',
            ];
        }

        $newUser = new User([
            'email' => $targetEmail,
            'password' => Hash::make($password !== '' ? $password : $this->generateProvisioningPassword()),
        ]);

        $this->hydrateUser($newUser, $profile, $config, $role);
        $newUser->save();
        $this->syncManagedRole($newUser, $role);

        $profile->forceFill([$relationColumn => $newUser->id])->save();

        return [
            'user' => $newUser->fresh(),
            'action' => 'created',
        ];
    }

    /**
     * Provision an access account if needed and send a secure invitation email.
     *
     * @param  array{
     *     relation_column?: string,
     *     role: string,
     *     model_class: class-string<Model>,
     *     entity_label?: string,
     *     invitation_label?: string,
     *     name?: callable|mixed,
     *     ime?: callable|mixed,
     *     prezime?: callable|mixed
     * }  $config
     * @return array{user: User, action: string, sent_to: string, invitation_sent_at: string}
     */
    public function sendInvitation(Model $profile, array $payload, array $config): array
    {
        $syncResult = $this->sync($profile, $payload, $config);
        $relationColumn = $config['relation_column'] ?? 'user_id';
        $invitationLabel = $config['invitation_label'] ?? $config['entity_label'] ?? 'profil';

        $user = $syncResult['user']
            ?? ($profile->getAttribute($relationColumn)
                ? User::find($profile->getAttribute($relationColumn))
                : null);

        if (!$user || !$user->email) {
            throw ValidationException::withMessages([
                'account_email' => ['Unesite pristupni email prije slanja pozivnice.'],
            ]);
        }

        $token = Password::broker()->createToken($user);
        $profileName = $this->resolveValue(
            $config['name'] ?? fn (Model $entity) => trim((string) ($entity->naziv ?? trim(($entity->ime ?? '') . ' ' . ($entity->prezime ?? '')))),
            $profile
        );

        $user->notify(new AdminAccessInvitationNotification(
            $token,
            (string) $invitationLabel,
            is_string($profileName) ? $profileName : null
        ));

        Log::info('Admin access invitation sent', [
            'profile_type' => $invitationLabel,
            'profile_id' => $profile->getKey(),
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'user' => $user->fresh(),
            'action' => $syncResult['action'],
            'sent_to' => $user->email,
            'invitation_sent_at' => now()->toIso8601String(),
        ];
    }

    private function hydrateUser(User $user, Model $profile, array $config, string $role): void
    {
        $name = $this->resolveValue(
            $config['name'] ?? fn (Model $entity) => trim((string) ($entity->naziv ?? trim(($entity->ime ?? '') . ' ' . ($entity->prezime ?? '')))),
            $profile
        );
        $ime = $this->resolveValue($config['ime'] ?? null, $profile);
        $prezime = $this->resolveValue($config['prezime'] ?? null, $profile);

        if ($name !== null && $name !== '') {
            $user->name = $name;
        }

        if ($ime !== null && $ime !== '') {
            $user->ime = $ime;
        }

        if ($prezime !== null && $prezime !== '') {
            $user->prezime = $prezime;
        }

        $user->role = $role;
        $user->email_verified_at = $user->email_verified_at ?? now();
    }

    private function syncManagedRole(User $user, string $role): void
    {
        $user->syncRoles([$role]);
    }

    private function assertUserCanBeAttached(
        User $candidate,
        ?User $currentUser,
        Model $profile,
        array $config,
        string $relationColumn,
        string $entityLabel,
        string $role
    ): void {
        if ($currentUser && $candidate->id === $currentUser->id) {
            return;
        }

        $legacyRole = $this->normalizeLegacyRole((string) ($candidate->role ?? ''));

        if ($candidate->hasAnyRole(['admin', 'patient']) || in_array($legacyRole, ['admin', 'patient'], true)) {
            throw ValidationException::withMessages([
                'account_email' => ["Uneseni email vec koristi postojeci korisnicki nalog. Za {$entityLabel} pristup koristite zaseban poslovni email."],
            ]);
        }

        $candidateManagedRoles = $this->managedRolesForUser($candidate);
        $conflictingManagedRoles = array_values(array_diff($candidateManagedRoles, [$role]));
        if ($conflictingManagedRoles !== []) {
            throw ValidationException::withMessages([
                'account_email' => ["Uneseni email je vec poslovni nalog drugog tipa profila i ne moze se preuzeti za {$entityLabel} pristup."],
            ]);
        }

        $existingLink = $this->findExistingManagedProfileLink($candidate, $profile, $config, $relationColumn);
        if ($existingLink !== null) {
            throw ValidationException::withMessages([
                'account_email' => ["Uneseni email je vec povezan sa drugim {$existingLink} profilom."],
            ]);
        }
    }

    private function releaseDetachedUser(?User $detachedUser, User $newOwner, string $oldRole): void
    {
        if (!$detachedUser || $detachedUser->is($newOwner)) {
            return;
        }

        $detachedUser->tokens()->delete();
        $detachedUser->setRememberToken(Str::random(60));

        $remainingRoles = array_values(array_diff($detachedUser->getRoleNames()->all(), $this->managedRoles()));
        if ($remainingRoles === []) {
            $remainingRoles = ['patient'];
        }

        $detachedUser->syncRoles($remainingRoles);

        if ($this->normalizeLegacyRole((string) ($detachedUser->role ?? '')) === $oldRole || $detachedUser->role === null) {
            $detachedUser->role = $remainingRoles[0];
        }

        $detachedUser->save();
    }

    private function findExistingManagedProfileLink(
        User $user,
        Model $currentProfile,
        array $config,
        string $relationColumn
    ): ?string {
        $checks = [
            [Doktor::class, 'user_id', 'doktor'],
            [Klinika::class, 'user_id', 'klinika'],
            [Laboratorija::class, 'user_id', 'laboratorija'],
            [Banja::class, 'user_id', 'banja'],
            [Dom::class, 'user_id', 'dom'],
            [ApotekaFirma::class, 'owner_user_id', 'apoteka'],
        ];

        foreach ($checks as [$modelClass, $column, $label]) {
            /** @var class-string<Model> $modelClass */
            $query = $modelClass::query()->where($column, $user->id);

            if ($modelClass === $config['model_class'] && $column === $relationColumn) {
                $query->whereKeyNot($currentProfile->getKey());
            }

            if ($query->exists()) {
                return $label;
            }
        }

        return null;
    }

    private function resolveValue(mixed $value, Model $profile): mixed
    {
        if (is_callable($value)) {
            return $value($profile);
        }

        return $value;
    }

    private function assertEmailNotReservedByPendingRegistration(string $email, ?User $currentUser): void
    {
        if ($currentUser && $this->normalizeEmail($currentUser->email) === $email) {
            return;
        }

        $pendingRequest = RegistrationRequest::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 'pending')
            ->first();

        if (!$pendingRequest) {
            return;
        }

        throw ValidationException::withMessages([
            'account_email' => ['Uneseni email je vec rezervisan kroz aktivan zahtjev za registraciju. Prvo odobrite ili odbijte postojeci zahtjev.'],
        ]);
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($email));

        return $normalized === '' ? null : $normalized;
    }

    private function generateProvisioningPassword(): string
    {
        return Str::password(32);
    }

    /**
     * @return string[]
     */
    private function managedRolesForUser(User $user): array
    {
        $roleNames = collect($user->getRoleNames()->all())
            ->map(fn (string $roleName) => $this->normalizeLegacyRole($roleName));

        $legacyRole = $this->normalizeLegacyRole((string) ($user->role ?? ''));
        if ($legacyRole !== '') {
            $roleNames->push($legacyRole);
        }

        return $roleNames
            ->filter(fn (string $roleName) => in_array($roleName, $this->managedRoles(), true))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeLegacyRole(string $role): string
    {
        $normalized = trim(mb_strtolower($role));

        return match ($normalized) {
            'spa' => 'spa_manager',
            'care_home', 'care_home_manager' => 'dom_manager',
            'pharmacy' => 'pharmacy_owner',
            default => $normalized,
        };
    }
}
