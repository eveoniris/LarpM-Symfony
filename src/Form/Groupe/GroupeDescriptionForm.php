<?php

namespace App\Form\Groupe;

use App\Entity\Groupe;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeDescriptionForm extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->security->isGranted(Role::SCENARISTE->value) || $this->security->isGranted(Role::ORGA->value)) {
            $builder->add('description', TextareaType::class, [
                'label' => 'Description publique du groupe',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ]);
        }

        $builder->add('description_membres', TextareaType::class, [
            'label' => 'Description du groupe pour les membres',
            'required' => false,
            'attr' => [
                'class' => 'tinymce',
                'row' => 9,
            ],
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groupe::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
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
        return 'groupeDescription';
    }
}
