<?php


namespace App\Form\GroupeSecondaire;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\GroupeSecondare\GroupeSecondaireNewMembreForm.
 *
 * @author kevin
 */
class GroupeSecondaireNewMembreForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => false,
            'label' => 'Choississez le personnage',
            'class' => \App\Entity\Personnage::class,
            'query_builder' => static function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('p');
                $qb->orderBy('p.nom', 'ASC');

                return $qb;
            },
            'attr' => [
                'class' => 'selectpicker',
                'data-live-search' => 'true',
                'placeholder' => 'Personnage',
            ],
            'choice_label' => 'nom',
            'mapped' => false,
        ])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Ajouter']);
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
