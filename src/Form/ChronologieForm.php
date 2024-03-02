<?php


namespace App\Form;

use App\Entity\Chronologie;
use App\Entity\Territoire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChronologieForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territoire', EntityType::class, [
                'required' => true,
                'label' => 'Territoire',
                'class' => Territoire::class,
                'choice_label' => 'nom',
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Description',
                'attr' => ['rows' => 9],
            ])
            ->add('year', IntegerType::class, [
                'required' => true,
                'label' => 'Année',
            ])
            ->add('month', IntegerType::class, [
                'required' => false,
                'label' => 'Mois (falcultatif)',
            ])
            ->add('day', IntegerType::class, [
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
            'data_class' => Chronologie::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
            'attr' => [
                'novalidate' => 'novalidate',
            ],
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
