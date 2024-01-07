<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\ObjetFindForm.
 *
 * @author Gectou4
 */
class ObjetFindForm extends AbstractType
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
                    'nom' => 'Nom',
                    'description' => 'Description',
                    'numero' => 'Numero',
                ],
                'label' => 'Type',
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'objetFind';
    }
}
