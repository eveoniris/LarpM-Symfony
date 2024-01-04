<?php


namespace App\Form\Intrigue;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\IntrigueRelectureForm.
 *
 * @author kevin
 */
class IntrigueRelectureForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('statut', 'choice', [
            'label' => 'Choisissez le statut',
            'required' => true,
            'choices' => [
                'Validé' => 'Validé',
                'Non validé' => 'Non validé',
            ],
        ])
            ->add('remarque', 'textarea', [
                'label' => 'Vos remarques éventuelles',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => '9',
                    'help' => 'Vos remarques vis à vis de cette intrigue.',
                ],
            ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Relecture::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'relecture';
    }
}
