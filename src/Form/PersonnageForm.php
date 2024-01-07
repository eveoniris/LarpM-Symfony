<?php

namespace App\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageForm.
 *
 * @author kevin
 */
class PersonnageForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
            'label' => '',
        ])
            ->add('surnom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => false,
                'label' => '',
            ])
            ->add('age', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => \App\Entity\Age::class,
                'property' => 'label',
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('a');
                    $qb->andWhere('a.enableCreation = true');

                    return $qb;
                },
            ])
            ->add('genre', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => \App\Entity\Genre::class,
                'property' => 'label',
            ])
            ->add('territoire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => 'Votre origine',
                'class' => \App\Entity\Territoire::class,
                'property' => 'nom',
                'query_builder' => static function (\LarpManager\Repository\TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere('t.territoire IS NULL');

                    return $qb;
                },
            ])
            ->add('intrigue', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [true => 'Oui', false => 'Non'],
                'label' => 'Participer aux intrigues',
            ])
            ->add('sensible', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [false => 'Non', true => 'Oui'],
                'label' => 'Personnage sensible',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnage';
    }
}
