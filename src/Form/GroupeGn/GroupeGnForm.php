<?php

namespace App\Form\GroupeGn;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Personnage;
use App\Enum\Role;
use App\Repository\PersonnageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
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

class GroupeGnForm extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->security->isGranted(Role::WARGAME->value)) {
            $builder->add('gn', EntityType::class, [
                'label' => 'Jeu',
                'required' => true,
                'class' => Gn::class,
                'choice_label' => 'label',
            ])
                ->add('free', ChoiceType::class, [
                    'label' => 'Groupe disponible ou réservé ?',
                    'required' => false,
                    'choices' => [
                        'Groupe disponible' => true,
                        'Groupe réservé' => false,
                    ],
                ])
                ->add('code', TextType::class, [
                    'required' => false,
                ])
                /*->add('jeuStrategique', CheckboxType::class, [
                    'label' => 'Participe au jeu stratégique',
                    'required' => false,
                ])
                ->add('jeuMaritime', CheckboxType::class, [
                    'label' => 'Participe au jeu maritime',
                    'required' => false,
                ])*/
                ->add('agents', IntegerType::class, [
                    'label' => "Nombre d'agents",
                    'required' => false,
                ])
                ->add('bateaux', IntegerType::class, [
                    'label' => 'Nombre de bateaux',
                    'required' => false,
                ])
                ->add('bateaux_localisation', TextareaType::class, [
                    'label' => 'Emplacement des bateaux',
                    'required' => false,
                ])
                ->add('sieges', IntegerType::class, [
                    'label' => "Nombre d'ordres de sieges",
                    'required' => false,
                ])
                ->add('initiative', IntegerType::class, [
                    'label' => 'Initiative',
                    'required' => false,
                ]);
        }

        // Les titres sont uniquement possible s'il y a un territoire
        /** @var GroupeGn $groupeGn */
        $groupeGn = $builder->getData();
        if (null === $groupeGn->getGroupe()->getTerritoire()) {
            return;
        }

        $fieldCallback = function (string $child, string $label) use ($options, $groupeGn) {
            return [
                'choice_label' => static fn (Personnage $personnage, $key, $index) => $personnage->getId(
                ).' - '.$personnage->getNameSurname(),
                'autocomplete' => true,
                'required' => false,
                'class' => Personnage::class,
                'placeholder' => 'Choisissez un personnage',
                'empty_data' => null,
                // On veut tous les personnages vivant du GN (pas que ceux du groupe)
                'query_builder' => static fn (PersonnageRepository $personnageRepository,
                ) => $personnageRepository // TODO and PID not IN groupeGn titres
                ->createQueryBuilder('p')
                    ->innerjoin('p.participants', 'parti', Join::WITH, 'p.id = parti.personnage')
                    // ->leftjoin('parti.groupeGn', 'g', Join::WITH, 'g.id = parti.groupeGn') // AND titre_id is null
                    ->where('p.vivant = :vivant AND parti.gn = :gnid')
                    // ->where('p.vivant = :vivant AND g.id = :groupe_gn_id')
                    ->setParameter('vivant', true)
                    ->setParameter('gnid', $groupeGn->getGn()->getId())
                    // ->setParameter('groupe_gn_id', $builder->getData()->getId())
                    ->orderBy('p.nom', 'ASC'),
                'constraints' => [
                    new Assert\Callback([
                        'callback' => function (?Personnage $personnage, ExecutionContextInterface $context) use (
                            $options,
                            $child
                        ) {
                            if (!$personnage) {
                                return;
                            }

                            $groupeGnRepository = $this->entityManager->getRepository(GroupeGn::class);

                            // TODO : allow on it self ;

                            $titres = $groupeGnRepository->getTitres($personnage, $options['gn'] ?? null);

                            if (!empty($titres)) {
                                $context
                                    ->buildViolation(
                                        $this->translator->trans(
                                            'groupeGn.titre.unique',
                                            [
                                                '%personnageName%' => $personnage->getIdName(),
                                                '%titres%' => $titres,
                                            ]
                                        )
                                    )
                                    ->atPath('['.$child.']')
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
            ];
        };

        $fields = [
            'suzerin' => 'Suzerin',
            'connetable' => 'Chef de guerre',
            'intendant' => 'Intendant',
            'navigateur' => 'Navigateur',
            'camarilla' => 'Eminence grise',
        ];

        foreach ($fields as $field => $label) {
            $builder->add($field, EntityType::class, [...$fieldCallback($field, $label), 'label' => $label]);
        }
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => GroupeGn::class,
            'gn' => null,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeGn';
    }
}
