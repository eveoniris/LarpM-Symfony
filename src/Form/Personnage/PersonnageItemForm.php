<?php


namespace App\Form\Personnage;

use App\Entity\Item;
use App\Entity\Personnage;
use App\Repository\ItemRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageItemForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('items', EntityType::class, [
            'label' => 'Choisissez les objets possédés par le personnage en début de jeu',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'class' => Item::class,
            'choice_label' => 'identitereverse',
            'query_builder' => static function (ItemRepository $er) {
                return $er->createQueryBuilder('i')->orderBy('i.label', 'ASC');
            },
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.Personnage::class,
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
