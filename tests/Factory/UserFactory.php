<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\User;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'username' => self::faker()->unique()->userName(),
            'password' => '$2y$13$hashed_test_password',
            'pwd' => 'test_password',
            'rights' => '',
            'isEnabled' => true,
            'roles' => [],
        ];
    }

    protected function initialize(): static
    {
        // User has addRole()/removeRole() that PropertyAccessor picks up over setRoles(),
        // causing factory role overrides to modify $rights instead of $roles.
        // alwaysForce('roles') bypasses PropertyAccessor for this field.
        return $this->instantiateWith(Instantiator::withConstructor()->alwaysForce('roles'));
    }
}
