<?php

namespace App\Form\Type;

use App\Entity\Localisation;
use App\Entity\Rangement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RangementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, ['attr' => ['help' => 'Les trois premières lettres (avec le numéro de l\'objet) servirons à créer le code identifiant un objet']])
            ->add('localisation', EntityType::class, ['class' => Localisation::class, 'choice_label' => 'label'])
            ->add('precision', TextareaType::class, ['required' => false, 'attr' => ['help' => '']]);
    }

    public function setDefaultOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rangement::class,
        ]);
    }

    public function getName(): string
    {
        return 'rangement';
    }
}
