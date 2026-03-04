<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ReligionLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ReligionForm.
 *
 * @author kevin
 */
class ReligionLevelType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'label' => 'Description',
            'required' => false,
            'attr' => ['rows' => 10],
        ])->add('index', 'number', [
            'label' => 'Index',
            'required' => true,
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReligionLevel::class,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}
