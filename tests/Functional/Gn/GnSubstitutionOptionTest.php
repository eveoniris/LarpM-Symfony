<?php

declare(strict_types=1);

namespace App\Tests\Functional\Gn;

use App\Entity\Gn;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Option d'opus « personnage de substitution » : un second personnage joué dans les
 * instances situées hors du temps et du lieu de l'événement.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class GnSubstitutionOptionTest extends WebTestCase
{
    public function testLOptionEstDesactiveeParDefaut(): void
    {
        $gn = new Gn();

        static::assertFalse($gn->isSubstitutionActive());
        static::assertNull($gn->getSubstitutionDescription());
    }

    public function testLeFormulaireExposeLOptionEtSaDescription(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/update');

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('[name="gn[substitutionActive]"]'));
        static::assertCount(1, $crawler->filter('[name="gn[substitutionDescription]"]'));
    }

    public function testLOptionEstPersisteeParLeFormulaire(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne(['substitutionActive' => false]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/update');
        $form = $crawler->selectButton('Sauvegarder')->form();

        // Gn::$description porte un NotBlank : il faut le renseigner pour que le formulaire valide.
        $form['gn[description]'] = 'Opus de test.';
        $form['gn[substitutionActive]'] = '1';
        $form['gn[substitutionDescription]'] = 'Les nobles restés au pays font avancer leurs intrigues.';

        $client->submit($form);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $rafraichi = $em->getRepository(Gn::class)->find($gn->getId());

        static::assertTrue($rafraichi->isSubstitutionActive());
        static::assertSame('Les nobles restés au pays font avancer leurs intrigues.', $rafraichi->getSubstitutionDescription());
    }
}
