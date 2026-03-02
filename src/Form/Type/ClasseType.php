<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\GroupeClasse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\ClasseType.
 *
 * @author kevin
 */
class ClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('classe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => false,
            'required' => true,
            'choice_label' => 'label',
            'class' => \App\Entity\Classe::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GroupeClasse::class,
        ]);
    }

    public function getName(): string
    {
        return 'groupeClasse';
    }
}
