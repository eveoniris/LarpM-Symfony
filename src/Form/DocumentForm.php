<?php

namespace App\Form;

use App\Entity\Document;
use App\Entity\Langue;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\DocumentForm.
 *
 * @author kevin
 */
class DocumentForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'required' => true,
            'attr' => [
                'help' => 'Le code d\'un document permet de l\'identifier rapidement. Il se construit de la manière suivante : L3_DJ_TE_005. L3 correspond à l\'opus de création. DJ correspond à Document en Jeu. TE correspond à TExte. 005 correspond à son numéro (suivez la numérotation des documents déjà créé)',
            ],
        ])
            ->add('titre', TextType::class, [
                'required' => true,
            ])
            ->add('auteur', TextType::class, [
                'required' => true,
                'empty_data' => null,
                'attr' => [
                    'help' => 'Indiquez l\'auteur (en jeu) du document. Cet auteur est soit un personnage fictif (p.e. le célébre poète Archibald) ou l\'un des personnage joué par un joueur',
                ],
            ])
            ->add('langues', EntityType::class, [
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'label' => 'Langues dans lesquelles le document est rédigé',
                'class' => Langue::class,
                'query_builder' => static function (LangueRepository $er) {
                    return $er->createQueryBuilder('l')->orderBy('l.label', 'ASC');
                },
                'choice_label' => 'label',
                'attr' => [
                    'help' => 'Vous pouvez choisir une ou plusieurs langues',
                ],
            ])
            ->add('cryptage', ChoiceType::class, [
                'required' => true,
                'choices' => [false => 'Non crypté', true => 'Crypté'],
                'label' => 'Indiquez si le document est crypté',
                'attr' => [
                    'help' => 'Un document crypté est rédigé dans la langue indiqué, mais le joueur doit le décrypter de lui-même (p.e rédigé en aquilonien, mais utilisant un code type césar)',
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 9,
                    'help' => 'Une courte description du document permet d\'éviter de télécharger et d\'ouvrir le document pour comprendre quel est son contenu.',
                ],
            ])
            ->add('statut', TextType::class, [
                'required' => false,
                'attr' => [
                    'help' => 'Une courte description du document permet d\'éviter de télécharger et d\'ouvrir le document pour comprendre quel est son contenu.',
                ],
            ])
            ->add('impression', ChoiceType::class, [
                'required' => false,
                'choices' => [false => 'Non imprimé', true => 'Imprimé'],
                'label' => 'Indiquez si le document a été imprimé',
                'attr' => [
                    'help' => 'Le responsable des documents devra indiqué pour chacun des documents s\'ils ont été imprimés ou pas.',
                ],
            ])
            ->add('document', 'file', [
                'label' => 'Choisissez votre fichier',
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'help' => 'Téléversez le fichier PDF correspondant à votre document.',
                ],
            ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Document::class,
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
        return 'document';
    }
}
