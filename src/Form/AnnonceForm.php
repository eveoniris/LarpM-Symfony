<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
                'choices' => [false => 'Publique', true => 'Dans les archive'],
                'label' => 'Choisissez la visibilité de votre annonce',
            ])
            ->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Choisissez le jeu auquel cette annonce fait référence',
                'required' => true,
                'multiple' => false,
                'class' => \App\Entity\Gn::class,
                'property' => 'label',
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
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Annonce::class,
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
