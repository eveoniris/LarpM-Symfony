<?php


namespace App\Form\Item;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Monnaie\ItemForm.
 *
 * @author kevin
 */
class ItemLinkForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', 'entity', [
            'required' => false,
            'mapped' => false,
            'label' => 'Personnage',
            'class' => \App\Entity\Personnage::class,
            'property' => 'nom',
            'attr' => [
                'help' => 'Personnage qui possède cet objet',
            ],
        ])
            ->add('groupe', 'entity', [
                'required' => false,
                'mapped' => false,
                'label' => 'Groupe',
                'class' => \App\Entity\Groupe::class,
                'property' => 'nom',
                'attr' => [
                    'help' => 'Groupe qui possède cet objet',
                ],
            ])
            ->add('lieu', 'entity', [
                'required' => false,
                'mapped' => false,
                'label' => 'Lieu',
                'class' => \App\Entity\Lieu::class,
                'property' => 'label',
                'attr' => [
                    'help' => 'Lieu ou est entreposé cet objet',
                ],
            ])
            ->add('submit', 'submit', [
                'label' => 'Valider',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Item::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'item';
    }
}
