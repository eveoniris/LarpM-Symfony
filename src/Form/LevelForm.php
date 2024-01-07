<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\LevelForm.
 *
 * @author kevin
 */
class LevelForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
        ])
            ->add('index', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => true,
            ])
            ->add('cout_favori', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Coût favori',
                'required' => true,
            ])
            ->add('cout', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Coût normal',
                'required' => true,
            ])
            ->add('cout_meconu', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Coût méconnu',
                'required' => true,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data' => \App\Entity\Level::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'levelForm';
    }
}
