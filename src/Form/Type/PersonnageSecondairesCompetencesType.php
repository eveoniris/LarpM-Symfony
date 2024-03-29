<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\PersonnageSecondairesCompetencesType.
 *
 * @author kevin
 */
class PersonnageSecondairesCompetencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('competence', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => false,
            'required' => true,
            'choice_label' => 'label',
            'class' => \App\Entity\Competence::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
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
