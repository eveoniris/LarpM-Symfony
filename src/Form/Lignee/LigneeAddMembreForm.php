<?php

namespace App\Form\Lignee;

use App\Entity\Personnage;
use App\Entity\PersonnageLignee;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Lignee\LigneeAddMembreForm.
 *
 * @author Gérald
 */
class LigneeAddMembreForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', EntityType::class, [
            'required' => true,
            'label' => 'Choisissez le personnage membre',
            'class' => Personnage::class,
            'query_builder' => static function (EntityRepository $er) {
                /* trouve les personnages ayant une lignée */
                $sqb = $er->createQueryBuilder('m');
                $sqb->join(PersonnageLignee::class, 'pl', 'WITH', 'm.id = pl.personnage');

                $dqlString = $sqb->getQuery()->getDQL();
                /* trouve les personnages n'ayant pas de lignée */
                $qb = $er->createQueryBuilder('p');
                $qb->where('p.id NOT IN ('.$dqlString.')');
                $qb->orderBy('p.nom', 'ASC');

                return $qb;
            },
            'attr' => [
                'class' => 'selectpicker',
                'data-live-search' => 'true',
                'placeholder' => 'Nouveau membre',
            ],
            'choice_label' => 'nom',
            'mapped' => false,
        ])
            ->add('parent1', EntityType::class, [
                'required' => true,
                'label' => 'Choisissez le parent du membre',
                'class' => Personnage::class,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->orderBy('p.nom', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Parent (obligatoire)',
                ],
                'choice_label' => 'nom',
                'mapped' => false,
            ])
            ->add('parent2', EntityType::class, [
                'required' => false,
                'label' => 'Choisissez le second parent',
                'class' => Personnage::class,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->orderBy('p.nom', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Second parent (facultatif)',
                ],
                'choice_label' => 'nom',
                'mapped' => false,
            ])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Ajouter']);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'ligneeAddMembre';
    }
}
