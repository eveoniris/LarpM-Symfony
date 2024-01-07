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
        $builder->add('title', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
            'label' => 'Titre',
            'attr' => [
                'placeholder' => 'Nouveau message',
            ],
        ])
            ->add('UserRelatedByDestinataire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
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
            ->add('text', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
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
