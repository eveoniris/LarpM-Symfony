<?php


namespace App\Form\Groupe;

use App\Entity\Groupe;
use App\Form\Type\GroupeHasRessourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeRessourceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('randomCommun', IntegerType::class, [
                'mapped' => false,
                'label' => 'X ressources communes choisies au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien de ressources COMMUNES il faut ajouter à ce groupe.',
                ],
            ])
            ->add('randomRare', IntegerType::class, [
                'mapped' => false,
                'label' => 'X ressources rares choisies au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien de ressources RARES il faut ajouter à ce groupe.',
                ],
            ])
            ->add('groupeHasRessources', CollectionType::class, [
                'label' => 'Ressources',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => GroupeHasRessourceType::class,
            ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groupe::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeRessource';
    }
}
