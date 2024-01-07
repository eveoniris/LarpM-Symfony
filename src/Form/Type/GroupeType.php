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
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false,
            ])
            ->add('code', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => false,
            ])
            ->add('User', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Scénariste',
                'required' => false,
                'class' => \App\Entity\User::class,
                'property' => 'name',
            ])
            ->add('jeu_strategique', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => false,
                'choices' => ['false' => 'Ne participe pas', 'true' => 'Participe']])
            ->add('jeu_maritime', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
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
