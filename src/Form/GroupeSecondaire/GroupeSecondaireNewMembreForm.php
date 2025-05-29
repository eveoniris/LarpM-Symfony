<?php

namespace App\Form\GroupeSecondaire;

use App\Entity\Personnage;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeSecondaireNewMembreForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', EntityType::class, [
            'required' => false,
            'label' => 'Choississez le personnage',
            'class' => Personnage::class,
            'autocomplete' => true,
            'query_builder' => static fn(EntityRepository $er) => $er->createQueryBuilder('p')->orderBy('p.nom', 'ASC'),
            // 'attr' => [
            //    //'class' => 'selectpicker',
            //    'data-live-search' => 'true',
            //    'placeholder' => 'Personnage',
            // ],
            'choice_label' => 'idName',
            'mapped' => false,
        ])
            ->add('submit', SubmitType::class, ['label' => 'Ajouter']);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeSecondaireNewMembre';
    }
}
