<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\EventListener\LoginHistorySubscriber;
use App\Security\LoginFingerprint;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use RuntimeException;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Un échec d'écriture de l'historique ne doit jamais empêcher la connexion.
 */
#[Group('unit')]
class LoginHistorySubscriberTest extends TestCase
{
    public function testUneEcritureEnEchecEstJournaliseeSansRemonterLException(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('isOpen')->willReturn(true);
        $em->method('flush')->willThrowException(new RuntimeException('base indisponible'));
        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $errors = [];

            public function log(mixed $level, Stringable|string $message, array $context = []): void
            {
                if ('error' === $level) {
                    $this->errors[] = (string) $message;
                }
            }
        };

        $subscriber = new LoginHistorySubscriber($em, $logger, new LoginFingerprint('sel'));
        $subscriber->onLoginSuccess($this->successEvent(new User()));

        static::assertCount(1, $logger->errors);
        static::assertStringContainsString('Historique de connexion non enregistré', $logger->errors[0]);
    }

    public function testUnEntityManagerFermeNeRienEcritEtNeLeveRien(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('isOpen')->willReturn(false);
        $em->expects(static::never())->method('persist');
        $em->expects(static::never())->method('flush');

        $subscriber = new LoginHistorySubscriber($em, new NullLogger(), new LoginFingerprint('sel'));
        $subscriber->onLoginSuccess($this->successEvent(new User()));
    }

    public function testUnAuthenticatorNonSuiviNeProduitAucuneLigne(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(static::never())->method('persist');

        $user = new User();
        $passport = new SelfValidatingPassport(new UserBadge('a@example.test', static fn () => $user));
        $event = new LoginSuccessEvent($this->createStub(AuthenticatorInterface::class), $passport, new UsernamePasswordToken($user, 'api'), Request::create('/'), null, 'api');

        (new LoginHistorySubscriber($em, new NullLogger(), new LoginFingerprint('sel')))->onLoginSuccess($event);
    }

    public function testLEntreeEnregistreeContientIpEtUserAgent(): void
    {
        $persisted = [];
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('isOpen')->willReturn(true);
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        (new LoginHistorySubscriber($em, new NullLogger(), new LoginFingerprint('sel')))->onLoginSuccess($this->successEvent(new User()));

        static::assertCount(1, $persisted);
        static::assertInstanceOf(LoginHistory::class, $persisted[0]);
        static::assertSame('203.0.113.9', $persisted[0]->getIp());
        static::assertSame('Agent/1.0', $persisted[0]->getUserAgent());
        static::assertTrue($persisted[0]->isSuccess());
    }

    private function successEvent(User $user): LoginSuccessEvent
    {
        $request = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => '203.0.113.9', 'HTTP_USER_AGENT' => 'Agent/1.0']);
        $passport = new SelfValidatingPassport(new UserBadge('a@example.test', static fn () => $user));

        return new LoginSuccessEvent($this->createStub(AbstractLoginFormAuthenticator::class), $passport, new UsernamePasswordToken($user, 'main'), $request, null, 'main');
    }
}
