<?php


namespace App\Form\Groupe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\GroupeSecondairePostulerForm.
 *
 * @author kevin
 */
class GroupeSecondairePostulerForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('explanation', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'label' => 'Explication (obligatoire)',
            'required' => true,
            'attr' => [
                'rows' => 9,
                'class' => 'tinymce',
                'help' => 'Soyez convaincant, votre explication sera transmise au chef de groupe qui validera (ou pas) votre demande.'],
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
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
