<?php

namespace App\Form;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewMessageForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, [
            'required' => true,
            'label' => 'Titre',
            'attr' => [
                'placeholder' => 'Nouveau message',
            ],
        ])
            ->add('UserRelatedByDestinataire', EntityType::class, [
                'required' => true,
                'label' => 'Destinataire',
                'class' => User::class,
                'choice_label' => 'UserName',
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Destinataire',
                ],
                'query_builder' => static fn (UserRepository $er) => $er->createQueryBuilder('u')->orderBy('u.username', 'ASC'),
            ])
            ->add('text', TextareaType::class, [
                'required' => true,
                'label' => 'Message',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                ],
            ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Message::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'newMessage';
    }
}
