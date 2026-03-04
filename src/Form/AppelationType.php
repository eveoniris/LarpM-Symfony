<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Appelation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\AppelationForm.
 *
 * @author kevin
 */
class AppelationType extends AbstractType
{
    /**
     * Construction du formualire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])->add('description', TextareaType::class, [
            'label' => 'Description',
            'required' => false,
            'attr' => ['rows' => 10],
        ])->add('titre', TextType::class, [
            'label' => 'Titre',
            'required' => false,
        ])->add('appelation', EntityType::class, [
            'label' => 'Cette appelation dépend de',
            'required' => false,
            'class' => Appelation::class,
            'choice_label' => 'label',
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appelation::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
}
