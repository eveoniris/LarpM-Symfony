<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompetenceNiveauForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('competence', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => true,
            'class' => \App\Entity\Competence::class,
            'choice_label' => 'nom',
        ])
            ->add('niveau', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'class' => 'App\Entity\Niveau',
                'choice_label' => 'label',
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data' => 'App\Entity\CompetenceNiveau',
        ]);
    }

    public function getName(): string
    {
        return 'competenceNiveauForm';
    }
}
