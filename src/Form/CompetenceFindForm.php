<?php

namespace App\Form;

use App\Form\Entity\BaseSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CompetenceFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', TextType::class, [
            'required' => true,
            'label' => 'Recherche',
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
        return 'CompetenceFind';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $constraints = [
            'value' => new NotBlank(),
        ];

        $resolver->setDefaults([
            'data_class' => BaseSearch::class,
     //       'constraints' => $constraints,
        ]);
    }
}
