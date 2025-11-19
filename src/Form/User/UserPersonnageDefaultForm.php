<?php

namespace App\Form\User;

use App\Entity\Personnage;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPersonnageDefaultForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', EntityType::class, [
            'required' => false,
            'label' => 'Choisissez votre personnage actif sur votre session.',
            'multiple' => false,
            'expanded' => true,
            'class' => Personnage::class,
            'choice_label' => 'identity',
            'placeholder' => 'Aucun',
            'empty_data' => null,
            'query_builder' => static fn (EntityRepository $er) => $er->createQueryBuilder('p')
                    ->join('p.user', 'u')
                    ->where('u.id = :userId')
                    ->setParameter('userId', $options['user_id']),
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user_id' => null,
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
