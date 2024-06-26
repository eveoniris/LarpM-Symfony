<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\ObjetType.
 *
 * @author kevin
 */
class ObjetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => true])
            ->add('numero', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => true])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, ['required' => false])
            ->add('photo', new PhotoType(), ['required' => false])
            ->add('proprietaire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, ['required' => false, 'class' => \App\Entity\Proprietaire::class, 'choice_label' => 'nom'])
            ->add('responsable', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, ['required' => false, 'class' => \App\Entity\User::class, 'choice_label' => 'name'])
            ->add('rangement', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, ['required' => false, 'class' => \App\Entity\Rangement::class, 'choice_label' => 'adresse'])
            ->add('etat', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, ['required' => false, 'class' => \App\Entity\Etat::class, 'choice_label' => 'label'])
            ->add('tags', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, ['required' => false, 'class' => \App\Entity\Tag::class, 'choice_label' => 'nom', 'multiple' => true])
            ->add('objetCarac', new ObjetCaracType(), ['required' => false])
            ->add('cout', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, ['required' => false])
            ->add('nombre', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, ['required' => false])
            ->add('budget', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, ['required' => false])
            ->add('investissement', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, ['choices' => ['false' => 'usage unique', 'true' => 'ré-utilisable']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Objet::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'objet';
    }
}
