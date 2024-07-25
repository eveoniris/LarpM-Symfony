<?php


namespace App\Form;

use App\Entity\Annonce;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\AnnonceForm.
 *
 * @author kevin
 */
class AnnonceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Titre',
            'required' => true,
        ])
            ->add('archive', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => ['Publique' => false, 'Dans les archive' => true],
                'label' => 'Choisissez la visibilité de votre annonce',
            ])
            ->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Choisissez le jeu auquel cette annonce fait référence',
                'required' => true,
                'multiple' => false,
                'class' => \App\Entity\Gn::class,
                'choice_label' => 'label',
                'empty_data' => null,
                'placeholder' => 'Aucun',
            ])
            ->add('text', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => true,
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce'],
            ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Annonce::class,
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
        return 'annonce';
    }
}
