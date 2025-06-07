<?php


namespace App\Form\GroupeSecondaire;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeSecondairePostulerForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('explanation', TextareaType::class, [
            'label' => 'Explication (obligatoire)',
            'required' => true,
            'attr' => [
                'rows' => 9,
                'class' => 'tinymce',
                'help' => 'Soyez convaincant, votre explication sera transmise au recruteur qui validera (ou pas) votre demande.',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
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
        return 'secondaryGroupApply';
    }
}
