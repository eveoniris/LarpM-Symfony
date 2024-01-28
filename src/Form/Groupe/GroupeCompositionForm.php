<?php


namespace App\Form\Groupe;

use App\Form\Type\ClasseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Groupe\GroupeCompositionForm.
 *
 * @author kevin
 */
class GroupeCompositionForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('groupeClasses', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
            'label' => 'Composition',
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'entry_type' => ClasseType::class,
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
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
        return 'groupeComposition';
    }
}
