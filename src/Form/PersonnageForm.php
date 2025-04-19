<?php

namespace App\Form;

use App\Entity\Age;
use App\Entity\Genre;
use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Repository\TerritoireRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, [
            'required' => true,
            'label' => '',
        ])
            ->add('surnom', TextType::class, [
                'required' => false,
                'label' => '',
            ])
            ->add('age', EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => Age::class,
                'choice_label' => 'label',
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('a');
                    $qb->andWhere('a.enableCreation = true');

                    return $qb;
                },
            ])
            ->add('genre', EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => Genre::class,
                'choice_label' => 'label',
            ])
            ->add('territoire', EntityType::class, [
                'required' => true,
                'label' => 'Votre origine',
                'class' => Territoire::class,
                'choice_label' => 'nom',
                'query_builder' => static function (TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere('t.territoire IS NULL');

                    return $qb;
                },
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
        return 'personnage';
    }
}
