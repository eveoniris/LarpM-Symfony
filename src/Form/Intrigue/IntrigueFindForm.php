<?php


namespace App\Form\Intrigue;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Intrigue\IntrigueFindForm.
 *
 * @author kevin
 */
class IntrigueFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
            'attr' => [
                'placeholder' => 'Votre recherche',
            ],
        ])
            ->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Titre' => 'titre',
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
        return 'intrigueFind';
    }
}
