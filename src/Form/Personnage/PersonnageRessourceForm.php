<?php


namespace App\Form\Personnage;

use App\Form\Type\PersonnageRessourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\PersonnageRessourceForm.
 *
 * @author kevin
 */
class PersonnageRessourceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('randomCommun', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'mapped' => false,
                'label' => 'X ressources communes choisies au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien de ressources COMMUNES il faut ajouter à ce personnage.',
                ],
            ])
            ->add('randomRare', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'mapped' => false,
                'label' => 'X ressources rares choisies au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien de ressources RARES il faut ajouter à ce personnage.',
                ],
            ])
            ->add('personnageRessources', 'collection', [
                'label' => 'Ressources',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'type' => new PersonnageRessourceType(),
            ])
            ->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);
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
        return 'personnageRessource';
    }
}
