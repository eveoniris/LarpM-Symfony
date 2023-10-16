<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text')
            ->add('description', 'textarea', [
                'required' => false,
            ])
            ->add('code', 'text', [
                'required' => false,
            ])
            ->add('User', 'entity', [
                'label' => 'Scénariste',
                'required' => false,
                'class' => \App\Entity\User::class,
                'property' => 'name',
            ])
            ->add('jeu_strategique', 'choice', [
                'required' => false,
                'choices' => ['false' => 'Ne participe pas', 'true' => 'Participe']])
            ->add('jeu_maritime', 'choice', [
                'required' => false,
                'choices' => ['false' => 'Ne participe pas', 'true' => 'Participe']]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Groupe::class,
        ]);
    }

    public function getName(): string
    {
        return 'groupe';
    }
}
