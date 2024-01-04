<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\PersonnageSecondairesCompetencesType.
 *
 * @author kevin
 */
class PersonnageSecondairesCompetencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('competence', 'entity', [
            'label' => false,
            'required' => true,
            'property' => 'label',
            'class' => \App\Entity\Competence::class,
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\PersonnageSecondaireCompetence::class,
        ]);
    }

    public function getName(): string
    {
        return 'personnageSecondairesCompetences';
    }
}
