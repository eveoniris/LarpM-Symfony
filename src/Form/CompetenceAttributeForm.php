<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\CompetenceAttributeForm.
 *
 * @author Jérôme
 */
class CompetenceAttributeForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('attributeType', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Type',
                'required' => true,
                'class' => \App\Entity\AttributeType::class,
                'property' => 'label',
            ])
            ->add('value', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => true,
                'label' => 'Nombre',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\CompetenceAttribute::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'competenceAttribute';
    }
}
