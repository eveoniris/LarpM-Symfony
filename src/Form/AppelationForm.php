<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\AppelationForm.
 *
 * @author kevin
 */
class AppelationForm extends AbstractType
{
    /**
     * Construction du formualire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('titre', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Titre',
                'required' => false,
            ])
            ->add('appelation', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Cette appelation dépend de',
                'required' => false,
                'class' => \App\Entity\Appelation::class,
                'property' => 'label',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Appelation::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'appelation';
    }
}
