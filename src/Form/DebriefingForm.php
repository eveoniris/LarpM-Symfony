<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\DebriefingForm.
 *
 * @author kevin
 */
class DebriefingForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => true,
            'attr' => [
                'help' => 'A quel GN correspond ce debriefing ?',
            ],
        ])
            ->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'attr' => [
                    'help' => 'A quel GN correspond ce debriefing ?',
                ],
            ])
            ->add('text', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => true,
                'attr' => [
                    // TODO 'class' => 'tinymce',
                    'rows' => 9],
            ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Debriefing::class,
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'debriefing';
    }
}
