<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\Personnage;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Choix du personnage actif sur LarpManager.
 *
 * Cette notion est purement applicative : elle détermine au nom de quel personnage
 * le joueur signe ses messages, postule à un groupe, etc. Elle n'a rien à voir avec
 * le personnage principal d'une participation à un GN.
 *
 * @extends AbstractType<User>
 */
class UserPersonnageDefaultType extends AbstractType
{
    /**
     * Au-delà, la liste devient illisible pour un joueur ordinaire. Les scénaristes
     * et l'organisation, qui manipulent beaucoup de personnages, ne sont pas limités.
     */
    public const DEFAULT_LIMIT = 5;

    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', EntityType::class, [
            'required' => false,
            'label' => 'Choisissez le personnage actif sur votre session LarpManager.',
            'multiple' => false,
            'expanded' => true,
            'class' => Personnage::class,
            'choice_label' => 'identity',
            'placeholder' => 'Aucun',
            'empty_data' => null,
            'choices' => $this->choix($options['user'], $options['limit']),
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'user' => null,
            'limit' => self::DEFAULT_LIMIT,
        ]);

        $resolver->setAllowedTypes('user', [User::class]);
        $resolver->setAllowedTypes('limit', ['null', 'int']);
    }

    /**
     * Les personnages vivants du joueur, du plus récemment joué au plus ancien.
     *
     * Le personnage actuellement actif reste toujours proposé, même s'il sort de la
     * limite ou s'il est mort : sinon le formulaire le remplacerait silencieusement.
     *
     * @return array<int, Personnage>
     */
    private function choix(User $user, ?int $limit): array
    {
        $actif = $user->getPersonnage(true);

        $vivants = [];
        foreach ($user->getPersonnages() as $personnage) {
            if (!$personnage->getVivant()) {
                continue;
            }

            $vivants[] = $personnage;
        }

        // Le dernier participé d'abord : c'est celui que le joueur cherche.
        usort($vivants, static fn (Personnage $a, Personnage $b) => ($b->getLastParticipant()?->getId() ?? 0) <=> ($a->getLastParticipant()?->getId() ?? 0));

        if (null !== $limit) {
            $vivants = \array_slice($vivants, 0, $limit);
        }

        if ($actif instanceof Personnage && !\in_array($actif, $vivants, true)) {
            array_unshift($vivants, $actif);
        }

        return $vivants;
    }
}
