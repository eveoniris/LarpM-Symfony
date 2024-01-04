<?php


namespace App\Form\GroupeGn;

use LarpManager\Repository\ParticipantRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\GroupeGn\GroupeGnOrdreForm.
 *
 * @author Kevin F.
 */
class GroupeGnOrdreForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('initiative', 'integer', [
                'required' => false,
            ])
            ->add('bateaux', 'integer', [
                'required' => false,
            ])
            ->add('agents', 'integer', [
                'required' => false,
            ])
            ->add('sieges', 'integer', [
                'required' => false,
                'label' => 'Armes de Siège',
            ])
            ->add('suzerain', 'entity', [
                'required' => false,
                'class' => \App\Entity\Participant::class,
                'property' => 'personnage.nom',
                'query_builder' => static function (ParticipantRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.personnage', 'u');
                    $qb->join('p.groupeGn', 'gg');
                    $qb->orderBy('u.nom', 'ASC');
                    $qb->where('gg.id = :groupeGnId');
                    $qb->setParameter('groupeGnId', $options['groupeGnId']);

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Responsable',
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\GroupeGn::class,
            'groupeGnId' => false,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeGnOrdre';
    }
}
