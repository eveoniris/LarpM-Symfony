<?php


namespace App\Form\Technologie;

use App\Entity\Ressource;
use App\Entity\TechnologiesRessources;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\TechnologieForm.
 *
 * @author Gérald
 */
class TechnologiesRessourcesForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('ressource', EntityType::class, [
            'class' => Ressource::class,
            'choice_label' => 'label',
            'label' => 'Ressource requise',
        ])
            ->add('quantite', ChoiceType::class, [
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7],
                'label' => 'Quantité',
            ])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TechnologiesRessources::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'technologiesRessources';
    }
}
