<?php

namespace App\Form\Stock;

use App\Entity\Objet;
use App\Entity\Photo;
use App\Form\Type\ObjetCaracType;
use App\Form\Type\PhotoType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjetForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, ['required' => true])
            ->add('numero', TextType::class, ['required' => true, 'label' => 'Numero de stock'])
            ->add('description', TextareaType::class, ['required' => false])
            ->add('photo', PhotoType::class, ['required' => false])
            ->add('proprietaire', EntityType::class, ['required' => false, 'class' => \App\Entity\Proprietaire::class, 'choice_label' => 'nom'])
            ->add('responsable', EntityType::class, ['required' => false, 'class' => \App\Entity\User::class, 'choice_label' => 'name'])
            ->add('rangement', EntityType::class, ['required' => false, 'class' => \App\Entity\Rangement::class, 'choice_label' => 'adresse'])
            ->add('etat', EntityType::class, ['required' => false, 'class' => \App\Entity\Etat::class, 'choice_label' => 'label'])
            ->add('tags', EntityType::class, ['required' => false, 'class' => \App\Entity\Tag::class, 'choice_label' => 'nom', 'multiple' => true])
            ->add('objetCarac', ObjetCaracType::class, ['required' => false])
            ->add('cout', IntegerType::class, ['required' => false])
            ->add('nombre', IntegerType::class, ['required' => false])
            ->add('budget', IntegerType::class, ['required' => false])
            ->add('investissement', ChoiceType::class, ['choices' => ['true' => 'ré-utilisable', 'false' => 'usage unique']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Objet::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'objet';
    }
}
