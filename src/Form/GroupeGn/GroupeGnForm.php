<?php

namespace App\Form\GroupeGn;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Personnage;
use App\Enum\Role;
use App\Repository\PersonnageRepository;
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

class GroupeGnForm extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
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

        // Seul un admin ou le suzerin peu changer cela
        if (!$this->security->isGranted(Role::WARGAME->value) || !$this->security->getUser()?->getId() === $groupeGn->getSuzerin()?->getId()) {
            return;
        }

        $fields = [
            'choice_label' => static fn (Personnage $personnage, $key, $index) => $personnage->getId().' - '.$personnage->getNameSurname(),
            'autocomplete' => true,
            'required' => false,
            'class' => Personnage::class,
            'placeholder' => 'Choisissez un personnage',
            'empty_data' => null,
            // On veut tous les personnages vivant du GN (pas que ceux du groupe)
            'query_builder' => static fn (PersonnageRepository $personnageRepository,
            ) => $personnageRepository
                ->createQueryBuilder('p')
                ->innerjoin('p.participants', 'parti', Join::WITH, 'p.id = parti.personnage')
               // ->innerjoin('parti.groupeGn', 'g', Join::WITH, 'g.id = parti.groupeGn')
                ->where('p.vivant = :vivant AND parti.gn = :gnid')
                // ->where('p.vivant = :vivant AND g.id = :groupe_gn_id')
                ->setParameter('vivant', true)
                ->setParameter('gnid', $groupeGn->getGn()->getId())
                // ->setParameter('groupe_gn_id', $builder->getData()->getId())
                ->orderBy('p.nom', 'ASC'),
        ];

        $builder->add('suzerin', EntityType::class, [...$fields, 'label' => 'Suzerin'])
            ->add('connetable', EntityType::class, [...$fields, 'label' => 'Chef de guerre'])
            ->add('intendant', EntityType::class, [...$fields, 'label' => 'Intendant'])
            ->add('navigateur', EntityType::class, [...$fields, 'label' => 'Navigateur'])
            ->add('camarilla', EntityType::class, [...$fields, 'label' => 'Eminence grise']);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => GroupeGn::class,
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
