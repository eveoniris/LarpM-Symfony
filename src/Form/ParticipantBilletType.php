<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ParticipantBilletForm.
 *
 * @author kevin
 */
class ParticipantBilletType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('billet', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Choisissez le billet a donner à cet utilisateur',
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'class' => \App\Entity\Billet::class,
            'choice_label' => 'fullLabel',
            'query_builder' => static function ($er) use ($options) {
                $qb = $er->createQueryBuilder('b');
                $qb->where('b.gn = :gnId');
                $qb->setParameter('gnId', $options['gnId']);
                $qb->orderBy('b.label', 'ASC');

                return $qb;
            },
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Participant::class,
            'gnId' => null,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}
