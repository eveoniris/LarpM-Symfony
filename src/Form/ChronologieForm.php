<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ChronologieForm.
 *
 * @author kevin
 */
class ChronologieForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territoire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => 'Territoire',
                'class' => \App\Entity\Territoire::class,
                'choice_label' => 'nom',
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => true,
                'label' => 'Description',
                'attr' => ['rows' => 9],
            ])
            ->add('year', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => true,
                'label' => 'Année',
            ])
            ->add('month', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => false,
                'label' => 'Mois (falcultatif)',
            ])
            ->add('day', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => false,
                'label' => 'Jour (falcultatif)',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Chronologie::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'chronologie';
    }
}
