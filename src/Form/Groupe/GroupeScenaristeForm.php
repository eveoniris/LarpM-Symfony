<?php


namespace App\Form\Groupe;

use App\Entity\Groupe;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Groupe\GroupeScenaristeForm.
 *
 * @author kevin
 */
class GroupeScenaristeForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('scenariste', EntityType::class, [
            'label' => 'Scénariste',
            'required' => false,
            'class' => User::class,
            'autocomplete' => true,
            'choice_label' => static fn(User $user) => sprintf('%s - %s', $user->getName(), $user->getEtatCivil()?->getFullName() ?? ''),
            'query_builder' => static function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('u');
                $qb->where(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%')),
                        $qb->expr()->like('u.roles', $qb->expr()->literal('%ROLE_ADMIN%')),
                        $qb->expr()->like('u.roles', $qb->expr()->literal('%ROLE_SCENARISTE%'))
                    )
                );

                return $qb;
            },
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeScenariste';
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\' . Groupe::class,
        ]);
    }
}
