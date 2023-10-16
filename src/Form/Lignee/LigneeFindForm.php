<?php

namespace App\Form\Lignee;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\LigneeFindForm.
 *
 * @author gerald
 */
class LigneeFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('search', TextType::class, [
            'required' => true,
            'attr' => [
                'placeholder' => 'Votre recherche',
            ],
        ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'nom' => 'Nom de la lignée',
                    'id' => 'ID de la lignée',
                ],
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'ligneeFind';
    }
}
