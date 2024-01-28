<?php


namespace App\Form\Personnage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageRichesseForm.
 *
 * @author kevin
 */
class PersonnageRichesseForm extends AbstractType
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
                'help' => "Indiquez combien de pièces d'argent votre personnage doit recevoir",
            ],
        ])
            ->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageRichesse';
    }
}
