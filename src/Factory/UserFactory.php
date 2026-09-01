<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            "email" => self::faker()->unique()->safeEmail(),
            "username" => self::faker()->unique()->userName(), # required for UserInterface
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            // Default dev password: "password" (change if needed).
            // setPassword() is overridden on User to write into `pwd`.
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, "password"),
            );
            $user->setEnabled(true);
        });
    }

    /**
     * Admin user factory method: creates a user with ROLE_ADMIN and password "admin".
     */
    public function admin(): static
    {
        return $this->with([
            "email" => "admin@larpm.local",
            "username" => "admin",
        ])->afterInstantiate(function (User $user): void {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, "admin"),
            );
            $user->setEnabled(true);
            $user->setRoles(["ROLE_ADMIN"]);
        });
    }
}
