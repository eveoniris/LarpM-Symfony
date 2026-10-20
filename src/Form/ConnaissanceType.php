<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Connaissance;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnaissanceType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'required' => true,
            'label' => 'Label',
        ])->add('file', FileType::class, [
            'label' => 'Téléversez un document',
            'required' => true, // on create only?
        ])->add('description', TextareaType::class, [
            'required' => false,
            'label' => 'Description',
            'attr' => [
                'class' => 'tinymce',
                'rows' => 5,
            ],
        ])->add('contraintes', TextareaType::class, [
            'required' => false,
            'label' => 'Prérequis',
            'attr' => [
                'class' => 'tinymce',
                'rows' => 5,
            ],
        ])->add('secret', ChoiceType::class, [
            'required' => true,
            'choices' => [
                false,
                true,
            ],
            'choice_label' => static fn ($value) => match ($value) {
                false => 'Visible',
                true => 'Secret',
                default => (string) $value,
            },
            'label' => 'Secret',
        ]);

        // Écriture réservée aux règles (ADMIN hérite de ROLE_REGLE), lecture seule pour les autres
        if ($this->security->isGranted(Role::REGLE->value)) {
            $builder->add('transmission', TextareaType::class, [
                'required' => false,
                'label' => 'Transmission',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 5,
                ],
            ]);
        }
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Connaissance::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /*
     * Nom du formulaire.
     */
}
