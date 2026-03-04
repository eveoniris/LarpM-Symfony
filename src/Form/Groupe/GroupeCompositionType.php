<?php

declare(strict_types=1);

namespace App\Form\Groupe;

use App\Entity\Groupe;
use App\Form\Type\ClasseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Groupe\GroupeCompositionForm.
 *
 * @author kevin
 */
class GroupeCompositionType extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('groupeClasses', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
            'label' => 'Composition',
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'entry_type' => ClasseType::class,
        ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groupe::class,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}
