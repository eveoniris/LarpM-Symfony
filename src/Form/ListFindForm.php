<?php

namespace App\Form;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListFindForm extends AbstractType
{
    public function __construct(protected readonly ?EntityManagerInterface $entityManager = null)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', TextType::class, [
            'required' => false,
            'label' => 'Recherche',
        ]);

        if ($options['type_choices'] ?? false) {
            $builder->add('type', ChoiceType::class, $options['type_choices']);
        }
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type_choices' => [
                'required' => true,
                'choices' => [
                    'Tout critère' => '*',
                    'Id' => 'id',
                    'Libellé' => 'label',
                    'Description' => 'description',
                ],
            ],
        ]);

        $resolver->setAllowedTypes('type_choices', 'array');
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'listFind';
    }
}
