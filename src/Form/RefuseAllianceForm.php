<?php


namespace App\Form;

use App\Entity\GroupeAllie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\AgeForm.
 *
 * @author kevin
 */
class RefuseAllianceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('messageAllie', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'label' => 'Un petit mot pour expliquer votre démarche',
            'required' => true,
            'attr' => [
                'class' => 'tinymce',
                'rows' => 9,
                'help' => 'Ce texte sera transmis au chef de groupe concerné.'],
        ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => GroupeAllie::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'refuseAlliance';
    }
}
