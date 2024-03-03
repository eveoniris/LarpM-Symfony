<?php

namespace App\Form\Debriefing;

use App\Entity\Debriefing;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * LarpManager\Form\DebriefingForm.
 *
 * @author kevin
 */
class DebriefingForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('titre', TextType::class, [
            'required' => true,
            'label' => 'Titre',
            'attr' => ['maxlength' => 45],
            'constraints' => [
                new Length(['max' => 45]),
                new NotBlank(),
            ],
        ])
            ->add('text', TextareaType::class, [
                'required' => true,
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 15,
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('player', EntityType::class, [
                'required' => false,
                'label' => 'Joueur',
                'class' => User::class,
                'choice_label' => 'Username',
                'placeholder' => 'Choisissez le joueur qui vous a fourni ce debriefing',
                'query_builder' => static function (UserRepository $p) {
                    $qb = $p->createQueryBuilder('p');
                    $qb->orderBy('p.Username', 'ASC');

                    return $qb;
                },
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('groupe', EntityType::class, [
                'required' => true,
                'label' => 'Groupe',
                'class' => Groupe::class,
                'choice_label' => 'nom',
                'query_builder' => static function (GroupeRepository $g) {
                    $qb = $g->createQueryBuilder('g');
                    $qb->orderBy('g.nom', 'ASC');

                    return $qb;
                },
            ])
            ->add('gn', EntityType::class, [
                'required' => true,
                'label' => 'GN',
                'class' => Gn::class,
                'choice_label' => 'label',
                'placeholder' => 'Choisissez le GN auquel est lié ce debriefing',
                'empty_data' => null,
            ])
            ->add('document', FileType::class, [
                'label' => 'Téléversez un document PDF',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => '.pdf'], ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Debriefing::class,
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
        return 'debriefing';
    }
}
