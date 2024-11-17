<?php


namespace App\Form;

use App\Entity\Gn;
use App\Entity\PersonnageBackground;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageBackgroundForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', TextareaType::class, [
            'required' => false,
            'label' => 'Background',
            'attr' => [
                'class' => 'tinymce',
                'rows' => 9],
        ])
            ->add('gn', EntityType::class, [
                'required' => true,
                'label' => 'GN',
                'class' => Gn::class,
                'choice_label' => 'label',
                'placeholder' => 'Choisissez le GN auquel est lié ce background',
                'empty_data' => null,
            ])
            ->add('visibility', ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => [
                    'Seul les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Le proprietaire du personnage peut voir ceci' => 'OWNER',
                ],
            ])
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']]
            );
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PersonnageBackground::class,
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
        return 'personnageBackground';
    }
}
