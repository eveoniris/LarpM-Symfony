<?php


namespace App\Form\Groupe;

use Doctrine\ORM\EntityRepository;
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
        $builder->add('scenariste', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Scénariste',
            'required' => false,
            'class' => \App\Entity\User::class,
            'choice_label' => 'etatCivil',
            'query_builder' => static function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('u');
                $qb->join('u.etatCivil', 'ec');
                $qb->where($qb->expr()->orX(
                    $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                    $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%'))));
                $qb->orderBy('ec.nom', 'ASC');

                return $qb;
            },
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Groupe::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeScenariste';
    }
}
