<?php


namespace App\Form\Personnage;

use App\Entity\Personnage;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[Deprecated]
class PersonnageEditForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('surnom', TextType::class, [
            'required' => false,
            'label' => '',
        ])
            ->add('intrigue', ChoiceType::class, [
                'required' => true,
                'choices' => ['Oui' => true, 'Non' => false],
                'label' => 'Participer aux intrigues (aide de camps)',
            ])
            ->add('sensible', ChoiceType::class, [
                'required' => true,
                'choices' => ['Non' => false, 'Oui' => true],
                'label' => 'Personnage sensible',
            ])
            ->add('bracelet', ChoiceType::class, [
                'required' => true,
                'choices' => ['Non' => false, 'Oui' => true],
                'label' => 'Possédez-vous votre bracelet de langue',
                'help' => 'Si vous cochez Oui. Vous ne recevrez pas de bracelet de langue dans votre enveloppe personnage ',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageEdit';
    }
}
