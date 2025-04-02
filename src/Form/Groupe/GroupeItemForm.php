<?php


namespace App\Form\Groupe;

use App\Entity\Groupe;
use App\Entity\Item;
use App\Repository\ItemRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class GroupeItemForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('items', EntityType::class, [
            'label' => 'Choisissez les objets possédé par le groupe en début de jeu',
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
            'data_class' => '\\'.Groupe::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeItem';
    }
}
