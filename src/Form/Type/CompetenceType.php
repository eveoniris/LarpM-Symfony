<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\CompetenceType.
 *
 * @author kevin
 */
class CompetenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('competence', 'entity', [
            'label' => false,
            'required' => true,
            'property' => 'nom',
            'class' => \App\Entity\Competence::class,
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\App\Entity\PersonnageCompetence',
        ]);
    }

    public function getName(): string
    {
        return 'personnageCompetence';
    }
}
