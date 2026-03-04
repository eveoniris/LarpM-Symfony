<?php

declare(strict_types=1);

namespace App\Form\Personnage;

use App\Form\Type\PersonnageRessourceType as PersonnageRessourceFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageRessourceForm.
 *
 * @author kevin
 */
class PersonnageRessourceType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('randomCommun', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
            'mapped' => false,
            'label' => 'X ressources communes choisies au hasard',
            'required' => false,
            'attr' => [
                'help' => 'Indiquez combien de ressources COMMUNES il faut ajouter à ce personnage.',
            ],
        ])->add('randomRare', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
            'mapped' => false,
            'label' => 'X ressources rares choisies au hasard',
            'required' => false,
            'attr' => [
                'help' => 'Indiquez combien de ressources RARES il faut ajouter à ce personnage.',
            ],
        ])->add('personnageRessources', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
            'label' => 'Ressources',
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'entry_type' => PersonnageRessourceFieldType::class,
        ])->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\' . \App\Entity\Personnage::class,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}
