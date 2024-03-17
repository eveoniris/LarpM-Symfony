<?php


namespace App\Form\Territoire;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\GroupFindForm.
 *
 * @author kevin
 */
class FiefForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => false,
            'attr' => [
                'placeholder' => 'Votre recherche',
            ],
            ])
            ->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'idFief' => 'Id du fief',
                    'nomFief' => 'Nom du fief',
                ],
            ])
            ->add('pays', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Par pays',
                'class' => \App\Entity\Territoire::class,
                'choices' => $options['listePays'],
                'placeholder' => 'Filtrer par pays',
            ])
            ->add('province', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Par province',
                'class' => \App\Entity\Territoire::class,
                'choices' => $options['listeProvinces'],
                'placeholder' => 'Filtrer par province',
            ])
            ->add('groupe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Par groupe',
                'class' => \App\Entity\Groupe::class,
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
            ]
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
