<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\NewMessageForm.
 *
 * @author kevin
 */
class NewMessageForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', 'text', [
            'required' => true,
            'label' => 'Titre',
            'attr' => [
                'placeholder' => 'Nouveau message',
            ],
        ])
            ->add('UserRelatedByDestinataire', 'entity', [
                'required' => true,
                'label' => 'Destinataire',
                'class' => \App\Entity\User::class,
                'property' => 'UserName',
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Destinataire',
                ],
                'query_builder' => static function ($er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->orderBy('u.Username', 'ASC');

                    return $qb;
                },
            ])
            ->add('text', 'textarea', [
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
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Message::class,
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
