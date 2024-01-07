<?php


namespace App\Form\GroupeGn;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\GroupeGn\GroupeGnPlaceAvailableForm.
 *
 * @author kevin
 */
class GroupeGnPlaceAvailableForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('placeAvailable', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
            'label' => 'Indiquez içi le nombre de personnes recherché pour compléter votre groupe',
            'required' => true,
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\GroupeGn::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeGnPlaceAvailable';
    }
}
