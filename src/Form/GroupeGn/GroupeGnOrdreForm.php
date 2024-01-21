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
            ->add('initiative', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => false,
            ])
            ->add('bateaux', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => false,
            ])
            ->add('agents', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => false,
            ])
            ->add('sieges', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => false,
                'label' => 'Armes de Siège',
            ])
            /*
            ->add('suzerain', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'class' => \App\Entity\Participant::class,
                'choice_label' => 'personnage.nom',
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
            ])
            */
            ;
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
