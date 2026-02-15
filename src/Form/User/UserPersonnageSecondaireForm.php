<?php

namespace App\Form\User;

use App\Entity\Personnage;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPersonnageSecondaireForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage_secondaire', EntityType::class, [
            'required' => false,
            'label' => 'Choisissez votre personnage secondaire.',
            'multiple' => false,
            'expanded' => true,
            'class' => Personnage::class,
            'choice_label' => 'identity',
            'placeholder' => 'Aucun',
            'empty_data' => null,
            'query_builder' => static fn (EntityRepository $er) => $er->createQueryBuilder('p')
                    ->join('p.user', 'u')
                    ->where('u.id = :userId AND p.id not in (:principalIds)')
                    ->setParameter('userId', $options['user_id'])
                    ->setParameter('principalIds', implode(',', $options['principal_ids'] ?? [0])),
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'user_id' => null,
            'principal_ids' => null,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'userPersonnageDefault';
    }
}
