<?php

namespace App\Form\Groupe;

use App\Entity\Groupe;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupeForm extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->security->isGranted(Role::SCENARISTE->value) || $this->security->isGranted(Role::ORGA->value)) {
            $builder->add('nom', TextType::class)
                ->add('numero', IntegerType::class, [
                    'required' => true,
                ])
                ->add('pj', ChoiceType::class, [
                    'label' => 'Type de groupe',
                    'required' => true,
                    'choices' => [
                        'Groupe composé de PJs' => true,
                        'Groupe composé PNJs' => false,
                    ],
                    'expanded' => true,
                ])
                ->add('description', TextareaType::class, [
                    'required' => false,
                    'label' => 'Description publique',
                    'attr' => [
                        'class' => 'tinymce',
                        'row' => 9,
                    ],
                ])
                ->add('scenariste', EntityType::class, [
                    'label' => 'Scénariste',
                    'required' => false,
                    'class' => User::class,
                    'choice_label' => 'name',
                    'autocomplete' => true,
                    'query_builder' => static function (EntityRepository $er) {
                        $qb = $er->createQueryBuilder('u');
                        $qb->where(
                            $qb->expr()->orX(
                                $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                                $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%'))
                            )
                        );

                        return $qb;
                    },
                ]);
        }

        $builder->add('description_membres', TextareaType::class, [
            'required' => false,
            'label' => 'Description pour les membres',
            'attr' => [
                'class' => 'tinymce',
                'row' => 9,
            ],
        ])->add('discord', TextType::class, [
            'required' => false,
            'label' => 'Lien discord',
            'helper' => 'https://discord.gg/xxxx',
            'constraints' => [
                new Assert\Callback([
                    'callback' => function (?Groupe $groupe, ExecutionContextInterface $context) {
                        if (!$groupe) {
                            return;
                        }

                        if (empty($groupe->getDiscord())) {
                            return;
                        }

                        if (!str_starts_with($groupe->getDiscord(), 'https://discord.gg/')) {
                            $context
                                ->buildViolation($this->translator->trans('groupe.discord.link'))
                                ->atPath('[discord]')
                                ->addViolation();
                        }
                    },
                ]),
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
            'searchable_fields' => ['username'],
            // for a whole secutiry access : 'security' => ['ROLE_ADMIN', 'ROLE_SCENARISTE', 'ROLE_ORGA'],
            'query_builder' => static function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('u');
                $qb->where(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%'))
                    )
                );

                return $qb;
            },
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupe';
    }
}
