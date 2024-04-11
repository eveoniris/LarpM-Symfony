<?php


namespace App\Form\GroupeGn;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\GroupeGn\GroupeGnForm.
 *
 * @author kevin
 */
class GroupeGnForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Jeu',
            'required' => true,
            'class' => \App\Entity\Gn::class,
            'choice_label' => 'label',
        ])
            ->add('free', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Groupe disponible ou réservé ?',
                'required' => false,
                'choices' => [
                    'Groupe disponible' => true,
                    'Groupe réservé' => false,
                ],
            ])
            ->add('code', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => false,
            ])
            ->add('jeuStrategique', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'Participe au jeu stratégique',
                'required' => false,
            ])
            ->add('jeuMaritime', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'Participe au jeu maritime',
                'required' => false,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\GroupeGn::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeGn';
    }
}
