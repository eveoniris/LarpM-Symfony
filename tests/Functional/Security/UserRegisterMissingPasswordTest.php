<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Régression : une soumission POST vers /user/register sans la clé `pwd` du tout (pas
 * juste une valeur vide) faisait planter avec un TypeError fatal (`BaseUser::setPwd()`
 * n'acceptait pas `null`), avant même que la validation du formulaire ne s'exécute.
 */
#[Group('functional')]
class UserRegisterMissingPasswordTest extends WebTestCase
{
    public function testRegisterWithoutPwdFieldDoesNotCrash(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/user/register');
        static::assertResponseIsSuccessful();

        $form = $crawler->selectButton("S'enregistrer")->form();
        $form['user_register[email]'] = 'sans-mdp@example.com';
        $form['user_register[username]'] = 'sans-mdp';
        $form['user_register[confirm_password]'] = 'peu-importe';
        $form->remove('user_register[pwd]');

        $client->submit($form);

        // Le formulaire doit être réaffiché avec une erreur de validation propre (422),
        // pas planter avec un TypeError fatal (500).
        static::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Cette valeur ne doit pas être vide', (string) $client->getResponse()->getContent());
    }
}
