<?php


namespace App\Form\GroupeGn;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeGnForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('gn', EntityType::class, [
            'label' => 'Jeu',
            'required' => true,
            'class' => Gn::class,
            'choice_label' => 'label',
        ])
            ->add('free', ChoiceType::class, [
                'label' => 'Groupe disponible ou réservé ?',
                'required' => false,
                'choices' => [
                    'Groupe disponible' => true,
                    'Groupe réservé' => false,
                ],
            ])
            ->add('code', TextType::class, [
                'required' => false,
            ])
            ->add('jeuStrategique', CheckboxType::class, [
                'label' => 'Participe au jeu stratégique',
                'required' => false,
            ])
            ->add('jeuMaritime', CheckboxType::class, [
                'label' => 'Participe au jeu maritime',
                'required' => false,
            ])
            ->add('agents', IntegerType::class, [
                'label' => "Nombre d'agents",
                'required' => false,
            ])
            ->add('bateaux', IntegerType::class, [
                'label' => "Nombre de bateaux",
                'required' => false,
            ])
            ->add('sieges', IntegerType::class, [
                'label' => "Nombre d'ordres de sieges",
                'required' => false,
            ])
            ->add('initiative', IntegerType::class, [
                'label' => "Initiative",
                'required' => false,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => GroupeGn::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeGn';
    }
}
