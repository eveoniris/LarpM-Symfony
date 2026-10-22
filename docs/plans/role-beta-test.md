# Rôle ROLE_BETA_TEST (bêta-testeurs)

Rôle prévu pour donner accès à des développements en cours à un petit panel de comptes, plus les super admins. Mis en place le 2026-10-22, aucune fonctionnalité n'y est encore rattachée.

## Règles

- **Attribution** : seul un super admin (`ROLE_SUPER_ADMIN`) peut attribuer ou retirer le rôle, depuis `/user/{id}/edit` (case « ROLE_BETA_TEST »). Un administrateur simple ne voit pas la case ; si le compte possède déjà le rôle, il l'aperçoit en lecture seule et il est conservé à l'enregistrement.
- **Serveur** : même en forçant la requête, un éditeur qui n'est pas super admin ne peut ni ajouter ni retirer un rôle réservé (`Role::reservedToSuperAdmin()` : `ROLE_SUPER_ADMIN` et `ROLE_BETA_TEST`). La saisie est nettoyée dans `UserController::sanitizeAssignedRoles()`.
- **Hiérarchie** (`config/packages/security.yaml`) : `ROLE_SUPER_ADMIN` (donc `ROLE_DEV`) hérite de `ROLE_BETA_TEST` et accède aux développements en cours. `ROLE_ADMIN`, `ROLE_ORGA` et les autres rôles ne l'ont pas.
- **Effet de bord corrigé** : avant, un administrateur simple qui enregistrait le formulaire d'un super admin retirait `ROLE_SUPER_ADMIN` (la case n'était pas proposée). Les rôles réservés sont maintenant conservés.

## Utilisation dans une fonctionnalité en cours

Contrôleur :

```php
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
```

Gabarit Twig (menu, bouton, bloc) :

```twig
{% if is_granted('ROLE_BETA_TEST') %}
    ...
{% endif %}
```

Dans les tests : `UserFactory::createOne(['roles' => ['ROLE_BETA_TEST']])`. Quand la fonctionnalité est prête, remplacer la condition par le rôle définitif et retirer le contrôle.

## Tests

`tests/Functional/User/RoleBetaTestTest.php` : hiérarchie, attribution refusée à un administrateur simple, conservation du rôle déjà attribué, attribution et retrait par un super admin, case proposée au seul super admin.
