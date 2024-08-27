<?php
namespace App\Security;

use App\Enum\Role;
use Symfony\Component\ExpressionLanguage\Expression;

class MultiRolesExpression extends Expression
{
    /**
     * @param Role ...$roles
     */
    public function __construct(...$roles)
    {
        parent::__construct($this->generateRolesExpression(...$roles));
    }

    /**
     * @param Role ...$roles
     */
    private function generateRolesExpression(...$roles): string
    {
        $roles = array_map(static fn ($role) => $role->value, $roles);

        return implode(' or ', array_map(static fn ($role) => "is_granted(\"$role\")", $roles));
    }
}
