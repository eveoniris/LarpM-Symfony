<?php


namespace App\Form\Groupe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\GroupeRichesseForm.
 *
 * @author kevin
 */
class GroupeRichesseForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('richesse', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
            'label' => 'PA',
            'required' => false,
            'attr' => [
                'help' => "Indiquez combien de pièces d'argent votre groupe doit recevoir en plus des pièces d'argent fournies par ses territoires",
            ],
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
        return 'groupeRichesse';
    }
}
