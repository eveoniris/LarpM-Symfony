<?php


namespace App\Form\Groupe;

use App\Entity\GroupeLangue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * LarpManager\Form\GroupeLangueForm.
 *
 * @author kevin
 */
class GroupeLangueForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'label' => 'Label',
            'required' => true,
            'attr' => ['maxlength' => 45],
            'constraints' => [
                new Length(['max' => 45]),
                new NotBlank(),
            ],
        ])
            ->add('couleur', TextType::class, [
                'label' => 'Couleur',
                'required' => true,
                'attr' => ['maxlength' => 45],
                'constraints' => [
                    new Length(['max' => 45]),
                    new NotBlank(),
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GroupeLangue::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeLangue';
    }
}
