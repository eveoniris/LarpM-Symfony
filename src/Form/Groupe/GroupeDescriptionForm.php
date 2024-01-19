<?php


namespace App\Form\Groupe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\GroupeDescriptionForm.
 *
 * @author kevin
 */
class GroupeDescriptionForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'label' => 'Description du groupe',
            'required' => false,
            'attr' => [
                'class' => 'tinymce',
                'row' => 9,
            ],
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
        return 'groupeDescription';
    }
}
