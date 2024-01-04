<?php


namespace App\Form\Groupe;

use App\Form\Type\GroupeHasRessourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\GroupeRessourceForm.
 *
 * @author kevin
 */
class GroupeRessourceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('randomCommun', 'integer', [
                'mapped' => false,
                'label' => 'X ressources communes choisies au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien de ressources COMMUNES il faut ajouter à ce groupe.',
                ],
            ])
            ->add('randomRare', 'integer', [
                'mapped' => false,
                'label' => 'X ressources rares choisies au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien de ressources RARES il faut ajouter à ce groupe.',
                ],
            ])
            ->add('groupeHasRessources', 'collection', [
                'label' => 'Ressources',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'type' => new GroupeHasRessourceType(),
            ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Groupe::class,
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
