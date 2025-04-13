<?php

namespace App\Form\Espece;

use App\Entity\Bonus;
use App\Entity\Espece;
use App\Enum\EspeceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EspeceForm extends AbstractType
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
            ->add('secret', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'visible' => false,
                    'secrète' => true,
                ],
                'label' => 'Secret',
            ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'choices' => EspeceType::toArray(),
                'label' => 'Type',
            ])
            /* TODO ?
            ->add('bonus', EntityType::class, [
                'required' => false,
                'label' => 'Bonus',
                'class' => Bonus::class,
                'autocomplete' => true,
                'label_html' => true,
                'choice_label' => static fn (Bonus $bonus, $currentKey) => $bonus->getTitre().' - '.$bonus->getDescription(),
            ]) */
        ;
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Espece::class,
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
        return 'escepe';
    }
}
