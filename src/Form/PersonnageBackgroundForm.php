<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageBackgroundForm.
 *
 * @author kevin
 */
class PersonnageBackgroundForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'required' => false,
            'label' => 'Background',
            'attr' => [
                'class' => 'tinymce',
                'rows' => 9],
        ])
            ->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => 'GN',
                'class' => \App\Entity\Gn::class,
                'choice_label' => 'label',
                'placeholder' => 'Choisissez le GN auquel est lié ce background',
                'empty_data' => null,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\PersonnageBackground::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageBackground';
    }
}
