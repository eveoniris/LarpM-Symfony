<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\BackgroundFindForm.
 *
 * @author kevin
 */
class BackgroundFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
            'label' => 'Recherche',
        ])
            ->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Auteur' => 'auteur',
                    'Groupe' => 'groupe',
                ],
                'label' => 'Type',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'backgroundFind';
    }
}
