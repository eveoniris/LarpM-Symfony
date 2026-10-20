<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class ThemeTest extends WebTestCase
{
    public function testModeClairMemoriseSansPerdreLaBeta(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER'], 'uiTheme' => 'beta']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $button = $crawler->filter('#btnSwitch');
        static::assertCount(1, $button);
        static::assertSame('dark', $crawler->filter('html')->attr('data-bs-theme'));

        $client->request('POST', (string) $button->attr('data-theme-url'), ['mode' => 'light', '_token' => $button->attr('data-theme-token')]);
        static::assertResponseStatusCodeSame(204);

        $fresh = static::getContainer()->get('doctrine')->getManager()->find(User::class, $user->getId());
        static::assertTrue($fresh->isUiLight());
        static::assertTrue($fresh->isUiBeta());
        static::assertSame('beta light', $fresh->getUiTheme());

        $crawler = $client->request('GET', '/');
        static::assertSame('light', $crawler->filter('html')->attr('data-bs-theme'));
    }

    public function testJetonInvalideRefuse(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('POST', '/user/theme', ['mode' => 'light', '_token' => 'faux']);

        static::assertResponseStatusCodeSame(403);
    }

    public function testBasculeBetaConserveLeModeClair(): void
    {
        $user = new User();
        $user->setUiLight(true)->setUiBeta(true);
        static::assertSame('beta light', $user->getUiTheme());

        $user->setUiTheme('rien');
        static::assertSame('light', $user->getUiTheme());
        static::assertFalse($user->isUiBeta());

        $user->setUiLight(false);
        static::assertNull($user->getUiTheme());
    }
}
