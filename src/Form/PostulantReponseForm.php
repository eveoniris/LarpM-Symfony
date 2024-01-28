<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PostulantReponseForm.
 *
 * @author kevin
 */
class PostulantReponseForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('explanation', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'label' => 'Votre réponse',
            'required' => true,
            'attr' => [
                'rows' => 9,
                'help' => "un petit mot de bienvenue, ou d'explication de votre refus"],
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'postulantReponse';
    }
}
