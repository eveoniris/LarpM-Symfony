<?php

namespace App\Form;

use App\Form\Entity\ListSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', TextType::class, [
            'required' => true,
            'label' => 'Recherche',
        ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                   // TODO etatCivil.name? 'name' ,
                    '*',
                    'username',
                    'roles',
                    'email',
                    'id',
                ],
                'choice_label' => static fn ($value) => match ($value) {
                    '*' => 'Tout',
                    'username' => 'Pseudo',
                    'roles' => 'Roles',
                    'name' => 'Nom',
                    'email' => 'Email',
                    'id' => 'ID',
                },
                'label' => 'Type',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'UserFind';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {

        $resolver->setDefaults([
            'data_class' => ListSearch::class,
        ]);
    }
}
