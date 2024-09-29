<?php


namespace App\Form;

use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\Groupe;
use App\Entity\Personnage;
use App\Entity\Religion;
use App\Repository\PersonnageRepository;
use App\Repository\ReligionRepository;
use App\Repository\ClasseRepository;
use App\Repository\CompetenceRepository;
use App\Repository\GroupeRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageOldFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', EntityType::class, [
            'label' => 'Choisissez le personnage',
            'choice_label' => 'nom',
            'autocomplete' => true,
            'class' => Personnage::class,
            'query_builder' => static fn (PersonnageRepository $personnageRepository
            ) => $personnageRepository
                ->createQueryBuilder('p')
                ->innerjoin('p.user', 'u', Join::WITH, 'p.user = :uid')
                ->where('p.vivant = :vivant')
                ->setParameter('vivant', true)
                ->setParameter('uid', $builder->getData()->getUser()->getId())
                ->orderBy('p.nom', 'ASC'),
        ])
            ->add('save', SubmitType::class, ['label' => 'Valider'])
        ;
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Personnage::class,
            'searchable_fields' => ['nom', 'surnom', 'numero'],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageOldFind';
    }
}
