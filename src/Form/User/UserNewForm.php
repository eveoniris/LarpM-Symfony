<?php

namespace App\Form\User;

use App\Entity\Billet;
use App\Entity\Gn;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserNewForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', TextType::class, [
            'label' => 'Adresse mail',
            'required' => true,
        ])
            ->add('username', TextType::class, [
                'label' => "Nom d'utilisateur",
                'required' => true,
            ])
            ->add('gn', EntityType::class, [
                'label' => 'Jeu auquel le nouvel utilisateur participe',
                'class' => Gn::class,
                'choice_label' => 'label',
                'query_builder' => static fn ($er) => $er->createQueryBuilder('gn')->orderBy('gn.id', 'DESC'),
            ])
            ->add('billet', EntityType::class, [
                'label' => 'Choisissez le billet à donner à cet utilisateur',
                'class' => Billet::class,
                'choice_label' => 'fullLabel',
                'query_builder' => static fn ($er) => $er->createQueryBuilder('b')->orderBy('b.gn', 'DESC'),
            ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'UserNew';
    }
}
