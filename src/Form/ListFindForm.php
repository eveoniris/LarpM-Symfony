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
    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', TextType::class, [
            'required' => false,
            'label' => 'Recherche',
        ]);

        if ($options['choices'] ?? false) {
            $builder->add('type', ChoiceType::class, $options['choices']);
        }
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'required' => true,
                'choices' => [
                    '*',
                    'id',
                    'label',
                    'description',
                ],
                'choice_label' => static fn ($value) => match ($value) {
                    '*' => 'Tout critère',
                    'label' => 'Libellé',
                    'description' => 'Description',
                    'id' => 'ID',
                },
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'listFind';
    }
}
