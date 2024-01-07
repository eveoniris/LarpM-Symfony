<?php


namespace App\Form\GroupeSecondaire;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
                // TODO 'class' => 'tinymce',
                'help' => 'Soyez convaincant, votre explication sera transmise au chef de groupe qui validera (ou pas) votre demande.'],
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'secondaryGroupApply';
    }
}
