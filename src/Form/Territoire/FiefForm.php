<?php


namespace App\Form\Territoire;

use App\Entity\Groupe;
use App\Entity\Territoire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FiefForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', TextType::class, [
            'required' => false,
            'attr' => [
                'placeholder' => 'Votre recherche',
            ],
        ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Id du fief' => 'idFief',
                    'Nom du fief' => 'nomFief',
                ],
            ])
            ->add('pays', EntityType::class, [
                'required' => false,
                'label' => 'Par pays',
                'class' => Territoire::class,
                'choices' => $options['listePays'],
                'placeholder' => 'Filtrer par pays',
            ])
            ->add('province', EntityType::class, [
                'required' => false,
                'label' => 'Par province',
                'class' => Territoire::class,
                'choices' => $options['listeProvinces'],
                'placeholder' => 'Filtrer par province',
            ])
            ->add('groupe', EntityType::class, [
                'required' => false,
                'label' => 'Par groupe',
                'class' => Groupe::class,
                'choices' => $options['listeGroupes'],
                'placeholder' => 'Filtrer par groupe',
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'listeGroupes' => '',
                'listePays' => '',
                'listeProvinces' => '',
            ],
        );
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'fief';
    }
}
