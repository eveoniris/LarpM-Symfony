<?php


namespace App\Form\Intrigue;

use App\Entity\Relecture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $builder->add('statut', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'label' => 'Choisissez le statut',
            'required' => true,
            'choices' => [
                'Validé' => 'Validé',
                'Non validé' => 'Non validé',
            ],
        ])
            ->add('remarque', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Relecture::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
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
