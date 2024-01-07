<?php


namespace App\Form\Personnage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Personnage\PersonnageEditForm.
 *
 * @author kevin
 */
class PersonnageEditForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('surnom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => false,
            'label' => '',
        ])
            ->add('intrigue', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [true => 'Oui', false => 'Non'],
                'label' => 'Participer aux intrigues',
            ])
            ->add('sensible', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [false => 'Non', true => 'Oui'],
                'label' => 'Personnage sensible',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageEdit';
    }
}
