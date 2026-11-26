<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InterJeu;
use App\Entity\InterJeuOrga;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\UserRepository;
use App\Security\MultiRolesExpression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Accès des orgas externes à un inter-jeu, donnés et retirés par le rôle INTER_JEU (ou un admin).
 * Donner l'accès vaut validation. Le rôle ROLE_ORGA_INTER suit les accès : il ne sert qu'à afficher
 * le menu « Mes inter-jeux », les droits réels sont vérifiés par InterJeuVoter.
 */
#[IsGranted(new MultiRolesExpression(Role::INTER_JEU, Role::ADMIN))]
#[Route('/inter-jeu', name: 'inter.')]
final class InterOrgaController extends AbstractController
{
    #[Route('/{inter}/orgas', name: 'orgas', requirements: ['inter' => Requirement::DIGITS])]
    public function orgasAction(Request $request, InterJeu $inter, UserRepository $userRepository): RedirectResponse|Response
    {
        if (!$request->isMethod('POST')) {
            return $this->render('inter/orgas.html.twig', [
                'interJeu' => $inter,
            ]);
        }

        if (!$this->isCsrfTokenValid('inter_orgas_' . $inter->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');

            return $this->redirectToRoute('inter.orgas', ['inter' => $inter->getId()], 303);
        }

        $saisie = trim((string) $request->request->get('user'));
        $user = ctype_digit($saisie) ? $userRepository->find((int) $saisie) : $userRepository->findOneBy(['email' => $saisie]);

        if (!$user instanceof User) {
            $this->addFlash('error', 'Aucun utilisateur trouvé pour cet identifiant ou cet e-mail.');
        } elseif (null !== $inter->getOrga($user)) {
            $this->addFlash('warning', 'Cet utilisateur a déjà accès à l\'inter-jeu.');
        } else {
            $inter->addOrga($user, $this->getUser());
            $this->ajouterRoleOrga($user);
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Accès validé pour %s.', $user->getUsername()));
        }

        return $this->redirectToRoute('inter.orgas', ['inter' => $inter->getId()], 303);
    }

    #[Route('/{inter}/orga/{orga}/remove', name: 'orga.remove', requirements: ['inter' => Requirement::DIGITS, 'orga' => Requirement::DIGITS], methods: ['POST'])]
    public function orgaRemoveAction(Request $request, InterJeu $inter, InterJeuOrga $orga): RedirectResponse
    {
        if ($orga->getInterJeu() !== $inter || !$this->isCsrfTokenValid('remove_orga_' . $orga->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Demande invalide, recommencez.');

            return $this->redirectToRoute('inter.orgas', ['inter' => $inter->getId()], 303);
        }

        $user = $orga->getUser();
        $inter->removeOrga($orga);
        $this->entityManager->flush();
        $this->retirerRoleOrgaSansAcces($user);
        $this->entityManager->flush();
        $this->addFlash('success', \sprintf('Accès retiré pour %s.', $user->getUsername()));

        return $this->redirectToRoute('inter.orgas', ['inter' => $inter->getId()], 303);
    }

    private function ajouterRoleOrga(User $user): void
    {
        $roles = array_values(array_diff($user->getRoles(), ['ROLE_USER']));
        if (!\in_array(Role::ORGA_INTER->value, $roles, true)) {
            $roles[] = Role::ORGA_INTER->value;
            $user->setRoles($roles);
        }
    }

    private function retirerRoleOrgaSansAcces(User $user): void
    {
        if (0 !== $this->entityManager->getRepository(InterJeuOrga::class)->count(['user' => $user])) {
            return;
        }

        $user->setRoles(array_values(array_diff($user->getRoles(), ['ROLE_USER', Role::ORGA_INTER->value])));
    }
}
