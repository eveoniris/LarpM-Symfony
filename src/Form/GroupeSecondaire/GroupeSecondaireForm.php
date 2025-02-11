<?php

namespace App\Form\GroupeSecondaire;

use App\Entity\Personnage;
use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeSecondaireForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Description',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce'],
            ])
            ->add('description_secrete', TextareaType::class, [
                'required' => true,
                'label' => 'Description des secrets',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => 'les secrets ne sont accessibles qu\'aux membres selectionnés par le scénariste'],
            ])
            /*->add('scenariste', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Scénariste',
                'class' => \App\Entity\User::class,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->orderBy('u.nom', 'ASC');
                    $qb->orderBy('u.surnom', 'ASC');

                    return $qb;
                },
                'choice_label' => 'etatCivil',
            ])*/
            /*->add('responsable', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Chef du groupe',
                'class' => \App\Entity\Personnage::class,
                'query_builder' => static function (EntityRepository $er) {
                    return $qb = $er->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
                },
                'choice_label' => 'identity',
                'mapped' => false,
            ])*/
            ->add('personnage', EntityType::class, [
                'required' => false,
                'label' => 'Chef du groupe',
                'class' => Personnage::class,
                'query_builder' => static function (EntityRepository $er) {
                    return $qb = $er->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
                },
                'choice_label' => 'nom',
                // 'mapped' => false,
            ])
            /*->add('scenariste', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Scénariste',
                'required' => false,
                'class' => \App\Entity\User::class,
                'choice_label' => 'name',
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->where($qb->expr()->orX(
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%'))));
                    $qb->orderBy('ec.nom', 'ASC');

                    return $qb;
                },
            ])*/
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
