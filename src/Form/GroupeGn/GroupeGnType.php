<?php

declare(strict_types=1);

namespace App\Form\GroupeGn;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\GroupeGnRepository;
use App\Repository\PersonnageRepository;
use Doctrine\ORM\EntityManagerInterface;
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

class GroupeGnType extends AbstractType
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
            $builder
                ->add('gn', EntityType::class, [
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
                ->add('place_available', TextType::class, [
                    'required' => false,
                    'label' => 'Nombre de place disponible',
                ])
                /*->add('jeuStrategique', CheckboxType::class, [
                 * 'label' => 'Participe au jeu stratégique',
                 * 'required' => false,
                 * ])
                 * ->add('jeuMaritime', CheckboxType::class, [
                 * 'label' => 'Participe au jeu maritime',
                 * 'required' => false,
                 * ])*/
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
                    'label' => "Nombre d'armes de sieges",
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
        if (null === $groupeGn->getGroupe()->getTerritoire() && count($groupeGn->getGroupe()->getTerritoires()) <= 0) {
            return;
        }

        // not on new session creation
        if (null === $groupeGn->getGn()?->getId()) {
            return;
        }

        // Seul le Suzerain (ou, à défaut de Suzerain nommé, le Responsable qui joue ce rôle) OU un admin peut éditer cela
        /** @var User $user */
        $user = $this->security->getUser();
        $suzerain = $groupeGn->getSuzerain(false);

        if (!$this->security->isGranted(Role::WARGAME->value) && $suzerain?->getUser()?->getId() !== $user->getId()) {
            return;
        }

        $builder->add('suzerain', EntityType::class, $this->personnageTitreFieldOptions($groupeGn, 'suzerain', null));

        $fields = [
            'connetable' => 'Chef de guerre',
            'intendant' => 'Intendant',
            'navigateur' => 'Navigateur',
            'camarilla' => 'Eminence grise',
            'diplomate' => 'Diplomate',
        ];

        foreach ($fields as $field => $label) {
            $builder->add($field, EntityType::class, $this->personnageTitreFieldOptions($groupeGn, $field, $label));
        }
    }

    /**
     * Options communes (query_builder + validation) pour un champ de titre du groupe_gn.
     *
     * @return array<string, mixed>
     */
    private function personnageTitreFieldOptions(GroupeGn $groupeGn, string $field, ?string $label): array
    {
        /** @var GroupeGnRepository $groupeGnRepository */
        $groupeGnRepository = $this->entityManager->getRepository(GroupeGn::class);
        $gn = $groupeGn->getGn();

        return [
            'choice_label' => static fn (Personnage $personnage, $key, $index) => $personnage->getId() . ' - ' . $personnage->getNameSurname(),
            'autocomplete' => true,
            'required' => false,
            'class' => Personnage::class,
            'placeholder' => 'Choisissez un personnage',
            'empty_data' => null,
            'label' => $label,
            // Personnages vivants des utilisateurs participants (tous leurs personnages), hors titres déjà attribués ailleurs
            'query_builder' => static fn (PersonnageRepository $personnageRepository) => $groupeGnRepository->excludeAlreadyTitled($personnageRepository->findVivantsParticipantsAuGroupeGn($groupeGn), 'p', $gn, $groupeGn),
            'constraints' => [
                /* @phpstan-ignore argument.type */
                new Assert\Callback(function (?Personnage $personnage, ExecutionContextInterface $context) use ($field, $groupeGn, $groupeGnRepository): void {
                    if (!$personnage) {
                        return;
                    }

                    // Has titres in other groupe
                    $titres = $groupeGnRepository->getTitres($personnage, $groupeGn->getGn(), $groupeGn);

                    if (!empty($titres)) {
                        $context
                            ->buildViolation($this->translator->trans('groupeGn.titre.unique', [
                                '%personnageName%' => $personnage->getIdName(),
                                '%titres%' => $titres,
                            ]))
                            ->atPath('[' . $field . ']')
                            ->addViolation();
                    }

                    // has more than one title
                    $nbTitresInGroupe = $groupeGnRepository->countTitres($personnage, $groupeGn->getGn());

                    if ($nbTitresInGroupe > 1) {
                        $context
                            ->buildViolation($this->translator->trans('groupeGn.titre.unique', [
                                '%personnageName%' => $personnage->getIdName(),
                                '%titres%' => $groupeGnRepository->getTitres($personnage, $groupeGn->getGn()),
                            ]))
                            ->atPath('[' . $field . ']')
                            ->addViolation();
                    }
                }),
            ],
        ];
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['class' => GroupeGn::class]);
    }

    /*
     * Nom du formulaire.
     */
}
