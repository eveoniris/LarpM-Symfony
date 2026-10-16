<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Age;
use App\Entity\Genre;
use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\TerritoireRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class PersonnageUpdateType extends AbstractType
{
    private const ALERT_ICON = '<i class="fa-solid fa-circle-exclamation me-1"></i>';

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->security->isGranted(Role::SCENARISTE->value) || $this->security->isGranted(Role::ORGA->value)) {
            $builder->add('nom', TextType::class, [
                'required' => true,
                'label' => '',
            ])->add('age', EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => Age::class,
                'choice_label' => 'label',
            ])->add('genre', EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => Genre::class,
                'choice_label' => 'label',
            ])->add('territoire', EntityType::class, [
                'required' => true,
                'label' => 'Origine du personnage',
                'class' => Territoire::class,
                'choice_label' => 'nom',
                'query_builder' => static function (TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere('t.territoire IS NULL');

                    return $qb;
                },
            ]);
        }

        /** @var Personnage $personnage */
        $personnage = $builder->getData();

        $builder->add('surnom', TextType::class, [
            'required' => false,
            'label' => '',
        ])->add('intrigue', ChoiceType::class, [
            'required' => true,
            'choices' => ['Oui' => true, 'Non' => false],
            'label' => 'Participer aux intrigues',
        ])->add('sensible', ChoiceType::class, [
            'required' => true,
            'choices' => ['Non' => false, 'Oui' => true],
            ...$this->sensibleOptions($personnage),
        ])->add('bracelet', ChoiceType::class, [
            'required' => true,
            'choices' => ['Non' => false, 'Oui' => true],
            'help' => 'Si vous cochez Oui. Vous ne recevrez pas de bracelet de langue dans votre enveloppe personnage ',
            ...$this->braceletOptions($personnage),
        ]);

        if ($this->security->isGranted(Role::SCENARISTE->value) || $this->security->isGranted(Role::ORGA->value)) {
            $builder->add('scenariste', EntityType::class, [
                'label' => 'Scénariste',
                'required' => false,
                'class' => User::class,
                'choice_label' => static fn (User $user) => \sprintf('%s - %s', $user->getName(), $user->getEtatCivil()?->getFullName() ?? ''),
                'autocomplete' => true,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->where($qb->expr()->orX($qb->expr()->like('u.roles', $qb->expr()->literal('%ROLE_ADMIN%')), $qb->expr()->like('u.roles', $qb->expr()->literal('%ROLE_SCENARISTE%'))));

                    return $qb;
                },
            ]);
        }
    }

    /**
     * Un mineur est toujours sensible (règle de Personnage::isSensible()) : champ figé.
     * Tant que la valeur n'est pas renseignée, aucun choix par défaut n'est présélectionné.
     *
     * @return array<string, mixed>
     */
    private function sensibleOptions(Personnage $personnage): array
    {
        $user = $personnage->getUser();
        if (null !== $user && $user->getAgeJoueur() < 18) {
            $help = 'Les joueurs mineurs sont automatiquement considérés comme sensibles.';
            $etatCivil = $user->getEtatCivil();
            if (null !== $etatCivil && null === $etatCivil->getDateNaissance()) {
                // sans date de naissance le joueur est traité comme mineur : on renvoie vers l'état civil
                $help = \sprintf(
                    'La date de naissance du joueur n\'est pas renseignée : il est donc considéré comme mineur. <a href="%s">Renseigner la date de naissance dans l\'état civil</a>.',
                    htmlspecialchars($this->urlGenerator->generate('etatCivil.update', ['etatCivil' => $etatCivil->getId()])),
                );
            }

            return [
                'label' => 'Personnage sensible',
                'disabled' => true,
                'data' => true,
                'help' => $help,
                'help_html' => true,
            ];
        }

        if ($personnage->isSensibleSetted()) {
            return ['label' => 'Personnage sensible'];
        }

        return [
            'label_attr' => ['class' => 'text-danger'],
            'label_html' => true,
            'label' => self::ALERT_ICON . 'Personnage sensible',
            'placeholder' => '— Choisir —',
            'constraints' => [new NotNull(message: 'Veuillez indiquer si le personnage est sensible.')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function braceletOptions(Personnage $personnage): array
    {
        if ($personnage->isBraceletSetted()) {
            return ['label' => 'Possédez-vous votre bracelet de langue ?'];
        }

        return [
            'label_attr' => ['class' => 'text-danger'],
            'label_html' => true,
            'label' => self::ALERT_ICON . 'Possédez-vous votre bracelet de langue ?',
            'placeholder' => '— Choisir —',
            'constraints' => [new NotNull(message: 'Veuillez indiquer si vous possédez votre bracelet.')],
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnage::class,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}
