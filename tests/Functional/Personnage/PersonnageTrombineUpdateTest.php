<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Régression : uploader une photo dont le nom original slugifié dépasse la longueur de
 * trombineUrl faisait planter (SQL 1406) sur un Personnage EXISTANT récupéré depuis la DB
 * (résolveur d'entité de la route), car Doctrine n'appelle jamais le constructeur PHP lors
 * de l'hydratation : `initFile()` (qui fixe la limite de troncature) ne tournait donc jamais
 * dans ce flux, contrairement à un `new Personnage()`.
 */
#[Group('functional')]
class PersonnageTrombineUpdateTest extends WebTestCase
{
    public function testUploadWithVeryLongFilenameDoesNotOverflowColumn(): void
    {
        $client = static::createClient();

        EspeceFactory::createOne(['nom' => 'Humain']);
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/personnage/' . $personnage->getId() . '/trombine/update');
        static::assertResponseIsSuccessful();

        // Le nom du fichier temporaire réel doit rester court (limite du filesystem), mais le nom
        // "client original" simulé est volontairement bien plus long que trombineUrl (255), pour
        // reproduire le dépassement si la troncature n'est pas correctement appliquée.
        $tmpPath = tempnam(sys_get_temp_dir(), 'trombine') . '.png';
        $this->createValidPng($tmpPath);
        $longOriginalName = str_repeat('un-nom-de-fichier-tres-tres-long-descriptif-', 8) . '.png';

        $form = $crawler->selectButton('Envoyer')->form();
        $client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            [
                'trombine' => [
                    'file' => [
                        'name' => $longOriginalName,
                        'type' => 'image/png',
                        'tmp_name' => $tmpPath,
                        'error' => \UPLOAD_ERR_OK,
                        'size' => filesize($tmpPath),
                    ],
                ],
            ],
        );

        @unlink($tmpPath);

        static::assertResponseRedirects();

        // find() après clear() pour lire l'état réellement persisté en DB, pas l'objet
        // en cache dans l'identity map de l'EntityManager de test.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $personnage = $em->getRepository($personnage::class)->find($personnage->getId());
        $stored = $personnage->getTrombineUrl();

        self::assertNotNull($stored);
        self::assertLessThanOrEqual(255, mb_strlen($stored));
        // le nom stocké doit contenir un suffixe unique (uid), pas juste le slug tronqué
        self::assertMatchesRegularExpression('/-[0-9a-f]{10,}-[0-9]{5,}\.png$/', $stored);
    }

    private function createValidPng(string $path): void
    {
        $image = imagecreatetruecolor(200, 200);
        imagepng($image, $path);
    }
}
