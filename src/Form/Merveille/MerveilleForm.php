<?php

namespace App\Form\Merveille;

use App\Entity\Bonus;
use App\Entity\Merveille;
use App\Entity\Territoire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MerveilleForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, [
            'required' => true,
            'label' => 'Nom',
        ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description succinte',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                ],
            ])
            ->add('description_scenariste', TextareaType::class, [
                'required' => false,
                'label' => 'Description pour scenariste',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                ],
            ])
            ->add('description_cartographe', TextareaType::class, [
                'required' => false,
                'label' => 'Description pour cartographe',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'En attente' => 'pending',
                    'En construction' => 'build',
                    'Active' => 'active',
                    'Détruite' => 'destroyed',
                    'Supprimée' => 'deleted',
                ],
                'label' => 'Etat',
            ])
            ->add('date_creation', DateType::class, [
                'label' => 'Date GN de création',
                'required' => true,
            ])
            ->add('date_destruction', DateType::class, [
                'label' => 'Date GN de destruction',
                'required' => true,
            ])
            ->add('bonus', EntityType::class, [
                'required' => false,
                'label' => 'Bonus',
                'class' => Bonus::class,
                'autocomplete' => true,
                'label_html' => true,
                'choice_label' => static fn (Bonus $bonus, $currentKey) => $bonus->getTitre().' - '.$bonus->getDescription(),
            ])
            ->add('territoire', EntityType::class, [
                'required' => false,
                'label' => 'Territoire',
                'class' => Territoire::class,
                'autocomplete' => true,
                'choice_label' => 'nom',
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Merveille::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'merveille';
    }
}
