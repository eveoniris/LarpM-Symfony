<?php


namespace App\Form\Personnage;

use LarpManager\Repository\ItemRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\PersonnageDocumentForm.
 *
 * @author kevin
 */
class PersonnageItemForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('items', 'entity', [
            'label' => 'Choisissez les objets possédés par le personnage en début de jeu',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'class' => \App\Entity\Item::class,
            'property' => 'identitereverse',
            'query_builder' => static function (ItemRepository $er) {
                return $er->createQueryBuilder('i')->orderBy('i.label', 'ASC');
            },
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageItem';
    }
}
