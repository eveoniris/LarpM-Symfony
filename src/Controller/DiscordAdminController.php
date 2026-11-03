<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\DiscordGuild;
use App\Entity\SecondaryGroup;
use App\Enum\DiscordRoleType;
use App\Repository\DiscordGuildRepository;
use App\Service\Discord\DiscordApiException;
use App\Service\Discord\DiscordApiInterface;
use App\Service\Discord\DiscordMappingService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Configuration du bot Discord : guildes et correspondance des rôles.
 */
#[IsGranted('ROLE_ADMIN')]
final class DiscordAdminController extends AbstractController
{
    #[Route('/admin/discord', name: 'discord.admin', methods: ['GET', 'POST'])]
    public function listAction(Request $request, DiscordGuildRepository $guilds, DiscordApiInterface $discordApi): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('discord_guilde_add', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $guildId = trim($request->request->getString('guild_id'));
            $nom = trim($request->request->getString('nom'));

            if (!DiscordMappingService::isSnowflake($guildId)) {
                $errors[] = 'L\'identifiant de la guilde doit être un nombre de 15 à 25 chiffres.';
            } elseif (null !== $guilds->findOneBy(['guildId' => $guildId])) {
                $errors[] = 'Cette guilde est déjà configurée.';
            }
            if ('' === $nom || mb_strlen($nom) > 100) {
                $errors[] = 'Le nom est obligatoire (100 caractères maximum).';
            }

            if ([] === $errors) {
                $guild = (new DiscordGuild())
                    ->setGuildId($guildId)
                    ->setNom($nom);
                $this->entityManager->persist($guild);
                $this->entityManager->flush();
                $this->addFlash('success', 'La guilde a été ajoutée.');

                return $this->redirectToRoute('discord.admin.guilde', ['guild' => $guild->getId()]);
            }
        }

        $configured = $guilds->findAllOrdered();
        $knownIds = array_map(static fn (DiscordGuild $guild): string => $guild->getGuildId(), $configured);

        // Serveurs où le bot est invité ; en cas d'échec, l'ajout manuel reste possible.
        $botGuilds = [];
        $botGuildsError = null;
        try {
            $botGuilds = array_values(array_filter($discordApi->botGuilds(), static fn (array $guild): bool => !\in_array($guild['id'], $knownIds, true)));
        } catch (DiscordApiException $exception) {
            $botGuildsError = $exception->getMessage();
        }

        return $this->render('discord/admin/list.twig', [
            'botGuilds' => $botGuilds,
            'botGuildsError' => $botGuildsError,
            'guilds' => $configured,
            'errors' => $errors,
            'old' => $request->request->all(),
        ]);
    }

    #[Route('/admin/discord/guilde/{guild}', name: 'discord.admin.guilde', requirements: ['guild' => '\d+'], methods: ['GET', 'POST'])]
    public function guildeAction(
        Request $request,
        #[MapEntity(id: 'guild')]
        DiscordGuild $guild,
        DiscordMappingService $mappingService,
        DiscordApiInterface $discordApi,
    ): Response {
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('discord_guilde_' . $guild->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $nom = trim($request->request->getString('nom'));
            if ('' === $nom || mb_strlen($nom) > 100) {
                $errors[] = 'Le nom est obligatoire (100 caractères maximum).';
            }

            /** @var array<int|string, string> $groupes */
            $groupes = array_map(strval(...), $request->request->all('groupes'));
            if ([] === $errors) {
                $errors = $mappingService->apply($guild, $request->request->getString('membre'), $request->request->getString('orga'), $groupes);
            }

            if ([] === $errors) {
                $guild->setNom($nom);
                $this->entityManager->flush();
                $this->addFlash('success', 'La configuration de la guilde a été enregistrée.');

                return $this->redirectToRoute('discord.admin.guilde', ['guild' => $guild->getId()]);
            }
        }

        // Liste des rôles lue chez Discord ; en cas d'échec, la saisie manuelle des identifiants reste possible.
        $discordRoles = [];
        $rolesError = null;
        try {
            $discordRoles = $discordApi->guildRoles($guild->getGuildId());
        } catch (DiscordApiException $exception) {
            $rolesError = $exception->getMessage();
        }

        return $this->render('discord/admin/guilde.twig', [
            'discordRoles' => $discordRoles,
            'rolesError' => $rolesError,
            'guild' => $guild,
            'groups' => $this->entityManager->getRepository(SecondaryGroup::class)->findBy([], ['label' => 'ASC']),
            'byGroup' => $guild->groupMappings(),
            'membre' => $guild->roleIdOf(DiscordRoleType::MEMBRE),
            'orga' => $guild->roleIdOf(DiscordRoleType::ORGA),
            'errors' => $errors,
            'old' => $request->request->all(),
        ]);
    }

    #[Route('/admin/discord/guilde/{guild}/supprimer', name: 'discord.admin.guilde.delete', requirements: ['guild' => '\d+'], methods: ['POST'])]
    public function deleteAction(Request $request, #[MapEntity(id: 'guild')] DiscordGuild $guild): Response
    {
        if (!$this->isCsrfTokenValid('discord_guilde_delete_' . $guild->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $this->entityManager->remove($guild);
        $this->entityManager->flush();
        $this->addFlash('success', 'La guilde a été retirée de la configuration (rien n\'a été supprimé sur Discord).');

        return $this->redirectToRoute('discord.admin');
    }
}
