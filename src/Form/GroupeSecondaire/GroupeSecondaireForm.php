<?php

namespace App\Form\GroupeSecondaire;

use App\Entity\Groupe;
use App\Entity\Personnage;
use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupeSecondaireForm extends AbstractType
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
        if ($this->security->isGranted(Role::ROLE_GROUPE_TRANSVERSE->value)) {
            $builder->add('label', TextType::class)
                ->add('description', TextareaType::class, [
                    'required' => true,
                    'label' => 'Description',
                    'attr' => [
                        'rows' => 9,
                        'class' => 'tinymce',
                    ],
                ])
                ->add('description_secrete', TextareaType::class, [
                    'required' => true,
                    'label' => 'Description des secrets',
                    'attr' => [
                        'rows' => 9,
                        'class' => 'tinymce',
                        'help' => 'les secrets ne sont accessibles qu\'aux membres selectionnés par le scénariste',
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
                                $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%')),
                            ),
                        );

                        return $qb;
                    },
                ])
                ->add('personnage', EntityType::class, [
                    'required' => false,
                    'label' => 'Chef du groupe',
                    'class' => Personnage::class,
                    'query_builder' => static fn(EntityRepository $er) => $er->createQueryBuilder('p')->orderBy(
                        'p.nom',
                        'ASC',
                    ),
                    'choice_label' => 'nom',
                    // 'mapped' => false,
                ])
                ->add('secondaryGroupType', EntityType::class, [
                    'label' => 'Type',
                    'required' => true,
                    'class' => SecondaryGroupType::class,
                    'choice_label' => 'label',
                ])
                ->add('secret', CheckboxType::class, [
                    'label' => 'Cochez cette case pour rendre le groupe secret (visible uniquement par les joueurs membres)',
                    'required' => false,
                ]);
        }

        $builder->add('private', CheckboxType::class, [
            'label' => 'Cochez cette case pour rendre le groupe privé (Seul le chef et ceux qui peuvent voir les secrets verront les autres membres)',
            'required' => false,
        ])
            ->add('show_discord', CheckboxType::class, [
                'label' => 'Cochez cette case pour afficher le lien de votre serveur discord pour tout le monde si le groupe est publique)',
                'required' => false,
            ])
            ->add('discord', TextType::class, [
                'required' => false,
                'label' => 'Lien discord',
                'help' => 'https://discord.gg/xxxx',
                'constraints' => [
                    new Assert\Callback([
                        'callback' => function (?string $data, ExecutionContextInterface $context) {
                            if (empty($data)) {
                                return;
                            }

                            if (!str_starts_with($data, 'https://discord.gg/')) {
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
            'data_class' => SecondaryGroup::class,
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
        return 'secondaryGroup';
    }
}
