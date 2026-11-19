<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\MessengerSurveillance;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Alerte les super admins par e-mail (envoi direct, sans passer par la file) quand des messages sont en échec
 * ou que la file d'envoi s'accumule. Au plus un mail par jour, sauf si le nombre d'échecs augmente.
 * Appelée en fin de bin/cron-messenger.php.
 */
#[AsCommand(name: 'app:messenger:alerte', description: "Alerte les super admins si la file Messenger a des échecs ou s'accumule")]
final class MessengerAlerte extends Command
{
    private const int SEUIL_FILE = 500;

    public function __construct(
        private readonly MessengerSurveillance $surveillance,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        #[Autowire('%kernel.project_dir%/var/messenger_alerte.json')]
        private readonly string $etatPath,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $echecs = $this->surveillance->nombreEchecs();
        $attente = $this->surveillance->enAttente() ?? 0;

        if (0 === $echecs && $attente < self::SEUIL_FILE) {
            $this->ecrireEtat(null);
            $io->success('File saine.');

            return Command::SUCCESS;
        }

        $aujourdhui = (new DateTimeImmutable())->format('Y-m-d');
        $etat = $this->lireEtat();
        if (null !== $etat && $etat['date'] === $aujourdhui && $etat['echecs'] >= $echecs) {
            $io->note('Alerte déjà envoyée aujourd\'hui.');

            return Command::SUCCESS;
        }

        $destinataires = array_filter(array_map(static fn ($u): ?string => $u->getEmail(), $this->userRepository->findSuperAdmins()));
        if ([] === $destinataires) {
            $io->warning('Aucun super admin avec adresse e-mail.');

            return Command::FAILURE;
        }

        $this->mailer->send(
            (new Email())
                ->to(...$destinataires)
                ->subject(\sprintf('Alerte file d\'envoi : %d échec(s), %d en attente', $echecs, $attente))
                ->text(\sprintf("File d'envoi des e-mails (Messenger)\n\nÉchecs : %d\nEn attente : %d\n\nDétail et rejeu : /admin/messenger\n", $echecs, $attente)),
        );
        $this->ecrireEtat(['date' => $aujourdhui, 'echecs' => $echecs]);
        $io->success('Alerte envoyée.');

        return Command::SUCCESS;
    }

    /** @return array{date: string, echecs: int}|null */
    private function lireEtat(): ?array
    {
        if (!is_file($this->etatPath)) {
            return null;
        }
        $etat = json_decode((string) file_get_contents($this->etatPath), associative: true);

        $valide = \is_array($etat) && \is_string($etat['date'] ?? null) && \is_int($etat['echecs'] ?? null);

        return $valide ? $etat : null;
    }

    /** @param array{date: string, echecs: int}|null $etat null = remise à zéro */
    private function ecrireEtat(?array $etat): void
    {
        if (null === $etat) {
            if (is_file($this->etatPath)) {
                unlink($this->etatPath);
            }

            return;
        }
        file_put_contents($this->etatPath, json_encode($etat));
    }
}
